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

        // Compte rapide via SQL direct.
        $total = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_bihr_cat_l1'
             WHERE p.post_type = 'product' AND p.post_status != 'trash'"
        );

        if ( $callback ) {
            call_user_func( $callback, 'status', "$total produits WooCommerce à traiter...", 0, $total );
        }

        if ( 0 === $total ) {
            $stats = array( 'total' => 0, 'updated' => 0, 'elapsed' => 0 );
            if ( $callback ) {
                call_user_func( $callback, 'complete', '0 produit à traiter.', 0, 0, $stats );
            }
            return $stats;
        }

        // Étape 1 — Récupérer les combinaisons uniques et pré-créer les termes WC une seule fois.
        if ( $callback ) {
            call_user_func( $callback, 'status', 'Création des termes de catégories françaises...', 0, $total );
            if ( ob_get_level() ) { ob_flush(); }
            flush();
        }

        $unique_combos = $wpdb->get_results(
            "SELECT DISTINCT
                COALESCE(pm1.meta_value,'') AS l1,
                COALESCE(pm2.meta_value,'') AS l2,
                COALESCE(pm3.meta_value,'') AS l3
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_bihr_cat_l1'
             LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_bihr_cat_l2'
             LEFT JOIN {$wpdb->postmeta} pm3 ON pm3.post_id = p.ID AND pm3.meta_key = '_bihr_cat_l3'
             WHERE p.post_type = 'product' AND p.post_status != 'trash'",
            ARRAY_A
        );

        // Cache combo_key → term_taxonomy_id pour l'insertion en masse.
        $combo_ttid_cache = array();
        foreach ( $unique_combos as $row ) {
            $l1  = (string) $row['l1'];
            $l2  = (string) $row['l2'];
            $l3  = (string) $row['l3'];
            $fr1 = ( '' !== $l1 && isset( $mapping[ $l1 ] ) && '' !== $mapping[ $l1 ] ) ? $mapping[ $l1 ] : $l1;
            $fr2 = ( '' !== $l2 && isset( $mapping[ $l2 ] ) && '' !== $mapping[ $l2 ] ) ? $mapping[ $l2 ] : $l2;
            $fr3 = ( '' !== $l3 && isset( $mapping[ $l3 ] ) && '' !== $mapping[ $l3 ] ) ? $mapping[ $l3 ] : $l3;
            $key = "$fr1|||$fr2|||$fr3";
            if ( ! isset( $combo_ttid_cache[ $key ] ) ) {
                $term_id = BihrWI_Category_Path::ensure_product_categories( $fr1, $fr2, $fr3 );
                $tt_id   = $term_id ? (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
                        $term_id
                    )
                ) : 0;
                $combo_ttid_cache[ $key ] = $tt_id;
            }
        }
        unset( $unique_combos );

        // Étape 2 — Traitement par lots de 500 produits.
        $chunk_size = 500;
        $offset     = 0;
        $updated    = 0;

        while ( $offset < $total ) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT p.ID,
                        COALESCE(pm1.meta_value,'') AS l1,
                        COALESCE(pm2.meta_value,'') AS l2,
                        COALESCE(pm3.meta_value,'') AS l3
                     FROM {$wpdb->posts} p
                     LEFT JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_bihr_cat_l1'
                     LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_bihr_cat_l2'
                     LEFT JOIN {$wpdb->postmeta} pm3 ON pm3.post_id = p.ID AND pm3.meta_key = '_bihr_cat_l3'
                     WHERE p.post_type = 'product' AND p.post_status != 'trash'
                     ORDER BY p.ID
                     LIMIT %d OFFSET %d",
                    $chunk_size,
                    $offset
                ),
                ARRAY_A
            );

            if ( empty( $rows ) ) {
                break;
            }

            // Construire les données par produit.
            $pid_fr1    = array(); // pid => fr1
            $pid_fr2    = array();
            $pid_fr3    = array();
            $pid_ttid   = array(); // pid => term_taxonomy_id
            $all_pids   = array();

            foreach ( $rows as $row ) {
                $pid = (int) $row['ID'];
                $l1  = (string) $row['l1'];
                $l2  = (string) $row['l2'];
                $l3  = (string) $row['l3'];
                $fr1 = ( '' !== $l1 && isset( $mapping[ $l1 ] ) && '' !== $mapping[ $l1 ] ) ? $mapping[ $l1 ] : $l1;
                $fr2 = ( '' !== $l2 && isset( $mapping[ $l2 ] ) && '' !== $mapping[ $l2 ] ) ? $mapping[ $l2 ] : $l2;
                $fr3 = ( '' !== $l3 && isset( $mapping[ $l3 ] ) && '' !== $mapping[ $l3 ] ) ? $mapping[ $l3 ] : $l3;

                $all_pids[]    = $pid;
                $pid_fr1[$pid] = $fr1;
                $pid_fr2[$pid] = $fr2;
                $pid_fr3[$pid] = $fr3;
                $key           = "$fr1|||$fr2|||$fr3";
                $pid_ttid[$pid] = isset( $combo_ttid_cache[ $key ] ) ? $combo_ttid_cache[ $key ] : 0;
            }

            $ids_str = implode( ',', $all_pids );

            // Mise à jour des metas françaises via CASE WHEN (une requête par meta_key).
            foreach ( array( '_bihr_category1_fr' => $pid_fr1, '_bihr_category2_fr' => $pid_fr2, '_bihr_category3_fr' => $pid_fr3 ) as $meta_key => $pid_vals ) {
                $cases = '';
                $args  = array();
                foreach ( $pid_vals as $pid => $val ) {
                    $cases .= $wpdb->prepare( ' WHEN %d THEN %s', $pid, $val );
                    $args[] = $pid;
                }
                // UPDATE pour les produits qui ont déjà la meta.
                $wpdb->query(
                    "UPDATE {$wpdb->postmeta}
                     SET meta_value = CASE post_id $cases END
                     WHERE meta_key = '$meta_key' AND post_id IN ($ids_str)"
                );
                // INSERT pour ceux qui ne l'ont pas encore.
                $existing = $wpdb->get_col(
                    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '$meta_key' AND post_id IN ($ids_str)"
                );
                $missing = array_diff( $all_pids, array_map( 'intval', $existing ) );
                if ( ! empty( $missing ) ) {
                    $inserts = array();
                    foreach ( $missing as $pid ) {
                        $inserts[] = $wpdb->prepare( '(%d, %s, %s)', $pid, $meta_key, $pid_vals[$pid] );
                    }
                    $wpdb->query( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ',', $inserts ) );
                }
            }

            // Supprimer les anciennes product_cat et insérer les nouvelles en masse.
            $wpdb->query(
                "DELETE tr FROM {$wpdb->term_relationships} tr
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 WHERE tr.object_id IN ($ids_str) AND tt.taxonomy = 'product_cat'"
            );

            $rel_rows = array();
            foreach ( $pid_ttid as $pid => $tt_id ) {
                if ( $tt_id ) {
                    $rel_rows[] = "($pid, $tt_id, 0)";
                }
            }
            if ( ! empty( $rel_rows ) ) {
                $wpdb->query(
                    "INSERT IGNORE INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id, term_order) VALUES "
                    . implode( ',', $rel_rows )
                );
            }

            $updated += count( $rows );
            $offset  += $chunk_size;

            if ( $callback ) {
                call_user_func( $callback, 'progress', "Produits traités : $updated / $total", $updated, $total );
                if ( ob_get_level() ) { ob_flush(); }
                flush();
            }

            $wpdb->flush();
        }

        // Recalculer les compteurs de termes.
        if ( $callback ) {
            call_user_func( $callback, 'status', 'Recalcul des compteurs de catégories...', $updated, $total );
            if ( ob_get_level() ) { ob_flush(); }
            flush();
        }
        $unique_ttids = array_unique( array_filter( array_values( $combo_ttid_cache ) ) );
        if ( ! empty( $unique_ttids ) ) {
            wp_update_term_count_now( $unique_ttids, 'product_cat' );
        }

        $elapsed = round( microtime( true ) - $start, 1 );
        $stats   = array(
            'total'   => $total,
            'updated' => $updated,
            'elapsed' => $elapsed,
        );

        if ( $callback ) {
            call_user_func( $callback, 'complete', "$updated produits mis à jour en {$elapsed}s", $total, $total, $stats );
        }

        return $stats;
    }

    /**
     * Phase 1 de l'application chunked : compte les produits et pré-crée tous les termes WC.
     * Stocke le cache combo→ttid et le total dans des transients (1h).
     *
     * @return array {total: int} ou {error: string}
     */
    public function prepare_category_apply() {
        global $wpdb;

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return array( 'error' => 'WooCommerce non disponible' );
        }

        $mapping = $this->get_mapping();

        $total = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_bihr_cat_l1'
             WHERE p.post_type = 'product' AND p.post_status != 'trash'"
        );

        if ( 0 === $total ) {
            return array( 'total' => 0 );
        }

        $unique_combos = $wpdb->get_results(
            "SELECT DISTINCT
                COALESCE(pm1.meta_value,'') AS l1,
                COALESCE(pm2.meta_value,'') AS l2,
                COALESCE(pm3.meta_value,'') AS l3
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_bihr_cat_l1'
             LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_bihr_cat_l2'
             LEFT JOIN {$wpdb->postmeta} pm3 ON pm3.post_id = p.ID AND pm3.meta_key = '_bihr_cat_l3'
             WHERE p.post_type = 'product' AND p.post_status != 'trash'",
            ARRAY_A
        );

        $combo_ttid_cache = array();
        foreach ( $unique_combos as $row ) {
            $l1  = (string) $row['l1'];
            $l2  = (string) $row['l2'];
            $l3  = (string) $row['l3'];
            $fr1 = ( '' !== $l1 && isset( $mapping[ $l1 ] ) && '' !== $mapping[ $l1 ] ) ? $mapping[ $l1 ] : $l1;
            $fr2 = ( '' !== $l2 && isset( $mapping[ $l2 ] ) && '' !== $mapping[ $l2 ] ) ? $mapping[ $l2 ] : $l2;
            $fr3 = ( '' !== $l3 && isset( $mapping[ $l3 ] ) && '' !== $mapping[ $l3 ] ) ? $mapping[ $l3 ] : $l3;
            $key = "$fr1|||$fr2|||$fr3";
            if ( ! isset( $combo_ttid_cache[ $key ] ) ) {
                $term_id = BihrWI_Category_Path::ensure_product_categories( $fr1, $fr2, $fr3 );
                $tt_id   = $term_id ? (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat'",
                    $term_id
                ) ) : 0;
                $combo_ttid_cache[ $key ] = $tt_id;
            }
        }

        set_transient( 'bihrwi_apply_combo_cache', $combo_ttid_cache, HOUR_IN_SECONDS );
        set_transient( 'bihrwi_apply_total', $total, HOUR_IN_SECONDS );
        set_transient( 'bihrwi_apply_started_at', microtime( true ), HOUR_IN_SECONDS );

        return array( 'total' => $total );
    }

    /**
     * Phase 2 de l'application chunked : traite un lot de produits.
     *
     * @param int $offset     Position de départ.
     * @param int $chunk_size Nombre de produits à traiter.
     * @return array {updated, offset, total, done, [elapsed, error]}
     */
    public function apply_category_chunk( $offset, $chunk_size = 200 ) {
        global $wpdb;

        $combo_ttid_cache = get_transient( 'bihrwi_apply_combo_cache' );
        $total            = (int) get_transient( 'bihrwi_apply_total' );

        if ( false === $combo_ttid_cache || ! $total ) {
            return array( 'error' => 'Session expirée, relancez depuis le début.' );
        }

        $mapping = $this->get_mapping();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID,
                    COALESCE(pm1.meta_value,'') AS l1,
                    COALESCE(pm2.meta_value,'') AS l2,
                    COALESCE(pm3.meta_value,'') AS l3
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_bihr_cat_l1'
                 LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_bihr_cat_l2'
                 LEFT JOIN {$wpdb->postmeta} pm3 ON pm3.post_id = p.ID AND pm3.meta_key = '_bihr_cat_l3'
                 WHERE p.post_type = 'product' AND p.post_status != 'trash'
                 ORDER BY p.ID
                 LIMIT %d OFFSET %d",
                $chunk_size,
                $offset
            ),
            ARRAY_A
        );

        if ( empty( $rows ) ) {
            return array( 'updated' => 0, 'offset' => $offset, 'total' => $total, 'done' => true );
        }

        $pid_fr1  = array();
        $pid_fr2  = array();
        $pid_fr3  = array();
        $pid_ttid = array();
        $all_pids = array();

        foreach ( $rows as $row ) {
            $pid = (int) $row['ID'];
            $l1  = (string) $row['l1'];
            $l2  = (string) $row['l2'];
            $l3  = (string) $row['l3'];
            $fr1 = ( '' !== $l1 && isset( $mapping[ $l1 ] ) && '' !== $mapping[ $l1 ] ) ? $mapping[ $l1 ] : $l1;
            $fr2 = ( '' !== $l2 && isset( $mapping[ $l2 ] ) && '' !== $mapping[ $l2 ] ) ? $mapping[ $l2 ] : $l2;
            $fr3 = ( '' !== $l3 && isset( $mapping[ $l3 ] ) && '' !== $mapping[ $l3 ] ) ? $mapping[ $l3 ] : $l3;
            $all_pids[]      = $pid;
            $pid_fr1[ $pid ] = $fr1;
            $pid_fr2[ $pid ] = $fr2;
            $pid_fr3[ $pid ] = $fr3;
            $key              = "$fr1|||$fr2|||$fr3";
            $pid_ttid[ $pid ] = isset( $combo_ttid_cache[ $key ] ) ? $combo_ttid_cache[ $key ] : 0;
        }

        $ids_str = implode( ',', $all_pids );

        foreach ( array( '_bihr_category1_fr' => $pid_fr1, '_bihr_category2_fr' => $pid_fr2, '_bihr_category3_fr' => $pid_fr3 ) as $meta_key => $pid_vals ) {
            $cases = '';
            foreach ( $pid_vals as $pid => $val ) {
                $cases .= $wpdb->prepare( ' WHEN %d THEN %s', $pid, $val );
            }
            $wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = CASE post_id $cases END WHERE meta_key = '$meta_key' AND post_id IN ($ids_str)" );
            $existing = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '$meta_key' AND post_id IN ($ids_str)" );
            $missing  = array_diff( $all_pids, array_map( 'intval', $existing ) );
            if ( ! empty( $missing ) ) {
                $ins = array();
                foreach ( $missing as $pid ) {
                    $ins[] = $wpdb->prepare( '(%d, %s, %s)', $pid, $meta_key, $pid_vals[ $pid ] );
                }
                $wpdb->query( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ',', $ins ) );
            }
        }

        $wpdb->query(
            "DELETE tr FROM {$wpdb->term_relationships} tr
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE tr.object_id IN ($ids_str) AND tt.taxonomy = 'product_cat'"
        );

        $rel_rows = array();
        foreach ( $pid_ttid as $pid => $tt_id ) {
            if ( $tt_id ) {
                $rel_rows[] = "($pid, $tt_id, 0)";
            }
        }
        if ( ! empty( $rel_rows ) ) {
            $wpdb->query( "INSERT IGNORE INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id, term_order) VALUES " . implode( ',', $rel_rows ) );
        }

        $new_offset = $offset + count( $rows );
        $done       = ( $new_offset >= $total );

        if ( $done ) {
            $unique_ttids = array_unique( array_filter( array_values( $combo_ttid_cache ) ) );
            if ( ! empty( $unique_ttids ) ) {
                wp_update_term_count_now( $unique_ttids, 'product_cat' );
            }
            $elapsed = round( microtime( true ) - (float) get_transient( 'bihrwi_apply_started_at' ), 1 );
            delete_transient( 'bihrwi_apply_combo_cache' );
            delete_transient( 'bihrwi_apply_total' );
            delete_transient( 'bihrwi_apply_started_at' );
            return array( 'updated' => count( $rows ), 'offset' => $new_offset, 'total' => $total, 'done' => true, 'elapsed' => $elapsed );
        }

        $wpdb->flush();
        return array( 'updated' => count( $rows ), 'offset' => $new_offset, 'total' => $total, 'done' => false );
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
