<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Schema {

    const OPTION_KEY = 'bihrwi_db_version';
    const DB_VERSION = '2.0.0';

    public static function init() {
        $current_version = get_option( self::OPTION_KEY, '0' );
        if ( version_compare( $current_version, self::DB_VERSION, '<' ) ) {
            self::run_migrations( $current_version );
            update_option( self::OPTION_KEY, self::DB_VERSION );
        }
    }

    private static function run_migrations( string $from_version ) {
        if ( version_compare( $from_version, '2.0.0', '<' ) ) {
            self::migrate_200();
        }
    }

    private static function migrate_200() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Table produits (v2)
        $table_products = $wpdb->prefix . 'bihr_products';
        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_code VARCHAR(100) NOT NULL,
            new_part_number VARCHAR(100) NULL,
            name TEXT NULL,
            description LONGTEXT NULL,
            image_url TEXT NULL,
            dealer_price_ht DECIMAL(15,4) NULL,
            stock_level INT NULL,
            stock_description TEXT NULL,
            category VARCHAR(255) NULL,
            cat_l1 VARCHAR(255) NULL,
            cat_l2 VARCHAR(255) NULL,
            cat_l3 VARCHAR(255) NULL,
            product_id BIGINT(20) UNSIGNED NULL,
            image_processed VARCHAR(20) NULL DEFAULT '0',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY product_code (product_code),
            KEY idx_cat_l1 (cat_l1),
            KEY idx_cat_l2 (cat_l2),
            KEY idx_cat_l3 (cat_l3),
            KEY idx_product_id (product_id),
            KEY idx_new_part_number (new_part_number)
        ) $charset_collate;";
        dbDelta( $sql_products );

        // Table file d'attente d'import
        $queue_table = $wpdb->prefix . 'bihr_import_queue';
        $sql_queue = "CREATE TABLE IF NOT EXISTS $queue_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            bihr_id BIGINT UNSIGNED NOT NULL,
            status ENUM('pending','done','error') NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status)
        ) $charset_collate;";
        dbDelta( $sql_queue );

        // Vérifier les colonnes manquantes et les ajouter
        $columns = $wpdb->get_col( "DESCRIBE $table_products" );
        $updates = array(
            'product_id'      => "ALTER TABLE $table_products ADD COLUMN product_id BIGINT(20) UNSIGNED NULL AFTER cat_l3",
            'image_processed' => "ALTER TABLE $table_products ADD COLUMN image_processed VARCHAR(20) NULL DEFAULT '0' AFTER product_id",
            'created_at'      => "ALTER TABLE $table_products ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER image_processed",
            'updated_at'      => "ALTER TABLE $table_products ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        );

        foreach ( $updates as $column => $sql ) {
            if ( ! in_array( $column, $columns, true ) ) {
                $wpdb->query( $sql );
            }
        }

        // Table véhicules
        $vc = new BihrWI_Vehicle_Compatibility( new BihrWI_Logger() );
        $vc->create_tables();
    }

    public static function get_table_name( string $name ): string {
        global $wpdb;
        return $wpdb->prefix . $name;
    }
}
