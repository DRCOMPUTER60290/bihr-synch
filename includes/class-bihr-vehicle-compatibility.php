<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestion de la compatibilité véhicule-produit
 */
class BihrWI_Vehicle_Compatibility {

    protected $logger;
    protected $vehicles_table;
    protected $compatibility_table;
    protected $import_dir;

    public function __construct( BihrWI_Logger $logger = null ) {
        global $wpdb;
        
        $this->logger              = $logger ?? new BihrWI_Logger();
        $this->vehicles_table      = $wpdb->prefix . 'bihr_vehicles';
        $this->compatibility_table = $wpdb->prefix . 'bihr_vehicle_compatibility';
        $this->import_dir          = trailingslashit( wp_upload_dir()['basedir'] ) . 'bihr-import/';
        
        // Vérifier et créer les tables si nécessaire
        $this->ensure_tables_exist();
        $this->ensure_import_dir_exists();
    }

    /**
     * Initialise WP_Filesystem pour les opérations de fichiers
     */
    protected function get_wp_filesystem() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
        global $wp_filesystem;
        return $wp_filesystem;
    }

    /**
     * S'assure que le dossier d'import existe
     */
    protected function ensure_import_dir_exists() {
        if ( ! file_exists( $this->import_dir ) ) {
            wp_mkdir_p( $this->import_dir );
        }
    }

    /**
     * Vérifie si les tables existent et les crée si nécessaire
     */
    protected function ensure_tables_exist() {
        global $wpdb;
        
        $vehicles_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->vehicles_table
            )
        );
        
        $compatibility_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $this->compatibility_table
            )
        );
        
        if ( ! $vehicles_exists || ! $compatibility_exists ) {
            $this->create_tables();
        }
    }

    /**
     * Crée les tables de compatibilité
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Table des véhicules
        $sql_vehicles = "CREATE TABLE IF NOT EXISTS {$this->vehicles_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            vehicle_code VARCHAR(50) NOT NULL,
            version_code VARCHAR(50),
            commercial_model_code VARCHAR(50),
            manufacturer_code VARCHAR(50),
            vehicle_year YEAR,
            version_name VARCHAR(255),
            commercial_model_name VARCHAR(255),
            manufacturer_name VARCHAR(100),
            universe_name VARCHAR(100),
            category_name VARCHAR(100),
            displacement_cm3 INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY vehicle_code (vehicle_code),
            KEY manufacturer_code (manufacturer_code),
            KEY vehicle_year (vehicle_year),
            KEY commercial_model_code (commercial_model_code)
        ) $charset_collate;";

        // Table de compatibilité produit-véhicule
        $sql_compatibility = "CREATE TABLE IF NOT EXISTS {$this->compatibility_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            vehicle_code VARCHAR(50) NOT NULL,
            part_number VARCHAR(100) NOT NULL,
            barcode VARCHAR(100),
            manufacturer_part_number VARCHAR(100),
            position_id VARCHAR(50),
            position_value VARCHAR(255),
            attributes TEXT,
            source_brand VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vehicle_code (vehicle_code),
            KEY part_number (part_number),
            KEY manufacturer_part_number (manufacturer_part_number),
            KEY source_brand (source_brand)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_vehicles );
        dbDelta( $sql_compatibility );

        $this->logger->log( 'Tables de compatibilité véhicule créées' );
    }

    /**
     * Importe la liste des véhicules depuis VehiclesList.csv
     */
    public function import_vehicles_list( $file_path = null ) {
        global $wpdb;
        $this->logger->log( '=== IMPORT LISTE VÉHICULES ===' );

        $file_path = $file_path ?: $this->import_dir . 'VehiclesList.csv';
        $this->logger->log( "Fichier: {$file_path}" );

        $wp_filesystem = $this->get_wp_filesystem();
        
        if ( ! $wp_filesystem->exists( $file_path ) ) {
            $this->logger->log( 'Erreur: Impossible d\'ouvrir le fichier' );
            return array(
                'success' => false,
                'message' => 'Impossible d\'ouvrir le fichier',
                'imported' => 0,
                'errors' => 0
            );
        }

        $content = $wp_filesystem->get_contents( $file_path );
        if ( false === $content ) {
            $this->logger->log( 'Erreur: Impossible de lire le fichier' );
            return array(
                'success' => false,
                'message' => 'Impossible de lire le fichier',
                'imported' => 0,
                'errors' => 0
            );
        }

        $lines = explode( "\n", $content );
        $header = str_getcsv( array_shift( $lines ), ',' );
        if ( ! $header ) {
            return array(
                'success' => false,
                'message' => 'Fichier CSV invalide (header manquant)',
                'imported' => 0,
                'errors' => 0
            );
        }

        // Vider la table avant import
        $wpdb->query( "TRUNCATE TABLE {$this->vehicles_table}" );
        $this->logger->log( 'Table véhicules vidée' );

        $count = 0;
        $errors = 0;
        $vehicle_format = array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d' );

        foreach ( $lines as $line ) {
            if ( trim( $line ) === '' ) {
                continue;
            }

            $row = str_getcsv( $line, ',' );
            if ( count( $row ) < 11 ) {
                continue;
            }

            $vehicle_data = array(
                'vehicle_code'           => sanitize_text_field( $row[0] ?? '' ),
                'version_code'           => sanitize_text_field( $row[1] ?? '' ),
                'commercial_model_code'  => sanitize_text_field( $row[2] ?? '' ),
                'manufacturer_code'      => sanitize_text_field( $row[3] ?? '' ),
                'vehicle_year'           => absint( $row[4] ?? 0 ),
                'version_name'           => sanitize_text_field( $row[5] ?? '' ),
                'commercial_model_name'  => sanitize_text_field( $row[6] ?? '' ),
                'manufacturer_name'      => sanitize_text_field( $row[7] ?? '' ),
                'universe_name'          => sanitize_text_field( $row[8] ?? '' ),
                'category_name'          => sanitize_text_field( $row[9] ?? '' ),
                'displacement_cm3'       => absint( $row[10] ?? 0 ),
            );

            $result = $wpdb->insert( $this->vehicles_table, $vehicle_data, $vehicle_format );
            
            if ( $result ) {
                $count++;
            } else {
                $errors++;
            }
        }

        $this->logger->log( "✓ Import terminé: {$count} véhicules importés, {$errors} erreurs" );
        $this->logger->log( '==============================' );

        return array(
            'success' => true,
            'message' => "{$count} véhicules importés, {$errors} erreurs",
            'imported' => $count,
            'errors' => $errors
        );
    }

    /**
     * Vide les tables de compatibilité
     */
    public function clear_data() {
        global $wpdb;

        $wpdb->query( "TRUNCATE TABLE {$this->vehicles_table}" );
        $wpdb->query( "TRUNCATE TABLE {$this->compatibility_table}" );

        $this->logger->log( 'Tables de compatibilité vidées' );

        return array(
            'success'  => true,
            'message'  => 'Tables vidées',
            'vehicles' => 0,
            'links'    => 0,
        );
    }

    /**
     * Décompresse une archive ZIP dans le dossier d'import
     */
    public function unzip_to_import_dir( $zip_path ) {
        $this->ensure_import_dir_exists();

        if ( ! file_exists( $zip_path ) ) {
            return array(
                'success' => false,
                'message' => 'Archive introuvable: ' . $zip_path,
            );
        }

        if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();

        $result = unzip_file( $zip_path, $this->import_dir );

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'message' => $result->get_error_message(),
            );
        }

        return array(
            'success' => true,
            'message' => 'Archive extraite',
            'target'  => $this->import_dir,
        );
    }

    /**
     * Importe les compatibilités par marque avec support de progression
     * 
     * @param string $brand_name Nom de la marque
     * @param string $file_path Chemin du fichier CSV
     * @param int $batch_start Ligne de départ pour ce batch
     * @return array Résultat avec progression
     */
    public function import_brand_compatibility( $brand_name, $file_path = null, $batch_start = 0 ) {
        $brand_name = sanitize_text_field( (string) $brand_name );
        $file_path  = $file_path ?: $this->import_dir . '[' . $brand_name . '].csv';
        
        if ( ! file_exists( $file_path ) ) {
            return array(
                'success'       => false,
                'imported'      => 0,
                'errors'        => 0,
                'total_lines'   => 0,
                'processed'     => 0,
                'progress'      => 0,
                'is_complete'   => false
            );
        }

        global $wpdb;

        // Compter le nombre total de lignes une seule fois (stocké en transient)
        $transient_key = 'bihr_import_total_' . md5( $file_path );
        $total_lines = get_transient( $transient_key );
        
        if ( false === $total_lines ) {
            $total_lines = $this->count_csv_lines( $file_path );
            set_transient( $transient_key, $total_lines, HOUR_IN_SECONDS );
        }

        // Optimisation MySQL : désactiver les vérifications de clés au début du premier batch
        if ( $batch_start === 0 ) {
            $wpdb->query( "ALTER TABLE {$this->compatibility_table} DISABLE KEYS" );
        }

        $batch_size = 50000; // Traiter 50000 lignes par batch (optimisé pour fichiers très volumineux 400k+)
        
        // Utiliser un fichier stream au lieu de charger tout en mémoire (CRITIQUE pour 400k lignes)
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return array(
                'success'       => false,
                'imported'      => 0,
                'errors'        => 0,
                'total_lines'   => $total_lines,
                'processed'     => 0,
                'progress'      => 0,
                'is_complete'   => false
            );
        }
        
        $handle = fopen( $file_path, 'r' );
        if ( false === $handle ) {
            return array(
                'success'       => false,
                'imported'      => 0,
                'errors'        => 0,
                'total_lines'   => $total_lines,
                'processed'     => 0,
                'progress'      => 0,
                'is_complete'   => false
            );
        }
        
        // Sauter le header
        $header = fgetcsv( $handle, 0, ',' );
        if ( false === $header ) {
            fclose( $handle );
            delete_transient( $transient_key );
            return array(
                'success'       => false,
                'imported'      => 0,
                'errors'        => 0,
                'total_lines'   => $total_lines,
                'processed'     => 0,
                'progress'      => 0,
                'is_complete'   => false
            );
        }
        
        // Sauter aux lignes précédentes si reprise (sans charger en mémoire)
        for ( $i = 0; $i < $batch_start; $i++ ) {
            if ( false === fgetcsv( $handle, 0, ',' ) ) {
                break; // Fin du fichier
            }
        }
        
        $count = 0;
        $errors = 0;
        $batch = array();
        $current_line = $batch_start;
        
        // Lire ligne par ligne jusqu'à atteindre batch_size
        while ( $current_line < $batch_start + $batch_size ) {
            $row = fgetcsv( $handle, 0, ',' );
            if ( false === $row ) {
                break; // Fin du fichier
            }
            
            // Ignorer les lignes vides ou invalides
            if ( count( $row ) < 3 || empty( trim( $row[0] ?? '' ) ) ) {
                $current_line++;
                continue;
            }
            
            $batch[] = array(
                'vehicle_code'              => sanitize_text_field( trim( $row[0] ?? '' ) ),
                'part_number'               => sanitize_text_field( trim( $row[1] ?? '' ) ),
                'barcode'                   => sanitize_text_field( trim( $row[2] ?? '' ) ),
                'manufacturer_part_number'  => sanitize_text_field( trim( $row[3] ?? '' ) ),
                'position_id'               => sanitize_text_field( trim( $row[4] ?? '' ) ),
                'position_value'            => sanitize_text_field( trim( $row[5] ?? '' ) ),
                'attributes'                => sanitize_textarea_field( trim( $row[6] ?? '' ) ),
                'source_brand'              => $brand_name,
            );
            $current_line++;
        }
        
        fclose( $handle );

        // Insérer le batch en masse avec une seule requête SQL (ULTRA OPTIMISÉ pour 400k+ lignes)
        if ( ! empty( $batch ) ) {
            $table_name = esc_sql( $this->compatibility_table );
            
            // Utiliser des transactions pour améliorer les performances
            $wpdb->query( 'START TRANSACTION' );
            
            // Construire la requête INSERT en masse (plus efficace que prepare() pour chaque ligne)
            $values_parts = array();
            $all_values = array();
            
            foreach ( $batch as $data ) {
                $values_parts[] = '(%s, %s, %s, %s, %s, %s, %s, %s)';
                $all_values[] = $data['vehicle_code'];
                $all_values[] = $data['part_number'];
                $all_values[] = $data['barcode'];
                $all_values[] = $data['manufacturer_part_number'];
                $all_values[] = $data['position_id'];
                $all_values[] = $data['position_value'];
                $all_values[] = $data['attributes'];
                $all_values[] = $data['source_brand'];
            }
            
            if ( ! empty( $values_parts ) ) {
                $sql = "INSERT INTO `{$table_name}` 
                        (`vehicle_code`, `part_number`, `barcode`, `manufacturer_part_number`, 
                         `position_id`, `position_value`, `attributes`, `source_brand`) 
                        VALUES " . implode( ', ', $values_parts );
                
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared below with all placeholders
                $prepared_sql = $wpdb->prepare( $sql, $all_values );
                
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared above
                $result = $wpdb->query( $prepared_sql );
                
                if ( false !== $result ) {
                    $count = $result; // Nombre de lignes insérées
                    $wpdb->query( 'COMMIT' );
                } else {
                    // En cas d'erreur, rollback
                    $wpdb->query( 'ROLLBACK' );
                    $errors = count( $batch );
                    $count = 0;
                    $this->logger->log( 'Erreur insertion en masse pour ' . $brand_name . ': ' . $wpdb->last_error );
                }
            } else {
                $wpdb->query( 'ROLLBACK' );
            }
        }

        // Calculer la progression
        $processed = $batch_start + count( $batch );
        $progress = $total_lines > 0 ? round( ( $processed / $total_lines ) * 100 ) : 100;
        $is_complete = $processed >= $total_lines;

        // Nettoyer le transient et flush cache si terminé
        if ( $is_complete ) {
            delete_transient( $transient_key );
            // Réactiver les index à la fin
            $wpdb->query( "ALTER TABLE {$this->compatibility_table} ENABLE KEYS" );
            wp_cache_flush();
        }

        return array(
            'success'       => true,
            'imported'      => $count,
            'errors'        => $errors,
            'total_lines'   => $total_lines,
            'processed'     => $processed,
            'progress'      => $progress,
            'is_complete'   => $is_complete,
            'next_batch'    => $is_complete ? 0 : $processed,
        );
    }

    /**
     * Compte les lignes dans un fichier CSV
     * 
     * @param string $file_path Chemin du fichier
     * @return int Nombre de lignes (sans le header)
     */
    protected function count_csv_lines( $file_path ) {
        $count = 0;
        global $wp_filesystem;
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        if ( ! $wp_filesystem->exists( $file_path ) ) {
            return 0;
        }
        $content = $wp_filesystem->get_contents( $file_path );
        if ( false === $content ) {
            return 0;
        }
        $lines = explode( "\n", $content );
        array_shift( $lines ); // header
        $count = 0;
        foreach ( $lines as $line ) {
            if ( trim( $line ) === '' ) continue;
            $count++;
        }
        return $count;
    }

    /**
     * Récupère tous les fabricants distincts
     */
    public function get_manufacturers() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT DISTINCT manufacturer_code, manufacturer_name 
             FROM {$this->vehicles_table} 
             WHERE manufacturer_name IS NOT NULL AND manufacturer_name != ''
             ORDER BY manufacturer_name ASC",
            ARRAY_A
        );

        return $results;
    }

    /**
     * Récupère les modèles pour un fabricant donné
     */
    public function get_models_by_manufacturer( $manufacturer_code ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT commercial_model_code, commercial_model_name 
                 FROM {$this->vehicles_table} 
                 WHERE manufacturer_code = %s 
                 AND commercial_model_name IS NOT NULL 
                 AND commercial_model_name != ''
                 ORDER BY commercial_model_name ASC",
                $manufacturer_code
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Récupère les versions pour un modèle donné
     */
    public function get_versions_by_model( $commercial_model_code ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vehicle_code, version_name, vehicle_year, displacement_cm3 
                 FROM {$this->vehicles_table} 
                 WHERE commercial_model_code = %s 
                 ORDER BY vehicle_year DESC, version_name ASC",
                $commercial_model_code
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Récupère les produits compatibles avec un véhicule
     */
    public function get_compatible_products( $vehicle_code ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT c.part_number, c.source_brand, c.manufacturer_part_number
                 FROM {$this->compatibility_table} c
                 WHERE c.vehicle_code = %s
                 ORDER BY c.part_number ASC",
                $vehicle_code
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Récupère les véhicules compatibles avec un produit
     */
    public function get_compatible_vehicles( $part_number ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT 
                    v.vehicle_code,
                    v.manufacturer_name,
                    v.commercial_model_name,
                    v.version_name,
                    v.vehicle_year,
                    v.displacement_cm3,
                    c.source_brand
                 FROM {$this->compatibility_table} c
                 INNER JOIN {$this->vehicles_table} v ON c.vehicle_code = v.vehicle_code
                 WHERE c.part_number = %s
                 ORDER BY v.manufacturer_name ASC, v.commercial_model_name ASC, v.vehicle_year DESC",
                $part_number
            ),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Vérifie si un produit est compatible avec un véhicule
     */
    public function is_compatible( $part_number, $vehicle_code ) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$this->compatibility_table} 
                 WHERE part_number = %s AND vehicle_code = %s",
                $part_number,
                $vehicle_code
            )
        );

        return $count > 0;
    }

    /**
     * Mappe les en-têtes CSV vers les colonnes connues (insensible à la casse et aux espaces)
     */
    protected function normalize_header( string $h ): string {
        return strtolower( preg_replace( '/[\s_\-]+/', '', $h ) );
    }

    /**
     * Détecte si un ensemble de headers correspond à des données véhicules ou à des liens
     * Retourne 'vehicles', 'links', ou 'unknown'
     */
    protected function detect_csv_type( array $headers ): string {
        $norm = array_map( array( $this, 'normalize_header' ), $headers );
        $norm = array_flip( $norm );

        $vehicle_keys  = array( 'vehiclecode', 'manufacturercode', 'manufacturername', 'commercialmodelname', 'vehicleyear' );
        $link_keys     = array( 'vehiclecode', 'partnumber' );

        $has_vehicle = count( array_intersect( $vehicle_keys, array_keys( $norm ) ) ) >= 3;
        $has_link    = isset( $norm['vehiclecode'] ) && isset( $norm['partnumber'] );

        if ( $has_vehicle ) return 'vehicles';
        if ( $has_link )    return 'links';
        return 'unknown';
    }

    /**
     * Retourne un tableau [field => col_index] pour les colonnes véhicules connues
     */
    protected function map_vehicle_headers( array $headers ): array {
        $aliases = array(
            'vehicle_code'          => array( 'vehiclecode', 'vehicle_code', 'vehicleid', 'id' ),
            'version_code'          => array( 'versioncode', 'version_code' ),
            'commercial_model_code' => array( 'commercialmodelcode', 'modelcode', 'commercial_model_code' ),
            'manufacturer_code'     => array( 'manufacturercode', 'manufacturer_code', 'brandcode' ),
            'vehicle_year'          => array( 'vehicleyear', 'vehicle_year', 'year', 'annee', 'modelyear' ),
            'version_name'          => array( 'versionname', 'version_name', 'version' ),
            'commercial_model_name' => array( 'commercialmodelname', 'commercial_model_name', 'modelname', 'model' ),
            'manufacturer_name'     => array( 'manufacturername', 'manufacturer_name', 'brand', 'make', 'marque' ),
            'universe_name'         => array( 'universename', 'universe_name', 'universe' ),
            'category_name'         => array( 'categoryname', 'category_name', 'category', 'categorie' ),
            'displacement_cm3'      => array( 'displacementcm3', 'displacement_cm3', 'displacement', 'cylindree', 'cc' ),
        );

        $map = array();
        $norm_map = array();
        foreach ( $headers as $i => $h ) {
            $norm_map[ $this->normalize_header( $h ) ] = $i;
        }

        foreach ( $aliases as $field => $variants ) {
            foreach ( $variants as $v ) {
                if ( isset( $norm_map[ $v ] ) ) {
                    $map[ $field ] = $norm_map[ $v ];
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Retourne un tableau [field => col_index] pour les colonnes de liens connus
     */
    protected function map_link_headers( array $headers ): array {
        $aliases = array(
            'vehicle_code'             => array( 'vehiclecode', 'vehicle_code', 'vehicleid' ),
            'part_number'              => array( 'partnumber', 'part_number', 'productcode', 'product_code', 'sku', 'reference' ),
            'barcode'                  => array( 'barcode', 'ean', 'ean13', 'gtin' ),
            'manufacturer_part_number' => array( 'manufacturerpartnumber', 'manufacturer_part_number', 'oem', 'oemreference' ),
            'position_id'              => array( 'positionid', 'position_id', 'position' ),
            'position_value'           => array( 'positionvalue', 'position_value', 'positionlabel' ),
            'attributes'               => array( 'attributes', 'attribute', 'options' ),
        );

        $map = array();
        $norm_map = array();
        foreach ( $headers as $i => $h ) {
            $norm_map[ $this->normalize_header( $h ) ] = $i;
        }

        foreach ( $aliases as $field => $variants ) {
            foreach ( $variants as $v ) {
                if ( isset( $norm_map[ $v ] ) ) {
                    $map[ $field ] = $norm_map[ $v ];
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Importe les véhicules depuis un CSV en détectant automatiquement les colonnes.
     * Remplace la table entière si $truncate = true.
     */
    public function import_vehicles_from_csv_autodetect( string $file_path, bool $truncate = false ): array {
        global $wpdb;

        $this->logger->log( "=== IMPORT VÉHICULES (auto-detect) : {$file_path} ===" );

        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return array( 'success' => false, 'message' => "Fichier introuvable : {$file_path}", 'imported' => 0, 'errors' => 0 );
        }

        $handle = fopen( $file_path, 'r' );
        if ( false === $handle ) {
            return array( 'success' => false, 'message' => 'Impossible d\'ouvrir le fichier', 'imported' => 0, 'errors' => 0 );
        }

        // Détecter le délimiteur (virgule ou point-virgule)
        $first_line = fgets( $handle );
        rewind( $handle );
        $delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

        $headers = fgetcsv( $handle, 0, $delimiter );
        if ( ! $headers ) {
            fclose( $handle );
            return array( 'success' => false, 'message' => 'Header CSV manquant', 'imported' => 0, 'errors' => 0 );
        }

        $map = $this->map_vehicle_headers( $headers );
        $this->logger->log( 'Colonnes détectées : ' . wp_json_encode( $map ) );

        if ( ! isset( $map['vehicle_code'] ) || ! isset( $map['manufacturer_name'] ) ) {
            fclose( $handle );
            return array(
                'success' => false,
                'message' => 'Colonnes requises manquantes (vehicle_code, manufacturer_name). Headers trouvés: ' . implode( ', ', $headers ),
                'imported' => 0,
                'errors'   => 0,
            );
        }

        if ( $truncate ) {
            $wpdb->query( "TRUNCATE TABLE {$this->vehicles_table}" );
            $this->logger->log( 'Table véhicules vidée avant import' );
        }

        $count  = 0;
        $errors = 0;
        $batch  = array();
        $batch_size = 500;

        while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
            if ( empty( $row ) || ( count( $row ) === 1 && trim( $row[0] ) === '' ) ) continue;

            $vehicle_code = sanitize_text_field( trim( $row[ $map['vehicle_code'] ] ?? '' ) );
            if ( empty( $vehicle_code ) ) continue;

            $batch[] = array(
                'vehicle_code'          => $vehicle_code,
                'version_code'          => sanitize_text_field( trim( $row[ $map['version_code'] ?? -1 ] ?? '' ) ),
                'commercial_model_code' => sanitize_text_field( trim( $row[ $map['commercial_model_code'] ?? -1 ] ?? '' ) ),
                'manufacturer_code'     => sanitize_text_field( trim( $row[ $map['manufacturer_code'] ?? -1 ] ?? '' ) ),
                'vehicle_year'          => absint( $row[ $map['vehicle_year'] ?? -1 ] ?? 0 ),
                'version_name'          => sanitize_text_field( trim( $row[ $map['version_name'] ?? -1 ] ?? '' ) ),
                'commercial_model_name' => sanitize_text_field( trim( $row[ $map['commercial_model_name'] ?? -1 ] ?? '' ) ),
                'manufacturer_name'     => sanitize_text_field( trim( $row[ $map['manufacturer_name'] ] ) ),
                'universe_name'         => sanitize_text_field( trim( $row[ $map['universe_name'] ?? -1 ] ?? '' ) ),
                'category_name'         => sanitize_text_field( trim( $row[ $map['category_name'] ?? -1 ] ?? '' ) ),
                'displacement_cm3'      => absint( $row[ $map['displacement_cm3'] ?? -1 ] ?? 0 ),
            );

            if ( count( $batch ) >= $batch_size ) {
                $res = $this->flush_vehicles_batch( $batch );
                $count  += $res['inserted'];
                $errors += $res['errors'];
                $batch   = array();
            }
        }
        fclose( $handle );

        if ( ! empty( $batch ) ) {
            $res    = $this->flush_vehicles_batch( $batch );
            $count  += $res['inserted'];
            $errors += $res['errors'];
        }

        $this->logger->log( "✓ Véhicules importés: {$count}, erreurs: {$errors}" );
        return array( 'success' => true, 'message' => "{$count} véhicules importés, {$errors} erreurs", 'imported' => $count, 'errors' => $errors );
    }

    /**
     * Insère un batch de véhicules en base via INSERT … ON DUPLICATE KEY UPDATE
     */
    protected function flush_vehicles_batch( array $batch ): array {
        global $wpdb;

        $values_parts = array();
        $all_values   = array();
        $table        = esc_sql( $this->vehicles_table );

        foreach ( $batch as $d ) {
            $values_parts[] = '(%s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %d)';
            array_push(
                $all_values,
                $d['vehicle_code'], $d['version_code'], $d['commercial_model_code'],
                $d['manufacturer_code'], $d['vehicle_year'], $d['version_name'],
                $d['commercial_model_name'], $d['manufacturer_name'],
                $d['universe_name'], $d['category_name'], $d['displacement_cm3']
            );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = $wpdb->prepare(
            "INSERT INTO `{$table}`
             (vehicle_code, version_code, commercial_model_code, manufacturer_code,
              vehicle_year, version_name, commercial_model_name, manufacturer_name,
              universe_name, category_name, displacement_cm3)
             VALUES " . implode( ', ', $values_parts ) . "
             ON DUPLICATE KEY UPDATE
               version_code=VALUES(version_code), commercial_model_code=VALUES(commercial_model_code),
               manufacturer_code=VALUES(manufacturer_code), vehicle_year=VALUES(vehicle_year),
               version_name=VALUES(version_name), commercial_model_name=VALUES(commercial_model_name),
               manufacturer_name=VALUES(manufacturer_name), universe_name=VALUES(universe_name),
               category_name=VALUES(category_name), displacement_cm3=VALUES(displacement_cm3)",
            $all_values
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $result = $wpdb->query( $sql );

        if ( false === $result ) {
            $this->logger->log( 'Erreur batch véhicules: ' . $wpdb->last_error );
            return array( 'inserted' => 0, 'errors' => count( $batch ) );
        }
        return array( 'inserted' => count( $batch ), 'errors' => 0 );
    }

    /**
     * Importe des liens véhicule-produit depuis un CSV en détectant automatiquement les colonnes.
     * Supporte l'import par batch (batch_start / batch_size).
     */
    public function import_links_from_csv_autodetect( string $file_path, string $source_name, int $batch_start = 0 ): array {
        global $wpdb;

        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return array(
                'success' => false, 'message' => "Fichier introuvable : {$file_path}",
                'imported' => 0, 'errors' => 0, 'total_lines' => 0,
                'processed' => 0, 'progress' => 0, 'is_complete' => true,
            );
        }

        // Cache du total de lignes
        $transient_key = 'bihr_links_total_' . md5( $file_path );
        $total_lines   = get_transient( $transient_key );
        if ( false === $total_lines ) {
            $total_lines = $this->count_csv_lines( $file_path );
            set_transient( $transient_key, $total_lines, HOUR_IN_SECONDS );
        }

        $handle = fopen( $file_path, 'r' );
        if ( false === $handle ) {
            return array(
                'success' => false, 'message' => 'Impossible d\'ouvrir le fichier',
                'imported' => 0, 'errors' => 0, 'total_lines' => $total_lines,
                'processed' => 0, 'progress' => 0, 'is_complete' => true,
            );
        }

        $first_line = fgets( $handle );
        rewind( $handle );
        $delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

        $headers = fgetcsv( $handle, 0, $delimiter );
        if ( ! $headers ) {
            fclose( $handle );
            return array(
                'success' => false, 'message' => 'Header CSV manquant',
                'imported' => 0, 'errors' => 0, 'total_lines' => $total_lines,
                'processed' => 0, 'progress' => 0, 'is_complete' => true,
            );
        }

        $map = $this->map_link_headers( $headers );

        if ( ! isset( $map['vehicle_code'] ) || ! isset( $map['part_number'] ) ) {
            fclose( $handle );
            return array(
                'success' => false,
                'message' => 'Colonnes requises manquantes (vehicle_code, part_number). Headers trouvés: ' . implode( ', ', $headers ),
                'imported' => 0, 'errors' => 0, 'total_lines' => $total_lines,
                'processed' => 0, 'progress' => 0, 'is_complete' => true,
            );
        }

        // Désactiver les index au premier batch
        if ( $batch_start === 0 ) {
            $wpdb->query( "ALTER TABLE {$this->compatibility_table} DISABLE KEYS" );
        }

        // Sauter les lignes déjà traitées
        for ( $i = 0; $i < $batch_start; $i++ ) {
            if ( false === fgetcsv( $handle, 0, $delimiter ) ) break;
        }

        $batch_size   = 50000;
        $batch        = array();
        $current_line = $batch_start;

        while ( $current_line < $batch_start + $batch_size ) {
            $row = fgetcsv( $handle, 0, $delimiter );
            if ( false === $row ) break;

            $vehicle_code = trim( $row[ $map['vehicle_code'] ] ?? '' );
            $part_number  = trim( $row[ $map['part_number'] ] ?? '' );
            if ( empty( $vehicle_code ) || empty( $part_number ) ) {
                $current_line++;
                continue;
            }

            $batch[] = array(
                'vehicle_code'             => sanitize_text_field( $vehicle_code ),
                'part_number'              => sanitize_text_field( $part_number ),
                'barcode'                  => sanitize_text_field( trim( $row[ $map['barcode'] ?? -1 ] ?? '' ) ),
                'manufacturer_part_number' => sanitize_text_field( trim( $row[ $map['manufacturer_part_number'] ?? -1 ] ?? '' ) ),
                'position_id'              => sanitize_text_field( trim( $row[ $map['position_id'] ?? -1 ] ?? '' ) ),
                'position_value'           => sanitize_text_field( trim( $row[ $map['position_value'] ?? -1 ] ?? '' ) ),
                'attributes'               => sanitize_textarea_field( trim( $row[ $map['attributes'] ?? -1 ] ?? '' ) ),
                'source_brand'             => $source_name,
            );
            $current_line++;
        }
        fclose( $handle );

        $count  = 0;
        $errors = 0;

        if ( ! empty( $batch ) ) {
            $table        = esc_sql( $this->compatibility_table );
            $values_parts = array();
            $all_values   = array();

            foreach ( $batch as $d ) {
                $values_parts[] = '(%s, %s, %s, %s, %s, %s, %s, %s)';
                array_push(
                    $all_values,
                    $d['vehicle_code'], $d['part_number'], $d['barcode'],
                    $d['manufacturer_part_number'], $d['position_id'],
                    $d['position_value'], $d['attributes'], $d['source_brand']
                );
            }

            $wpdb->query( 'START TRANSACTION' );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare(
                "INSERT INTO `{$table}`
                 (vehicle_code, part_number, barcode, manufacturer_part_number,
                  position_id, position_value, attributes, source_brand)
                 VALUES " . implode( ', ', $values_parts ),
                $all_values
            );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $result = $wpdb->query( $sql );

            if ( false !== $result ) {
                $count = $result;
                $wpdb->query( 'COMMIT' );
            } else {
                $wpdb->query( 'ROLLBACK' );
                $errors = count( $batch );
                $this->logger->log( 'Erreur liens batch: ' . $wpdb->last_error );
            }
        }

        $processed   = $current_line;
        $progress    = $total_lines > 0 ? min( 100, round( ( $processed / $total_lines ) * 100 ) ) : 100;
        $is_complete = $processed >= $total_lines || count( $batch ) < $batch_size;

        if ( $is_complete ) {
            delete_transient( $transient_key );
            $wpdb->query( "ALTER TABLE {$this->compatibility_table} ENABLE KEYS" );
            wp_cache_flush();
        }

        return array(
            'success'     => true,
            'imported'    => $count,
            'errors'      => $errors,
            'total_lines' => $total_lines,
            'processed'   => $processed,
            'progress'    => $progress,
            'is_complete' => $is_complete,
            'next_batch'  => $is_complete ? 0 : $processed,
        );
    }

    /**
     * Scanne un dossier et retourne tous les fichiers CSV trouvés avec leur type détecté.
     */
    public function scan_catalog_folder( string $folder ): array {
        $result = array();
        if ( ! is_dir( $folder ) ) return $result;

        $files = glob( rtrim( $folder, '/\\' ) . '/*.csv' );
        if ( empty( $files ) ) {
            $files = glob( rtrim( $folder, '/\\' ) . '/*.CSV' );
        }
        if ( empty( $files ) ) return $result;

        foreach ( $files as $file ) {
            $handle = fopen( $file, 'r' );
            if ( ! $handle ) continue;

            $first  = fgets( $handle );
            rewind( $handle );
            $delim  = ( substr_count( $first, ';' ) > substr_count( $first, ',' ) ) ? ';' : ',';
            $header = fgetcsv( $handle, 0, $delim );
            fclose( $handle );

            $type = $header ? $this->detect_csv_type( $header ) : 'unknown';
            $result[] = array(
                'path'    => $file,
                'name'    => basename( $file ),
                'type'    => $type,
                'headers' => $header ?: array(),
            );
        }

        return $result;
    }

    /**
     * Point d'entrée : import depuis la nouvelle structure 3 dossiers BIHR.
     * $folders = [ 'extended' => '/path/', 'hardpart' => '/path/', 'ridergear' => '/path/' ]
     * ou un seul dossier contenant tout.
     */
    public function import_from_folder_structure( array $folders, bool $truncate_vehicles = true ): array {
        $vehicles_total = 0;
        $links_total    = 0;
        $report         = array();
        $all_dirs       = array_filter( $folders, 'is_dir' );

        if ( empty( $all_dirs ) ) {
            return array( 'success' => false, 'message' => 'Aucun dossier valide trouvé', 'report' => array() );
        }

        if ( $truncate_vehicles ) {
            global $wpdb;
            $wpdb->query( "TRUNCATE TABLE {$this->vehicles_table}" );
            $this->logger->log( 'Table véhicules vidée (import nouveau format)' );
        }

        foreach ( $all_dirs as $label => $dir ) {
            $files = $this->scan_catalog_folder( $dir );
            foreach ( $files as $f ) {
                if ( $f['type'] === 'vehicles' ) {
                    $res = $this->import_vehicles_from_csv_autodetect( $f['path'], false );
                    $vehicles_total += $res['imported'];
                    $report[]        = array( 'file' => $f['name'], 'type' => 'vehicles', 'imported' => $res['imported'], 'errors' => $res['errors'] );
                } elseif ( $f['type'] === 'links' ) {
                    $res = $this->import_links_from_csv_autodetect( $f['path'], $label );
                    $links_total += $res['imported'];
                    $report[]     = array( 'file' => $f['name'], 'type' => 'links', 'imported' => $res['imported'], 'errors' => $res['errors'] );
                } else {
                    $report[] = array( 'file' => $f['name'], 'type' => 'unknown', 'headers' => implode( ', ', array_slice( $f['headers'], 0, 5 ) ) );
                }
            }
        }

        return array(
            'success'  => true,
            'vehicles' => $vehicles_total,
            'links'    => $links_total,
            'report'   => $report,
        );
    }

    /**
     * Obtient des statistiques sur les compatibilités
     */
    public function get_statistics() {
        global $wpdb;

        $stats = array();

        // Nombre total de véhicules
        $stats['total_vehicles'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->vehicles_table}" );

        // Nombre total de compatibilités
        $stats['total_compatibilities'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->compatibility_table}" );

        // Nombre de produits avec compatibilités
        $stats['products_with_compatibility'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT part_number) FROM {$this->compatibility_table}"
        );

        // Marques sources
        $stats['source_brands'] = $wpdb->get_results(
            "SELECT source_brand, COUNT(*) as count 
             FROM {$this->compatibility_table} 
             GROUP BY source_brand 
             ORDER BY count DESC",
            ARRAY_A
        );

        // Fabricants de véhicules
        $stats['manufacturers'] = $wpdb->get_results(
            "SELECT manufacturer_name, COUNT(*) as count 
             FROM {$this->vehicles_table} 
             GROUP BY manufacturer_name 
             ORDER BY count DESC 
             LIMIT 10",
            ARRAY_A
        );

        return $stats;
    }
}
