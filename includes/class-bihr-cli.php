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
     * Importe une plage de produits depuis wp_bihr_products vers WooCommerce.
     *
     * ## OPTIONS
     *
     * [--offset=<offset>]
     * : ID de départ dans wp_bihr_products (par défaut 0).
     *
     * [--limit=<limit>]
     * : Nombre maximum de produits à traiter dans ce run (par défaut 1000).
     *
     * [--ids=<ids>]
     * : Liste d'IDs wp_bihr_products séparés par des virgules (prioritaire sur offset/limit).
     *
     * ## EXAMPLES
     *
     *   # Importer les 1000 premiers produits
     *   wp bihr import-products --offset=0 --limit=1000
     *
     *   # Importer une liste précise d'IDs
     *   wp bihr import-products --ids=12,13,14,15
     */
    public function import_products( $args, $assoc_args ) {
        global $wpdb;

        $logger = new BihrWI_Logger();
        $sync   = new BihrWI_Product_Sync( $logger );

        // Priorité à la liste d'IDs explicite
        $ids_arg = isset( $assoc_args['ids'] ) ? $assoc_args['ids'] : '';
        if ( ! empty( $ids_arg ) ) {
            $ids = array_filter( array_map( 'intval', explode( ',', $ids_arg ) ) );
        } else {
            $offset = isset( $assoc_args['offset'] ) ? max( 0, (int) $assoc_args['offset'] ) : 0;
            $limit  = isset( $assoc_args['limit'] ) ? max( 1, (int) $assoc_args['limit'] ) : 1000;

            $table_name = $wpdb->prefix . 'bihr_products';
            $ids        = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT id FROM `{$table_name}` ORDER BY id ASC LIMIT %d OFFSET %d",
                    $limit,
                    $offset
                )
            );
        }

        if ( empty( $ids ) ) {
            WP_CLI::warning( 'Aucun produit à importer (liste vide).' );
            return;
        }

        $total   = count( $ids );
        $success = 0;
        $errors  = 0;

        WP_CLI::log( sprintf( 'Démarrage import WP-CLI de %d produit(s)…', $total ) );

        foreach ( $ids as $index => $product_id ) {
            $num = $index + 1;
            try {
                $wc_id = $sync->import_to_woocommerce( $product_id );
                if ( $wc_id ) {
                    $success++;
                    WP_CLI::log( sprintf( '[%d/%d] Produit #%d importé (WC ID: %d)', $num, $total, $product_id, $wc_id ) );
                } else {
                    $errors++;
                    WP_CLI::warning( sprintf( '[%d/%d] Échec import produit #%d', $num, $total, $product_id ) );
                }
            } catch ( Exception $e ) {
                $errors++;
                WP_CLI::warning( sprintf( '[%d/%d] Erreur produit #%d : %s', $num, $total, $product_id, $e->getMessage() ) );
            }
        }

        WP_CLI::success( sprintf( 'Import terminé. Succès: %d | Erreurs: %d', $success, $errors ) );
    }
}
