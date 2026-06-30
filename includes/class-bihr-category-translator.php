<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Category_Translator {

    const IMPORT_SUBDIR = 'bihr-import';
    const OUTPUT_SUBDIR = 'bihr';

    protected $logger;
    protected $api_key;
    protected $output_dir;
    protected $mapping_file;
    protected $csv_file;

    public function __construct( $logger = null ) {
        $this->logger       = $logger;
        $this->api_key      = bihrwi_decrypt_credential( get_option( 'bihrwi_openai_key', '' ) );
        $upload_dir         = wp_upload_dir();
        $base               = trailingslashit( $upload_dir['basedir'] );
        $this->output_dir   = $base . self::OUTPUT_SUBDIR;
        $this->mapping_file = $this->output_dir . '/category-mapping.json';
        $this->csv_file     = $this->output_dir . '/categories-bihr.csv';
    }

    protected function log( $message ) {
        if ( $this->logger && method_exists( $this->logger, 'log' ) ) {
            $this->logger->log( '[CategoryTranslator] ' . $message );
        }
    }

    public function is_ai_enabled() {
        return ! empty( $this->api_key );
    }

    protected function ensure_output_dir() {
        if ( ! file_exists( $this->output_dir ) ) {
            wp_mkdir_p( $this->output_dir );
        }
        $htaccess = $this->output_dir . '/.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, 'Options -Indexes' . PHP_EOL );
        }
    }

    protected function get_import_dir() {
        $upload_dir = wp_upload_dir();
        return trailingslashit( $upload_dir['basedir'] ) . self::IMPORT_SUBDIR;
    }

    public function get_mapping() {
        if ( ! file_exists( $this->mapping_file ) ) {
            return array();
        }
        $content = file_get_contents( $this->mapping_file );
        $data    = json_decode( $content, true );
        return is_array( $data ) ? $data : array();
    }

    protected function save_mapping( $mapping ) {
        $this->ensure_output_dir();
        file_put_contents(
            $this->mapping_file,
            wp_json_encode( $mapping, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
        );
    }

    public function clear_mapping() {
        if ( file_exists( $this->mapping_file ) ) {
            unlink( $this->mapping_file );
        }
    }

    /**
     * Traduit un nom de catégorie BIHR en français.
     * Retourne le nom original si aucune traduction trouvée.
     *
     * @param string $bihr_name Nom BIHR (anglais).
     * @return string
     */
    public function translate_name( $bihr_name ) {
        $mapping = $this->get_mapping();
        $trimmed = trim( $bihr_name );
        return isset( $mapping[ $trimmed ] ) && $mapping[ $trimmed ] !== '' ? $mapping[ $trimmed ] : $bihr_name;
    }

    /**
     * Transforme un tableau de noms BIHR en tableau d'objets {value, label}.
     * Préserve la clé BIHR comme valeur interne pour les requêtes.
     *
     * @param string[] $bihr_names
     * @return array[] Chaque élément : ['value' => 'BIHR_KEY', 'label' => 'Traduction FR']
     */
    public function to_labeled_array( $bihr_names ) {
        $mapping = $this->get_mapping();
        $result  = array();
        foreach ( $bihr_names as $name ) {
            $trimmed  = trim( $name );
            $label    = ( isset( $mapping[ $trimmed ] ) && $mapping[ $trimmed ] !== '' ) ? $mapping[ $trimmed ] : $name;
            $result[] = array(
                'value' => $name,
                'label' => $label,
            );
        }
        return $result;
    }

    /**
     * Scanne tous les fichiers cat-extended-full-*.csv et retourne les catégories uniques.
     *
     * @param callable|null $callback fn($type, $name_or_msg, $current, $total, $extra)
     * @return array { unique_strings: string[], combinations: array[] }
     */
    public function scan_unique_categories( $callback = null ) {
        $import_dir = $this->get_import_dir();
        $files      = glob( $import_dir . '/cat-extended-full-*.csv' );

        if ( empty( $files ) ) {
            $this->log( 'Aucun fichier cat-extended-full-*.csv dans ' . $import_dir );
            return array( 'unique_strings' => array(), 'combinations' => array() );
        }

        $unique_strings = array();
        $combinations   = array();
        $combo_keys     = array();
        $total_files    = count( $files );

        foreach ( $files as $index => $file ) {
            $filename = basename( $file );
            $this->log( "Analyse : $filename" );

            if ( $callback ) {
                call_user_func( $callback, 'file_start', $filename, $index + 1, $total_files );
            }

            $handle = fopen( $file, 'r' );
            if ( ! $handle ) {
                continue;
            }

            $header = fgetcsv( $handle, 0, ',' );
            if ( ! $header ) {
                fclose( $handle );
                continue;
            }

            $cols = array_flip( array_map( 'trim', $header ) );
            $idx1 = isset( $cols['Category1'] ) ? $cols['Category1'] : false;
            $idx2 = isset( $cols['Category2'] ) ? $cols['Category2'] : false;
            $idx3 = isset( $cols['Category3'] ) ? $cols['Category3'] : false;

            if ( false === $idx1 ) {
                $this->log( "Colonnes Category1/2/3 introuvables dans $filename" );
                fclose( $handle );
                continue;
            }

            $rows_read = 0;
            while ( ( $row = fgetcsv( $handle, 0, ',' ) ) !== false ) {
                $cat1 = isset( $row[ $idx1 ] ) ? trim( $row[ $idx1 ] ) : '';
                $cat2 = ( false !== $idx2 && isset( $row[ $idx2 ] ) ) ? trim( $row[ $idx2 ] ) : '';
                $cat3 = ( false !== $idx3 && isset( $row[ $idx3 ] ) ) ? trim( $row[ $idx3 ] ) : '';

                if ( $cat1 !== '' ) {
                    $unique_strings[ $cat1 ] = true;
                }
                if ( $cat2 !== '' ) {
                    $unique_strings[ $cat2 ] = true;
                }
                if ( $cat3 !== '' ) {
                    $unique_strings[ $cat3 ] = true;
                }

                $combo_key = $cat1 . '|' . $cat2 . '|' . $cat3;
                if ( ! isset( $combo_keys[ $combo_key ] ) ) {
                    $combo_keys[ $combo_key ] = true;
                    $combinations[] = array(
                        'cat1' => $cat1,
                        'cat2' => $cat2,
                        'cat3' => $cat3,
                    );
                }
                $rows_read++;
            }

            fclose( $handle );

            if ( $callback ) {
                call_user_func( $callback, 'file_done', $filename, $index + 1, $total_files, $rows_read );
            }
        }

        ksort( $unique_strings );

        usort( $combinations, function( $a, $b ) {
            $cmp = strcmp( $a['cat1'], $b['cat1'] );
            if ( 0 !== $cmp ) return $cmp;
            $cmp = strcmp( $a['cat2'], $b['cat2'] );
            if ( 0 !== $cmp ) return $cmp;
            return strcmp( $a['cat3'], $b['cat3'] );
        } );

        return array(
            'unique_strings' => array_keys( $unique_strings ),
            'combinations'   => $combinations,
        );
    }

    protected function generate_csv( $combinations ) {
        $this->ensure_output_dir();
        $handle = fopen( $this->csv_file, 'w' );
        if ( ! $handle ) {
            return false;
        }
        fwrite( $handle, "\xEF\xBB\xBF" );
        fputcsv( $handle, array( 'Category1', 'Category2', 'Category3' ) );
        foreach ( $combinations as $combo ) {
            fputcsv( $handle, array( $combo['cat1'], $combo['cat2'], $combo['cat3'] ) );
        }
        fclose( $handle );
        return true;
    }

    /**
     * Envoie un lot de catégories à OpenAI et retourne le tableau de traductions.
     */
    protected function translate_batch( $categories ) {
        if ( empty( $this->api_key ) ) {
            return array();
        }

        $list = implode( "\n", array_map( function( $c ) {
            return '- ' . $c;
        }, $categories ) );

        $prompt = "Tu es un expert en traduction pour une boutique WooCommerce de pièces et accessoires moto en France.\n\n"
            . "Traduis les noms de catégories suivants (anglais technique abrégé BIHR) en français.\n"
            . "Règles :\n"
            . "- Compréhensibles pour des clients français\n"
            . "- SEO-friendly pour une boutique moto\n"
            . "- Concises (max 50 caractères)\n"
            . "- Adaptées à une arborescence WooCommerce\n\n"
            . "Catégories :\n$list\n\n"
            . "Réponds UNIQUEMENT avec un JSON valide (sans balises markdown) :\n"
            . '{\"CATEGORY_ORIGINAL\": \"Traduction française\"}';

        $body = array(
            'model'       => 'gpt-4o-mini',
            'messages'    => array(
                array(
                    'role'    => 'system',
                    'content' => 'Tu es un traducteur spécialisé en terminologie moto. Tu réponds uniquement avec du JSON valide, sans explication ni balise markdown.',
                ),
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
            'max_tokens'  => 2000,
            'temperature' => 0.2,
        );

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 90,
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->log( 'OpenAI erreur: ' . $response->get_error_message() );
            return array();
        }

        $status  = wp_remote_retrieve_response_code( $response );
        $rbody   = wp_remote_retrieve_body( $response );

        if ( 200 !== $status ) {
            $this->log( "OpenAI HTTP $status: $rbody" );
            return array();
        }

        $data    = json_decode( $rbody, true );
        $content = isset( $data['choices'][0]['message']['content'] ) ? $data['choices'][0]['message']['content'] : '';

        // Enlever les balises markdown si présentes
        $content = preg_replace( '/^```json?\s*/i', '', trim( $content ) );
        $content = preg_replace( '/\s*```$/', '', $content );

        $translations = json_decode( $content, true );
        if ( ! is_array( $translations ) ) {
            $this->log( 'Parse JSON échoué: ' . substr( $content, 0, 200 ) );
            return array();
        }

        return $translations;
    }

    /**
     * Orchestrateur principal : scan + CSV + traduction IA + catégories WooCommerce.
     *
     * @param callable|null $callback fn($type, $message, $current, $total, $extra=[])
     */
    public function analyze_and_translate( $callback = null ) {
        $start_time = microtime( true );

        // Étape 1 : scan CSV
        if ( $callback ) {
            call_user_func( $callback, 'status', 'Analyse des fichiers CSV...', 0, 4 );
        }

        $scanned        = $this->scan_unique_categories( function( $type, $name, $current, $total, $extra = 0 ) use ( $callback ) {
            if ( $callback ) {
                call_user_func( $callback, $type, $name, $current, $total, $extra );
            }
        } );

        $unique_strings = $scanned['unique_strings'];
        $combinations   = $scanned['combinations'];

        if ( empty( $unique_strings ) ) {
            if ( $callback ) {
                call_user_func( $callback, 'error',
                    'Aucune catégorie trouvée. Vérifiez que des fichiers cat-extended-full-*.csv sont présents dans wp-content/uploads/bihr-import/',
                    0, 0
                );
            }
            return false;
        }

        // Étape 2 : génération CSV
        if ( $callback ) {
            call_user_func( $callback, 'status', 'Génération du fichier categories-bihr.csv...', 1, 4 );
        }
        $this->generate_csv( $combinations );

        // Étape 3 : traduction IA
        $mapping      = $this->get_mapping();
        $to_translate = array_values( array_filter( $unique_strings, function( $s ) use ( $mapping ) {
            return ! isset( $mapping[ $s ] ) || '' === $mapping[ $s ];
        } ) );

        $cached_count     = count( $unique_strings ) - count( $to_translate );
        $new_translations = 0;
        $ai_errors        = 0;

        if ( $callback ) {
            call_user_func( $callback, 'status',
                sprintf(
                    '%d catégories uniques — %d en cache — %d à traduire',
                    count( $unique_strings ),
                    $cached_count,
                    count( $to_translate )
                ),
                2, 4
            );
        }

        if ( ! empty( $to_translate ) ) {
            if ( ! $this->is_ai_enabled() ) {
                if ( $callback ) {
                    call_user_func( $callback, 'warning',
                        'Clé OpenAI non configurée — traduction IA ignorée. Configurez-la dans les réglages.',
                        2, 4
                    );
                }
            } else {
                $batches       = array_chunk( $to_translate, 25 );
                $total_batches = count( $batches );

                foreach ( $batches as $i => $batch ) {
                    if ( $callback ) {
                        call_user_func( $callback, 'translate_progress',
                            sprintf( 'Traduction lot %d / %d (%d catégories)...', $i + 1, $total_batches, count( $batch ) ),
                            $i + 1, $total_batches
                        );
                    }

                    $translations = $this->translate_batch( $batch );

                    if ( ! empty( $translations ) ) {
                        foreach ( $translations as $original => $translated ) {
                            $mapping[ $original ] = sanitize_text_field( $translated );
                            $new_translations++;
                        }
                        $this->save_mapping( $mapping );
                    } else {
                        $ai_errors++;
                        $this->log( "Lot $i : échec traduction IA" );
                    }

                    if ( $i < $total_batches - 1 ) {
                        usleep( 300000 );
                    }
                }
            }
        }

        // Étape 4 : création catégories WooCommerce françaises
        if ( $callback ) {
            call_user_func( $callback, 'status', 'Création des catégories WooCommerce françaises...', 3, 4 );
        }
        $wc_categories_created = $this->create_french_wc_categories( $combinations, $mapping, $callback );

        $elapsed = round( microtime( true ) - $start_time, 1 );
        $stats   = array(
            'categories_detected' => count( $combinations ),
            'unique_strings'      => count( $unique_strings ),
            'new_translations'    => $new_translations,
            'cached_translations' => $cached_count,
            'ai_errors'           => $ai_errors,
            'wc_categories'       => $wc_categories_created,
            'elapsed'             => $elapsed,
        );

        if ( $callback ) {
            call_user_func( $callback, 'complete', 'Analyse et traduction terminées !', 4, 4, $stats );
        }

        return $stats;
    }

    protected function create_french_wc_categories( $combinations, $mapping, $callback = null ) {
        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return 0;
        }

        $created = 0;
        $total   = count( $combinations );

        foreach ( $combinations as $i => $combo ) {
            $fr1 = ! empty( $mapping[ $combo['cat1'] ] ) ? $mapping[ $combo['cat1'] ] : $combo['cat1'];
            $fr2 = ( '' !== $combo['cat2'] && ! empty( $mapping[ $combo['cat2'] ] ) ) ? $mapping[ $combo['cat2'] ] : $combo['cat2'];
            $fr3 = ( '' !== $combo['cat3'] && ! empty( $mapping[ $combo['cat3'] ] ) ) ? $mapping[ $combo['cat3'] ] : $combo['cat3'];

            BihrWI_Category_Path::ensure_product_categories( $fr1, $fr2, $fr3 );
            $created++;

            if ( $callback && 0 === $i % 50 ) {
                call_user_func( $callback, 'wc_progress',
                    "Catégories WC : $created / $total",
                    $created, $total
                );
            }
        }

        return $created;
    }

    /**
     * Applique les catégories françaises à tous les produits WooCommerce BIHR existants.
     *
     * @param callable|null $callback fn($type, $message, $current, $total, $extra=[])
     */
    public function apply_to_products( $callback = null ) {
        global $wpdb;

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return array( 'error' => 'WooCommerce non disponible' );
        }

        $mapping = $this->get_mapping();
        $start   = microtime( true );

        // fields='ids' : ne charge que les IDs en mémoire (~660 KB pour 83 000 produits).
        $product_ids = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_bihr_cat_l1',
                    'compare' => 'EXISTS',
                ),
            ),
        ) );

        $total   = count( $product_ids );
        $updated = 0;

        if ( $callback ) {
            call_user_func( $callback, 'status', "$total produits WooCommerce à traiter...", 0, $total );
        }

        // Charger les 3 meta sources en une seule requête SQL au lieu de 3×N get_post_meta().
        // Sur 83 000 produits : 249 000 requêtes → 1 requête.
        $meta_by_post = array();
        $chunks       = array_chunk( $product_ids, 1000 );
        foreach ( $chunks as $chunk ) {
            $id_list = implode( ',', array_map( 'intval', $chunk ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $rows = $wpdb->get_results(
                "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
                 WHERE post_id IN ({$id_list})
                   AND meta_key IN ('_bihr_cat_l1','_bihr_cat_l2','_bihr_cat_l3')",
                ARRAY_A
            );
            foreach ( $rows as $row ) {
                $meta_by_post[ (int) $row['post_id'] ][ $row['meta_key'] ] = $row['meta_value'];
            }
        }

        // Différer le recomptage des termes : une seule passe à la fin au lieu de N passes.
        wp_defer_term_counting( true );

        // Accumuler les mises à jour FR pour un INSERT groupé (1 DELETE + 1 INSERT par tranche).
        $pending_fr    = array(); // post_id => [fr1, fr2, fr3]
        $write_chunk   = 500;

        $flush_pending_fr = function() use ( &$pending_fr, $wpdb ) {
            if ( empty( $pending_fr ) ) {
                return;
            }
            $id_list = implode( ',', array_map( 'intval', array_keys( $pending_fr ) ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query(
                "DELETE FROM {$wpdb->postmeta}
                 WHERE post_id IN ({$id_list})
                   AND meta_key IN ('_bihr_category1_fr','_bihr_category2_fr','_bihr_category3_fr')"
            );
            // 3 lignes par produit : (post_id, meta_key, meta_value) × 3 meta keys.
            $placeholders = array();
            $params       = array();
            foreach ( $pending_fr as $pid => $fr ) {
                $placeholders[] = '(%d,%s,%s)';
                $placeholders[] = '(%d,%s,%s)';
                $placeholders[] = '(%d,%s,%s)';
                array_push( $params, $pid, '_bihr_category1_fr', $fr[0] );
                array_push( $params, $pid, '_bihr_category2_fr', $fr[1] );
                array_push( $params, $pid, '_bihr_category3_fr', $fr[2] );
            }
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query(
                $wpdb->prepare(
                    'INSERT INTO ' . $wpdb->postmeta . ' (post_id,meta_key,meta_value) VALUES ' . implode( ',', $placeholders ),
                    ...$params
                )
            );
            $pending_fr = array();
        };

        foreach ( $product_ids as $product_id ) {
            $pid    = (int) $product_id;
            $cat_l1 = (string) ( $meta_by_post[ $pid ]['_bihr_cat_l1'] ?? '' );
            $cat_l2 = (string) ( $meta_by_post[ $pid ]['_bihr_cat_l2'] ?? '' );
            $cat_l3 = (string) ( $meta_by_post[ $pid ]['_bihr_cat_l3'] ?? '' );

            $fr1 = ( '' !== $cat_l1 && isset( $mapping[ $cat_l1 ] ) && '' !== $mapping[ $cat_l1 ] ) ? $mapping[ $cat_l1 ] : $cat_l1;
            $fr2 = ( '' !== $cat_l2 && isset( $mapping[ $cat_l2 ] ) && '' !== $mapping[ $cat_l2 ] ) ? $mapping[ $cat_l2 ] : $cat_l2;
            $fr3 = ( '' !== $cat_l3 && isset( $mapping[ $cat_l3 ] ) && '' !== $mapping[ $cat_l3 ] ) ? $mapping[ $cat_l3 ] : $cat_l3;

            $pending_fr[ $pid ] = array( $fr1, $fr2, $fr3 );

            $term_id = BihrWI_Category_Path::ensure_product_categories( $fr1, $fr2, $fr3 );
            if ( $term_id ) {
                wp_set_object_terms( $pid, array( $term_id ), 'product_cat' );
            }

            $updated++;

            if ( 0 === $updated % $write_chunk ) {
                $flush_pending_fr();
            }

            if ( $callback && 0 === $updated % 100 ) {
                call_user_func( $callback, 'progress',
                    "Produits mis à jour : $updated / $total",
                    $updated, $total
                );
            }
        }

        // Écrire le dernier lot et déclencher le recomptage des termes.
        $flush_pending_fr();
        wp_defer_term_counting( false );

        $elapsed = round( microtime( true ) - $start, 1 );
        $stats   = array(
            'total'   => $total,
            'updated' => $updated,
            'elapsed' => $elapsed,
        );

        if ( $callback ) {
            call_user_func( $callback, 'complete',
                "$updated produits mis à jour en {$elapsed}s",
                $total, $total, $stats
            );
        }

        return $stats;
    }

    /**
     * Retourne les traductions paginées pour l'interface admin.
     */
    public function get_all_translations( $search = '', $page = 1, $per_page = 50 ) {
        $mapping = $this->get_mapping();

        if ( '' !== $search ) {
            $search  = strtolower( $search );
            $mapping = array_filter( $mapping, function( $fr, $en ) use ( $search ) {
                return false !== strpos( strtolower( $en ), $search )
                    || false !== strpos( strtolower( $fr ), $search );
            }, ARRAY_FILTER_USE_BOTH );
        }

        $total = count( $mapping );
        $slice = array_slice( $mapping, ( $page - 1 ) * $per_page, $per_page, true );

        return array(
            'items'       => $slice,
            'total'       => $total,
            'total_pages' => (int) ceil( $total / max( 1, $per_page ) ),
            'page'        => $page,
        );
    }

    /**
     * Exporte le mapping complet en CSV (avec BOM UTF-8 pour Excel).
     */
    public function export_mapping_as_csv() {
        $mapping = $this->get_mapping();
        $lines   = array( "\xEF\xBB\xBF" . "\"Catégorie BIHR\",\"Catégorie française\"\n" );
        foreach ( $mapping as $en => $fr ) {
            $lines[] = '"' . str_replace( '"', '""', $en ) . '","' . str_replace( '"', '""', $fr ) . '"' . "\n";
        }
        return implode( '', $lines );
    }
}
