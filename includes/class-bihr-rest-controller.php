<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_REST_Controller {

    protected $namespace = 'bihr/v1';
    protected $logger;

    public function __construct() {
        $this->logger = new BihrWI_Logger();
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        // === Produits ===
        register_rest_route( $this->namespace, '/products', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_products' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
            'args'                => array(
                'page'     => array( 'type' => 'integer', 'default' => 1 ),
                'per_page' => array( 'type' => 'integer', 'default' => 50, 'maximum' => 200 ),
                'search'   => array( 'type' => 'string' ),
                'cat_l1'   => array( 'type' => 'string' ),
                'cat_l2'   => array( 'type' => 'string' ),
                'cat_l3'   => array( 'type' => 'string' ),
            ),
        ) );

        register_rest_route( $this->namespace, '/products/(?P<code>[a-zA-Z0-9-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_product' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        register_rest_route( $this->namespace, '/products/import', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_product' ),
            'permission_callback' => array( $this, 'check_woocommerce_permission' ),
            'args'                => array(
                'product_code' => array( 'type' => 'string', 'required' => true ),
            ),
        ) );

        register_rest_route( $this->namespace, '/products/import-batch', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'import_products_batch' ),
            'permission_callback' => array( $this, 'check_woocommerce_permission' ),
            'args'                => array(
                'ids' => array(
                    'type'  => 'array',
                    'items' => array( 'type' => 'integer' ),
                ),
            ),
        ) );

        // === Stock ===
        register_rest_route( $this->namespace, '/stock/(?P<code>[a-zA-Z0-9-]+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_stock' ),
            'permission_callback' => array( $this, 'check_woocommerce_permission' ),
        ) );

        register_rest_route( $this->namespace, '/stock/sync', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'sync_stock' ),
            'permission_callback' => array( $this, 'check_woocommerce_permission' ),
        ) );

        // === Commandes ===
        register_rest_route( $this->namespace, '/order/(?P<order_id>\d+)/status', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_order_sync_status' ),
            'permission_callback' => array( $this, 'check_woocommerce_permission' ),
        ) );

        register_rest_route( $this->namespace, '/order/(?P<order_id>\d+)/sync', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'sync_order' ),
            'permission_callback' => array( $this, 'check_woocommerce_permission' ),
        ) );

        // === Véhicules ===
        register_rest_route( $this->namespace, '/vehicles/manufacturers', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_manufacturers' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/vehicles/models', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_models' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'manufacturer_code' => array( 'type' => 'string', 'required' => true ),
            ),
        ) );

        register_rest_route( $this->namespace, '/vehicles/years', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_years' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'manufacturer_code' => array( 'type' => 'string', 'required' => true ),
                'model_code'        => array( 'type' => 'string', 'required' => true ),
            ),
        ) );

        register_rest_route( $this->namespace, '/vehicles/compatible-products', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_compatible_products' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'vehicle_code' => array( 'type' => 'string', 'required' => true ),
            ),
        ) );

        // === Catégories ===
        register_rest_route( $this->namespace, '/categories', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_categories' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( $this->namespace, '/categories/translate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'translate_categories' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        // === Catalog ===
        register_rest_route( $this->namespace, '/catalog/download', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'download_catalog' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
            'args'                => array(
                'type' => array(
                    'type'    => 'string',
                    'enum'    => array( 'References', 'Prices', 'Images', 'Attributes', 'Stocks' ),
                    'default' => 'References',
                ),
            ),
        ) );

        register_rest_route( $this->namespace, '/catalog/merge', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'merge_catalogs' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
        ) );

        // === Images ===
        register_rest_route( $this->namespace, '/images/pending', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'count_pending_images' ),
            'permission_callback' => array( $this, 'check_woocommerce_permission' ),
        ) );

        register_rest_route( $this->namespace, '/images/download', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'download_images' ),
            'permission_callback' => array( $this, 'check_woocommerce_permission' ),
        ) );
    }

    public function check_admin_permission(): bool {
        return current_user_can( 'manage_options' );
    }

    public function check_woocommerce_permission(): bool {
        return current_user_can( 'manage_woocommerce' );
    }

    protected function get_sync(): BihrWI_Product_Sync {
        return new BihrWI_Product_Sync( $this->logger );
    }

    protected function get_vehicle_compat(): BihrWI_Vehicle_Compatibility {
        return new BihrWI_Vehicle_Compatibility( $this->logger );
    }

    protected function get_api_client(): BihrWI_API_Client {
        return new BihrWI_API_Client( $this->logger );
    }

    protected function rest_success( $data = null, int $status = 200 ): WP_REST_Response {
        return new WP_REST_Response( $data, $status );
    }

    protected function rest_error( string $message, int $status = 400 ): WP_Error {
        return new WP_Error( 'bihr_error', $message, array( 'status' => $status ) );
    }

    // === Handlers ===

    public function get_products( WP_REST_Request $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bihr_products';
        $page  = $request->get_param( 'page' );
        $limit = $request->get_param( 'per_page' );
        $offset = ( $page - 1 ) * $limit;

        $where = array( '1=1' );
        $args  = array();

        if ( $search = $request->get_param( 'search' ) ) {
            $where[] = '(name LIKE %s OR product_code LIKE %s OR new_part_number LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $search ) . '%';
            $args    = array_merge( $args, array( $like, $like, $like ) );
        }
        foreach ( array( 'cat_l1', 'cat_l2', 'cat_l3' ) as $cat ) {
            if ( $val = $request->get_param( $cat ) ) {
                $where[] = "$cat = %s";
                $args[]  = $val;
            }
        }

        $where_sql = implode( ' AND ', $where );

        $total = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM `$table` WHERE $where_sql", ...$args )
        );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `$table` WHERE $where_sql ORDER BY id ASC LIMIT %d OFFSET %d",
                ...array_merge( $args, array( $limit, $offset ) )
            ),
            ARRAY_A
        );

        return $this->rest_success( array(
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $limit,
            'total_pages' => (int) ceil( $total / $limit ),
            'items'       => $rows ?: array(),
        ) );
    }

    public function get_product( WP_REST_Request $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bihr_products';
        $code  = $request->get_param( 'code' );

        $product = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM `$table` WHERE product_code = %s LIMIT 1", $code ),
            ARRAY_A
        );

        if ( ! $product ) {
            return $this->rest_error( __( 'Produit non trouvé.', 'bihr-synch' ), 404 );
        }

        return $this->rest_success( $product );
    }

    public function import_product( WP_REST_Request $request ) {
        $code = $request->get_param( 'product_code' );
        $sync = $this->get_sync();

        try {
            global $wpdb;
            $product_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}bihr_products WHERE product_code = %s LIMIT 1",
                    $code
                )
            );
            if ( ! $product_id ) {
                return $this->rest_error(
                    sprintf( __( 'Produit %s introuvable dans le catalogue.', 'bihr-synch' ), $code ),
                    404
                );
            }
            $wc_id = $sync->import_to_woocommerce( $product_id );
            if ( $wc_id ) {
                return $this->rest_success( array(
                    'product_id' => $wc_id,
                    'message'    => sprintf(
                        __( 'Produit %s importé avec succès (WC #%d).', 'bihr-synch' ),
                        $code,
                        $wc_id
                    ),
                ) );
            }
            return $this->rest_error(
                sprintf( __( 'Échec de l\'import du produit %s.', 'bihr-synch' ), $code ),
                500
            );
        } catch ( Exception $e ) {
            return $this->rest_error( $e->getMessage(), 500 );
        }
    }

    public function import_products_batch( WP_REST_Request $request ) {
        $ids  = $request->get_param( 'ids' );
        $sync = $this->get_sync();

        $results = array( 'success' => 0, 'errors' => array() );

        foreach ( $ids as $id ) {
            try {
                $wc_id = $sync->import_to_woocommerce( (int) $id );
                if ( $wc_id ) {
                    $results['success']++;
                } else {
                    $results['errors'][] = array( 'id' => $id, 'error' => 'Échec import' );
                }
            } catch ( Exception $e ) {
                $results['errors'][] = array( 'id' => $id, 'error' => $e->getMessage() );
            }
        }

        return $this->rest_success( $results );
    }

    public function get_stock( WP_REST_Request $request ) {
        $code  = $request->get_param( 'code' );
        $api   = $this->get_api_client();

        $stock = $api->get_real_time_stock( $code );
        if ( false === $stock ) {
            return $this->rest_error( __( 'Impossible de récupérer le stock.', 'bihr-synch' ), 500 );
        }

        return $this->rest_success( $stock );
    }

    public function sync_stock( WP_REST_Request $request ) {
        $logger = $this->logger;
        $api    = $this->get_api_client();

        global $wpdb;
        $products = $wpdb->get_results(
            "SELECT p.ID, pm.meta_value as product_code
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_bihr_product_code'
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
            LIMIT 50",
            ARRAY_A
        );

        $updated = 0;
        foreach ( $products as $product ) {
            if ( empty( $product['product_code'] ) ) {
                continue;
            }
            $stock = $api->get_real_time_stock( $product['product_code'] );
            if ( false !== $stock && isset( $stock['stock_level'] ) ) {
                wc_update_product_stock( $product['ID'], (int) $stock['stock_level'] );
                $updated++;
            }
        }

        return $this->rest_success( array(
            'processed' => count( $products ),
            'updated'   => $updated,
        ) );
    }

    public function get_order_sync_status( WP_REST_Request $request ) {
        $order_id = (int) $request->get_param( 'order_id' );

        return $this->rest_success( array(
            'synced'      => (bool) get_post_meta( $order_id, '_bihr_order_synced', true ),
            'bihr_id'     => get_post_meta( $order_id, '_bihr_order_id', true ),
            'bihr_url'    => get_post_meta( $order_id, '_bihr_order_url', true ),
            'sync_date'   => get_post_meta( $order_id, '_bihr_sync_date', true ),
            'sync_failed' => (bool) get_post_meta( $order_id, '_bihr_order_sync_failed', true ),
            'error'       => get_post_meta( $order_id, '_bihr_sync_error', true ),
        ) );
    }

    public function sync_order( WP_REST_Request $request ) {
        $order_id = (int) $request->get_param( 'order_id' );
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            return $this->rest_error( __( 'Commande introuvable.', 'bihr-synch' ), 404 );
        }

        $api    = $this->get_api_client();
        $order_sync = new BihrWI_Order_Sync( $this->logger, $api );

        ob_start();
        $order_sync->sync_order_to_bihr( $order_id, array(), $order, 'REST-' . $order_id . '-' . time() );
        ob_end_clean();

        if ( get_post_meta( $order_id, '_bihr_order_sync_failed', true ) ) {
            return $this->rest_error(
                get_post_meta( $order_id, '_bihr_sync_error', true ) ?: __( 'Échec de la synchronisation.', 'bihr-synch' ),
                500
            );
        }

        return $this->rest_success( array(
            'synced'   => true,
            'bihr_id'  => get_post_meta( $order_id, '_bihr_order_id', true ),
            'bihr_url' => get_post_meta( $order_id, '_bihr_order_url', true ),
        ) );
    }

    public function get_manufacturers( WP_REST_Request $request ) {
        $vc = $this->get_vehicle_compat();
        $data = $vc->get_manufacturers();
        if ( empty( $data ) ) {
            return $this->rest_success( array() );
        }
        return $this->rest_success( $data );
    }

    public function get_models( WP_REST_Request $request ) {
        $vc    = $this->get_vehicle_compat();
        $manuf = $request->get_param( 'manufacturer_code' );
        $data  = $vc->get_models_by_manufacturer( $manuf );
        return $this->rest_success( $data ?: array() );
    }

    public function get_years( WP_REST_Request $request ) {
        global $wpdb;
        $manuf = $request->get_param( 'manufacturer_code' );
        $model = $request->get_param( 'model_code' );
        $table = $wpdb->prefix . 'bihr_vehicles';

        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT vehicle_year, version_name, vehicle_code
                 FROM {$table}
                 WHERE manufacturer_code = %s
                 AND commercial_model_code = %s
                 AND vehicle_year IS NOT NULL
                 AND vehicle_year != ''
                 ORDER BY vehicle_year DESC",
                $manuf,
                $model
            ),
            ARRAY_A
        );
        return $this->rest_success( $data ?: array() );
    }

    public function get_compatible_products( WP_REST_Request $request ) {
        $vc          = $this->get_vehicle_compat();
        $vehicle_code = $request->get_param( 'vehicle_code' );
        $data         = $vc->get_compatible_products( $vehicle_code );
        return $this->rest_success( $data ?: array() );
    }

    public function get_categories( WP_REST_Request $request ) {
        global $wpdb;
        $table = $wpdb->prefix . 'bihr_products';

        $l1 = $wpdb->get_col( "SELECT DISTINCT cat_l1 FROM `$table` WHERE cat_l1 IS NOT NULL AND cat_l1 != '' ORDER BY cat_l1" );

        $result = array();
        foreach ( $l1 as $c1 ) {
            $l2 = $wpdb->get_col(
                $wpdb->prepare( "SELECT DISTINCT cat_l2 FROM `$table` WHERE cat_l1 = %s AND cat_l2 IS NOT NULL AND cat_l2 != '' ORDER BY cat_l2", $c1 )
            );
            $children = array();
            foreach ( $l2 as $c2 ) {
                $l3 = $wpdb->get_col(
                    $wpdb->prepare( "SELECT DISTINCT cat_l3 FROM `$table` WHERE cat_l1 = %s AND cat_l2 = %s AND cat_l3 IS NOT NULL AND cat_l3 != '' ORDER BY cat_l3", $c1, $c2 )
                );
                $children[] = array(
                    'name'     => $c2,
                    'children' => array_map( function( $c ) { return array( 'name' => $c ); }, $l3 ),
                );
            }
            $result[] = array(
                'name'     => $c1,
                'children' => $children,
            );
        }

        return $this->rest_success( $result );
    }

    public function translate_categories( WP_REST_Request $request ) {
        $translator = new BihrWI_Category_Translator( $this->logger );
        try {
            $result = $translator->analyze_and_translate();
            return $this->rest_success( array(
                'message' => __( 'Traduction des catégories terminée.', 'bihr-synch' ),
                'result'  => $result,
            ) );
        } catch ( Exception $e ) {
            return $this->rest_error( $e->getMessage(), 500 );
        }
    }

    public function download_catalog( WP_REST_Request $request ) {
        $type = $request->get_param( 'type' );
        $api  = $this->get_api_client();

        try {
            $ticket_id = $api->start_catalog_generation( $type . '/Full' );
            return $this->rest_success( array(
                'ticket_id' => $ticket_id,
                'message'   => sprintf(
                    __( 'Génération du catalog %s démarrée (ticket #%s).', 'bihr-synch' ),
                    $type,
                    $ticket_id
                ),
            ) );
        } catch ( Exception $e ) {
            return $this->rest_error( $e->getMessage(), 500 );
        }
    }

    public function merge_catalogs( WP_REST_Request $request ) {
        $sync = $this->get_sync();
        try {
            $total = $sync->merge_catalogs_from_directory();
            return $this->rest_success( array(
                'imported' => $total,
                'message'  => sprintf( __( '%d produits consolidés.', 'bihr-synch' ), $total ),
            ) );
        } catch ( Exception $e ) {
            return $this->rest_error( $e->getMessage(), 500 );
        }
    }

    public function count_pending_images( WP_REST_Request $request ) {
        global $wpdb;
        $count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
             WHERE meta_key = '_bihr_pending_image_url'"
        );
        return $this->rest_success( array( 'pending' => $count ) );
    }

    public function download_images( WP_REST_Request $request ) {
        $sync = $this->get_sync();
        try {
            $remaining = $sync->download_pending_images_parallel( 50, 5 );
            return $this->rest_success( array(
                'remaining' => $remaining,
                'message'   => sprintf( __( 'Images téléchargées (restant: %d).', 'bihr-synch' ), $remaining ),
            ) );
        } catch ( Exception $e ) {
            return $this->rest_error( $e->getMessage(), 500 );
        }
    }
}
