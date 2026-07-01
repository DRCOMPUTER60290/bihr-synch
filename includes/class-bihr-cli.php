<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Commandes WP-CLI pour le plugin BIHR Synch.
 *
 * @package Bihr_Sync
 * @since   2.0.0
 */
class BihrWI_CLI_Commands {

    /**
     * Importe les produits depuis wp_bihr_products vers WooCommerce.
     *
     * Optimisé pour WP-CLI (512MB, pas de timeout PHP, o2switch 64 cores) :
     *   - preload_product_rows() : 1 SELECT/chunk au lieu de N (élimine N+1 sur wp_bihr_products)
     *   - preload_product_lookup() : 1 SELECT/chunk sur wc_product_meta_lookup (élimine N+1 SKU)
     *   - batch_update_product_links() : 1 INSERT…ON DUPLICATE KEY UPDATE/chunk au lieu de N UPDATE
     *   - log bufferisé par chunk : 1 fwrite groupé au lieu de N syscalls
     *   - defer term/product counting pendant chaque chunk
     *   - wp_cache_flush() toutes les 200 itérations
     *   - chunk de 1000 : ~60s/chunk estimé < wait_timeout MySQL 260s
     *
     * Modes images :
     *   (défaut)       : images différées → lancer ensuite "wp bihr download-images --all"
     *   --with-images  : après chaque chunk produits, télécharge leurs images en parallèle
     *                    (curl_multi, 40 concurrent) — tout en un, sans étape séparée
     *
     * ## OPTIONS
     *
     * [--offset=<n>]
     * : Décalage de départ dans wp_bihr_products (défaut: 0).
     *
     * [--limit=<n>]
     * : Nombre max de produits à traiter au total (défaut: 0 = tous les non importés).
     *
     * [--chunk=<n>]
     * : Taille d'un chunk de traitement (défaut: 1000 — calibré < 260s MySQL o2switch).
     *
     * [--ids=<ids>]
     * : Liste d'IDs wp_bihr_products séparés par virgule (prioritaire sur offset/limit).
     *
     * [--with-images]
     * : Télécharge les images après chaque chunk via curl_multi (40 concurrent).
     *   Plus lent qu'un import seul mais tout se fait en une commande.
     *
     * [--img-concurrent=<n>]
     * : Connexions cURL simultanées pour les images (défaut: 40, avec --with-images).
     *
     * ## EXAMPLES
     *
     *   # Import complet, images différées (le plus rapide pour les produits)
     *   wp bihr import-products
     *   wp bihr download-images --all
     *
     *   # Tout en un : produits + images dans la même commande
     *   wp bihr import-products --with-images
     *
     *   # Import limité pour test
     *   wp bihr import-products --limit=100 --chunk=100
     */
    public function import_products( $args, $assoc_args ) {
        global $wpdb;

        $chunk_size     = isset( $assoc_args['chunk'] )          ? max( 1, (int) $assoc_args['chunk'] )          : 1000;
        $limit_total    = isset( $assoc_args['limit'] )          ? max( 0, (int) $assoc_args['limit'] )          : 0;
        $offset         = isset( $assoc_args['offset'] )         ? max( 0, (int) $assoc_args['offset'] )         : 0;
        $with_images    = isset( $assoc_args['with-images'] );
        $img_concurrent = isset( $assoc_args['img-concurrent'] ) ? max( 1, (int) $assoc_args['img-concurrent'] ) : 40;
        $skip_images    = true; // toujours différer en import, on télécharge après si --with-images

        $logger = new BihrWI_Logger();
        $sync   = new BihrWI_Product_Sync( $logger );

        // Construire la liste complète des IDs à traiter
        $ids_arg = isset( $assoc_args['ids'] ) ? $assoc_args['ids'] : '';
        if ( ! empty( $ids_arg ) ) {
            $all_ids = array_values( array_filter( array_map( 'intval', explode( ',', $ids_arg ) ) ) );
        } else {
            $table_name = $wpdb->prefix . 'bihr_products';
            $sql_limit  = $limit_total > 0 ? "LIMIT {$limit_total}" : '';
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $all_ids = array_map( 'intval', $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT id FROM `{$table_name}` WHERE product_id IS NULL ORDER BY id ASC LIMIT %d OFFSET %d",
                    $limit_total > 0 ? $limit_total : PHP_INT_MAX,
                    $offset
                )
            ) );
        }

        if ( empty( $all_ids ) ) {
            WP_CLI::success( 'Aucun produit à importer.' );
            return;
        }

        $total   = count( $all_ids );
        $success = 0;
        $errors  = 0;
        $chunks  = array_chunk( $all_ids, $chunk_size );

        WP_CLI::log( sprintf(
            'Import de %d produit(s) en %d chunk(s) de %d (images: %s)',
            $total,
            count( $chunks ),
            $chunk_size,
            $skip_images ? 'différées' : 'inline'
        ) );

        foreach ( $chunks as $chunk_index => $chunk_ids ) {
            $chunk_num   = $chunk_index + 1;
            $chunk_start = microtime( true );

            WP_CLI::log( sprintf( '--- Chunk %d/%d (%d produits) ---', $chunk_num, count( $chunks ), count( $chunk_ids ) ) );

            // Précharger toutes les lignes wp_bihr_products du chunk en 1 seul SELECT
            $sync->preload_product_rows( $chunk_ids );

            // Précharger le cache SKU pour ce chunk (élimine N+1 sur wc_product_meta_lookup)
            $sync->preload_product_lookup( $chunk_ids );

            // Buffériser les logs : 1 fwrite groupé par chunk au lieu de N syscalls fichier
            $logger->enable_buffer();

            // Différer les recomptages coûteux pendant le chunk
            wp_defer_term_counting( true );
            if ( function_exists( 'wc_defer_product_counting' ) ) {
                wc_defer_product_counting( true );
            }

            $i           = 0;
            $link_pairs  = array(); // bihr_id => wc_id, pour batch_update_product_links()

            foreach ( $chunk_ids as $product_id ) {
                $i++;
                try {
                    // skip_link_update=true : on regroupe les UPDATE en fin de chunk
                    $wc_id = $sync->import_to_woocommerce( $product_id, $skip_images, true );
                    if ( $wc_id ) {
                        $link_pairs[ $product_id ] = $wc_id;
                        $success++;
                    } else {
                        $errors++;
                        WP_CLI::warning( "Échec bihr_id={$product_id}" );
                    }
                } catch ( Throwable $e ) {
                    $errors++;
                    WP_CLI::warning( "Erreur bihr_id={$product_id} : " . $e->getMessage() );
                }

                // Vider le cache objet toutes les 200 itérations
                // (24 GB buffer pool InnoDB : cache WP beaucoup moins critique qu'en mutu classique)
                if ( 0 === $i % 200 ) {
                    wp_cache_flush();
                    if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
                        $wpdb->queries = array();
                    }
                }
            }

            // 1 seul INSERT…ON DUPLICATE KEY UPDATE pour tout le chunk (au lieu de N UPDATE individuels)
            $sync->batch_update_product_links( $link_pairs );

            // Écrire tous les logs accumulés pendant le chunk en 1 seule opération disque
            $logger->flush_buffer();
            $logger->disable_buffer();

            // Réactiver les recomptages et vider wpdb après chaque chunk
            wp_defer_term_counting( false );
            if ( function_exists( 'wc_defer_product_counting' ) ) {
                wc_defer_product_counting( false );
            }
            $wpdb->flush();

            $elapsed = microtime( true ) - $chunk_start;
            WP_CLI::log( sprintf(
                '    → %.1fs (%.2fs/produit) | total: succès=%d erreurs=%d',
                $elapsed,
                $elapsed / count( $chunk_ids ),
                $success,
                $errors
            ) );

            // Avertir si on approche du wait_timeout MySQL (260s)
            if ( $elapsed > 200 ) {
                WP_CLI::warning( sprintf( 'Chunk lent (%.0fs > 200s) — réduire --chunk', $elapsed ) );
            }

            // --with-images : télécharger les images de ce chunk en parallèle avant de continuer
            if ( $with_images ) {
                $img_pending = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                    '_bihr_pending_image_url'
                ) );
                if ( $img_pending > 0 ) {
                    WP_CLI::log( sprintf( '    [images] %d en attente → téléchargement parallèle (%d concurrent)…', $img_pending, $img_concurrent ) );
                    $img_start = microtime( true );
                    // Télécharger exactement les images de ce chunk (count = taille du chunk)
                    $remaining_after = $sync->download_pending_images_parallel( count( $chunk_ids ), $img_concurrent );
                    WP_CLI::log( sprintf(
                        '    [images] → %.1fs | %d restantes (chunks précédents)',
                        microtime( true ) - $img_start,
                        $remaining_after
                    ) );
                }
            }
        }

        WP_CLI::success( sprintf( 'Import terminé. Succès: %d | Erreurs: %d | Total: %d', $success, $errors, $total ) );
    }

    /**
     * Télécharge les images en attente (_bihr_pending_image_url) via curl_multi.
     *
     * En WP-CLI : WP_MAX_MEMORY_LIMIT=512MB, pas de timeout PHP.
     * Calibrage o2switch (64 cores, 115 GB disque) : --batch=2000 --concurrent=40
     *
     * ## OPTIONS
     *
     * [--batch=<n>]
     * : Images par run (défaut: 2000 — calibré pour 512MB WP-CLI o2switch).
     *
     * [--concurrent=<n>]
     * : cURL simultanés par chunk (défaut: 40 — o2switch 64 cores).
     *
     * [--all]
     * : Boucle jusqu'à épuisement de la file d'images.
     *
     * ## EXAMPLES
     *
     *   wp bihr download-images --all
     *   wp bihr download-images --batch=2000 --concurrent=40 --all
     */
    public function download_images( $args, $assoc_args ) {
        global $wpdb;

        $batch      = isset( $assoc_args['batch'] )      ? max( 1, (int) $assoc_args['batch'] )      : 2000;
        $concurrent = isset( $assoc_args['concurrent'] ) ? max( 1, (int) $assoc_args['concurrent'] ) : 40;
        $loop_all   = isset( $assoc_args['all'] );

        $logger = new BihrWI_Logger();
        $sync   = new BihrWI_Product_Sync( $logger );

        $total_processed = 0;
        $run = 0;

        do {
            $pending = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_bihr_pending_image_url'
            ) );

            if ( 0 === $pending ) {
                WP_CLI::success( 'Toutes les images ont été téléchargées.' );
                break;
            }

            $run++;
            WP_CLI::log( sprintf(
                'Run #%d — %d en attente, traitement de %d (concurrent=%d)…',
                $run, $pending, min( $batch, $pending ), $concurrent
            ) );

            $run_start = microtime( true );
            $remaining = $sync->download_pending_images_parallel( $batch, $concurrent );
            $done_this_run = $pending - $remaining;
            $total_processed += $done_this_run;

            WP_CLI::log( sprintf(
                '  → %d traitées en %.1fs | %d restantes',
                $done_this_run,
                microtime( true ) - $run_start,
                $remaining
            ) );

        } while ( $loop_all && $remaining > 0 );

        WP_CLI::success( sprintf( 'Téléchargement terminé. Total : %d image(s).', $total_processed ) );
    }
}
