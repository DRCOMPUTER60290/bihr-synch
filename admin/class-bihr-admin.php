<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Admin {

    protected $logger;
    protected $api_client;
    protected $product_sync;

    public function __construct() {
        $this->logger       = new BihrWI_Logger();
        $this->api_client   = new BihrWI_API_Client( $this->logger );
        $this->product_sync = new BihrWI_Product_Sync( $this->logger );

        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Handlers des formulaires
        add_action( 'admin_post_bihrwi_authenticate', array( $this, 'handle_authenticate' ) );
        add_action( 'admin_post_bihrwi_clear_logs', array( $this, 'handle_clear_logs' ) );
        add_action( 'admin_post_bihrwi_start_prices_generation', array( $this, 'handle_start_prices_generation' ) );
        add_action( 'admin_post_bihrwi_import_product', array( $this, 'handle_import_product' ) );
        add_action( 'admin_post_bihrwi_merge_catalogs', array( $this, 'handle_merge_catalogs' ) );
		add_action( 'admin_post_bihrwi_check_prices_now', array( $this, 'handle_check_prices_now' ) );
		add_action( 'admin_post_bihrwi_reset_data', array( $this, 'handle_reset_data' ) );
		add_action( 'admin_post_bihrwi_download_all_catalogs', array( $this, 'handle_download_all_catalogs' ) );
        add_action( 'admin_post_bihrwi_import_vehicles', array( $this, 'handle_import_vehicles' ) );
        add_action( 'admin_post_bihrwi_import_compatibility', array( $this, 'handle_import_compatibility' ) );
        add_action( 'admin_post_bihrwi_import_all_compatibility', array( $this, 'handle_import_all_compatibility' ) );

        // Handlers AJAX
        add_action( 'wp_ajax_bihrwi_download_all_catalogs_ajax', array( $this, 'ajax_download_all_catalogs' ) );
        add_action( 'wp_ajax_bihrwi_merge_catalogs_ajax', array( $this, 'ajax_merge_catalogs' ) );
        add_action( 'wp_ajax_bihrwi_import_single_product', array( $this, 'ajax_import_single_product' ) );
        add_action( 'wp_ajax_bihr_refresh_stock', array( $this, 'ajax_refresh_stock' ) );
        add_action( 'wp_ajax_bihrwi_import_vehicles', array( $this, 'ajax_import_vehicles' ) );
        add_action( 'wp_ajax_bihrwi_import_compatibility', array( $this, 'ajax_import_compatibility' ) );
        add_action( 'wp_ajax_bihrwi_import_all_compatibility', array( $this, 'ajax_import_all_compatibility' ) );
        add_action( 'wp_ajax_bihrwi_create_compatibility_tables', array( $this, 'ajax_create_compatibility_tables' ) );
        add_action( 'wp_ajax_bihrwi_import_vehicles_async', array( $this, 'ajax_import_vehicles_async' ) );
        add_action( 'wp_ajax_bihrwi_clear_compatibility', array( $this, 'ajax_clear_compatibility' ) );
        add_action( 'wp_ajax_bihrwi_upload_vehicles_zip', array( $this, 'ajax_upload_vehicles_zip' ) );
        add_action( 'wp_ajax_bihrwi_upload_links_zip', array( $this, 'ajax_upload_links_zip' ) );
        add_action( 'wp_ajax_bihrwi_get_order_data', array( $this, 'ajax_get_order_data' ) );
        add_action( 'wp_ajax_bihr_toggle_beginner_mode', array( $this, 'ajax_toggle_beginner_mode' ) );

        // Handlers pour synchronisation automatique des stocks
        add_action( 'admin_post_bihrwi_save_stock_sync_settings', array( $this, 'handle_save_stock_sync_settings' ) );
        add_action( 'wp_ajax_bihrwi_manual_stock_sync', array( $this, 'ajax_manual_stock_sync' ) );
        
        // WP-Cron hooks pour synchronisation automatique
        add_action( 'bihrwi_auto_stock_sync', array( $this, 'run_auto_stock_sync' ) );
        
        // Initialiser le WP-Cron si activé
        add_action( 'init', array( $this, 'setup_stock_sync_cron' ) );

    }
	
	/**
	 * Charge les assets CSS et JS pour l'admin
	 */
	public function enqueue_admin_assets( $hook ) {
		// Charge uniquement sur les pages du plugin
		if ( strpos( $hook, 'bihrwi' ) === false && strpos( $hook, 'bihr' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'bihr-admin-css',
			BIHRWI_PLUGIN_URL . 'admin/css/bihr-admin.css',
			array(),
			BIHRWI_VERSION
		);

		wp_enqueue_script(
			'bihr-progress-js',
			BIHRWI_PLUGIN_URL . 'admin/js/bihr-progress.js',
			array( 'jquery' ),
			BIHRWI_VERSION,
			true
		);

		wp_localize_script(
			'bihr-progress-js',
			'bihrProgressData',
			array(
				'nonce' => wp_create_nonce( 'bihrwi_ajax_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

    /**
     * Récupère les données détaillées d'une commande via BIHR (GET /api/v2.1/Order/Data)
     * en utilisant le TicketId stocké dans les métadonnées WooCommerce.
     */
    public function ajax_get_order_data() {
        check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $force    = ! empty( $_POST['force'] );

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Missing order_id.' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }

        $bihr_ticket_id = get_post_meta( $order_id, '_bihr_api_ticket_id', true );
        if ( empty( $bihr_ticket_id ) ) {
            wp_send_json_error( array( 'message' => 'BIHR TicketId manquant pour cette commande.' ) );
        }

        // NB: Le Swagger BIHR indique que /Order/Data attend `orderId` = order generation ticket ID.
        // On utilise donc directement le TicketId BIHR stocké sur la commande.

        // Cache simple via post_meta pour éviter de frapper l’API inutilement.
        $cached_json = get_post_meta( $order_id, '_bihr_order_data_json', true );
        $cached_at   = get_post_meta( $order_id, '_bihr_order_data_fetched_at', true );
        if ( ! $force && ! empty( $cached_json ) ) {
            $decoded = json_decode( $cached_json, true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                wp_send_json_success(
                    array(
                        'ticket_id'  => $bihr_ticket_id,
                        'fetched_at' => $cached_at,
                        'data'       => $decoded,
                        'cached'     => true,
                    )
                );
            }
        }

        $this->logger->log( "AJAX: Order/Data pour commande WC #{$order_id} (TicketId={$bihr_ticket_id})" );
        $data = $this->api_client->get_order_data( $bihr_ticket_id );
        
        if ( ! $data ) {
            wp_send_json_error( array( 'message' => 'Impossible de récupérer Order/Data côté BIHR (voir logs).', ) );
        }

        update_post_meta( $order_id, '_bihr_order_data_json', wp_json_encode( $data ) );
        update_post_meta( $order_id, '_bihr_order_data_fetched_at', current_time( 'mysql' ) );

        wp_send_json_success(
            array(
                'ticket_id'  => $bihr_ticket_id,
                'fetched_at' => current_time( 'mysql' ),
                'data'       => $data,
                'cached'     => false,
            )
        );
    }
	
	public function handle_check_prices_now() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( 'Permission denied.' );
    }

    check_admin_referer( 'bihrwi_check_prices_now_action', 'bihrwi_check_prices_now_nonce' );

    $redirect_url = add_query_arg( array( 'page' => 'bihr-products' ), admin_url( 'admin.php' ) );

    $status_data = get_option( 'bihrwi_prices_generation', array() );

    if ( empty( $status_data['ticket_id'] ) ) {
        $redirect_url = add_query_arg(
            array(
                'bihrwi_check_status' => 'noticket'
            ),
            $redirect_url
        );
        wp_safe_redirect( $redirect_url );
        exit;
    }

    try {
        $logger = new BihrWI_Logger();
        $api    = new BihrWI_API_Client( $logger );

        $ticket_id = $status_data['ticket_id'];

        // On interroge directement l’API : GenerationStatus
        $status_response = $api->get_catalog_status( $ticket_id );
        $status          = strtoupper( $status_response['status'] ?? '' );

        // Mise à jour du dernier statut affichable
        $status_data['last_status']  = $status;
        $status_data['last_checked'] = current_time( 'mysql' );
        update_option( 'bihrwi_prices_generation', $status_data );

        if ( $status === 'PROCESSING' ) {
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_check_status' => 'processing',
                ),
                $redirect_url
            );
        }

        elseif ( $status === 'ERROR' ) {
            $error_msg = $status_response['error'] ?? 'Erreur inconnue.';
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_check_status' => 'error',
                    'bihrwi_msg'          => urlencode( $error_msg ),
                ),
                $redirect_url
            );
        }

        elseif ( $status === 'DONE' && ! empty( $status_response['downloadId'] ) ) {
            $download_id = $status_response['downloadId'];

            $file_path = $api->download_catalog_file( $download_id, 'prices' );

            if ( $file_path ) {
                // Fichier téléchargé avec succès
                $logger->log( "Catalogue Prices téléchargé: {$file_path}" );
                delete_option( 'bihrwi_prices_generation' );

                // Lancer automatiquement la fusion des catalogues
                $logger->log( "Démarrage automatique de la fusion des catalogues..." );
                
                try {
                    $product_sync = new BihrWI_Product_Sync( $logger );
                    $total_products = $product_sync->merge_catalogs_from_directory();
                    
                    if ( $total_products > 0 ) {
                        $logger->log( "Fusion automatique réussie: {$total_products} produits" );
                        
                        $redirect_url = add_query_arg(
                            array(
                                'bihrwi_check_status' => 'done_and_merged',
                                'bihrwi_file'         => urlencode( $file_path ),
                                'total_products'      => $total_products,
                            ),
                            $redirect_url
                        );
                    } else {
                        $logger->log( "Échec de la fusion automatique" );
                        
                        $redirect_url = add_query_arg(
                            array(
                                'bihrwi_check_status' => 'done_merge_failed',
                                'bihrwi_file'         => urlencode( $file_path ),
                            ),
                            $redirect_url
                        );
                    }
                } catch ( Exception $merge_error ) {
                    $logger->log( "Exception lors de la fusion: " . $merge_error->getMessage() );
                    
                    $redirect_url = add_query_arg(
                        array(
                            'bihrwi_check_status' => 'done',
                            'bihrwi_file'         => urlencode( $file_path ),
                            'merge_error'         => urlencode( $merge_error->getMessage() ),
                        ),
                        $redirect_url
                    );
                }
            } else {
                $redirect_url = add_query_arg(
                    array(
                        'bihrwi_check_status' => 'downloadfail',
                    ),
                    $redirect_url
                );
            }
        }

    } catch ( Exception $e ) {
        $redirect_url = add_query_arg(
            array(
                'bihrwi_check_status' => 'exception',
                'bihrwi_msg'          => urlencode( $e->getMessage() ),
            ),
            $redirect_url
        );
    }

    wp_safe_redirect( $redirect_url );
    exit;
}

	

    public function register_menus() {
        // Menu principal avec dashboard
        add_menu_page(
            __( 'BIHR WooCommerce', 'bihr-woocommerce-importer' ),
            __( 'BIHR', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-dashboard',
            array( $this, 'render_dashboard_page' ),
            'dashicons-cart',
            56
        );

        // Dashboard (page d'accueil)
        add_submenu_page(
            'bihr-dashboard',
            __( 'Dashboard BIHR', 'bihr-woocommerce-importer' ),
            __( '🏠 Accueil', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-dashboard',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Authentification Bihr', 'bihr-woocommerce-importer' ),
            __( '🔐 Authentification', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-auth',
            array( $this, 'render_auth_page' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Logs Bihr', 'bihr-woocommerce-importer' ),
            __( '📊 Logs', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-logs',
            array( $this, 'render_logs_page' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Produits Bihr', 'bihr-woocommerce-importer' ),
            __( '📦 Produits BIHR', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-products',
            array( $this, 'render_products_page' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Paramètres Commandes', 'bihr-woocommerce-importer' ),
            __( '🛒 Commandes', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-orders',
            array( $this, 'render_orders_settings_page' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Gestion des marges', 'bihr-woocommerce-importer' ),
            __( '💰 Marges', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-margins',
            array( $this, 'render_margins_page' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Produits Importés', 'bihr-woocommerce-importer' ),
            __( '✅ Produits Importés', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-imported-products',
            array( $this, 'render_imported_products_page' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Compatibilité Véhicules', 'bihr-woocommerce-importer' ),
            __( '🚗 Compatibilité', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-compatibility',
            array( $this, 'render_compatibility_page' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Synchronisation SKU (Compatibilité)', 'bihr-woocommerce-importer' ),
            __( '🔄 Synchro SKU', 'bihr-woocommerce-importer' ),
            'manage_woocommerce',
            'bihr-sku-sync-compat',
            array( $this, 'render_sku_sync_compat_page' )
        );
    }

    // === RENDER PAGES ===

    public function render_margins_page() {
        // Traitement du formulaire de sauvegarde
        if ( isset( $_POST['bihrwi_margins_nonce'] ) && wp_verify_nonce( $_POST['bihrwi_margins_nonce'], 'bihrwi_save_margins' ) ) {
            $this->save_margin_settings();
            wp_redirect( add_query_arg( 'margin_saved', '1', admin_url( 'admin.php?page=bihrwi_margins' ) ) );
            exit;
        }

        include BIHRWI_PLUGIN_DIR . 'admin/views/margin-page.php';
    }

    private function save_margin_settings() {
        $settings = array(
            'default_margin_type'  => sanitize_text_field( $_POST['default_margin_type'] ?? 'percentage' ),
            'default_margin_value' => floatval( $_POST['default_margin_value'] ?? 0 ),
            'category_margins'     => array(),
            'price_range_margins'  => array(),
            'priority'             => sanitize_text_field( $_POST['priority'] ?? 'specific' ),
        );

        // Marges par catégorie
        if ( isset( $_POST['category_margins'] ) && is_array( $_POST['category_margins'] ) ) {
            foreach ( $_POST['category_margins'] as $key => $data ) {
                $settings['category_margins'][ $key ] = array(
                    'enabled' => isset( $data['enabled'] ),
                    'type'    => sanitize_text_field( $data['type'] ?? 'percentage' ),
                    'value'   => floatval( $data['value'] ?? 0 ),
                );
            }
        }

        // Marges par tranche de prix
        if ( isset( $_POST['price_range_margins'] ) && is_array( $_POST['price_range_margins'] ) ) {
            foreach ( $_POST['price_range_margins'] as $data ) {
                $settings['price_range_margins'][] = array(
                    'enabled' => isset( $data['enabled'] ),
                    'min'     => floatval( $data['min'] ?? 0 ),
                    'max'     => floatval( $data['max'] ?? 999999 ),
                    'type'    => sanitize_text_field( $data['type'] ?? 'percentage' ),
                    'value'   => floatval( $data['value'] ?? 0 ),
                );
            }
        }

        update_option( 'bihrwi_margin_settings', $settings );
        $this->logger->log( 'Configuration des marges mise à jour' );
    }

    public function render_dashboard_page() {
        include BIHRWI_PLUGIN_DIR . 'admin/views/dashboard-page.php';
    }

    public function render_auth_page() {
        $username   = get_option( 'bihrwi_username', '' );
        $password   = get_option( 'bihrwi_password', '' );
        $openai_key = get_option( 'bihrwi_openai_key', '' );
        $last_token = get_transient( 'bihrwi_api_token' );

        include BIHRWI_PLUGIN_DIR . 'admin/views/auth-page.php';
    }

    public function render_sku_sync_compat_page() {
        include BIHRWI_PLUGIN_DIR . 'admin/views/sku-sync-compatibility-page.php';
    }

    public function render_logs_page() {
        $log_contents = $this->logger->get_log_contents();
        include BIHRWI_PLUGIN_DIR . 'admin/views/logs-page.php';
    }

    public function render_products_page() {
        // Vérifier les permissions
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'bihr-woocommerce-importer' ) );
        }

        // Vérifier le nonce si présent (filtres appliqués)
        if ( isset( $_GET['bihrwi_filter_nonce_field'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['bihrwi_filter_nonce_field'] ) ), 'bihrwi_filter_nonce' ) ) {
                wp_die( esc_html__( 'Erreur de sécurité. Veuillez réessayer.', 'bihr-woocommerce-importer' ) );
            }
        }

        global $wpdb;

        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $per_page     = 20;

        // Récupération des filtres
        $filter_search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $filter_stock    = isset( $_GET['stock_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['stock_filter'] ) ) : '';
        $filter_price_min = isset( $_GET['price_min'] ) ? sanitize_text_field( wp_unslash( $_GET['price_min'] ) ) : '';
        $filter_price_max = isset( $_GET['price_max'] ) ? sanitize_text_field( wp_unslash( $_GET['price_max'] ) ) : '';
        $filter_category = isset( $_GET['category_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['category_filter'] ) ) : '';
        $sort_by         = isset( $_GET['sort_by'] ) ? sanitize_text_field( wp_unslash( $_GET['sort_by'] ) ) : '';

        // Calculer d'abord le total pour pouvoir borner la pagination (évite les pages vides)
        $total       = $this->product_sync->get_products_count( $filter_search, $filter_stock, $filter_price_min, $filter_price_max, $filter_category );
        $debug_count_last_query = $wpdb->last_query;
        $debug_count_last_error = $wpdb->last_error;
        $total_pages = max( 1, (int) ceil( $total / $per_page ) );

        // Si on demande une page au-delà du total, revenir à la dernière page valide
        if ( $current_page > $total_pages ) {
            $current_page = $total_pages;
        }

        $products = $this->product_sync->get_products( $current_page, $per_page, $filter_search, $filter_stock, $filter_price_min, $filter_price_max, $filter_category, $sort_by );
        $debug_products_last_query = $wpdb->last_query;
        $debug_products_last_error = $wpdb->last_error;
        
        // Récupérer la liste des catégories disponibles pour le dropdown
        $available_categories = $this->product_sync->get_distinct_categories();

        // Debug optionnel (affiché dans la vue uniquement si demandé)
        $bihrwi_debug = isset( $_GET['bihrwi_debug'] ) ? (int) $_GET['bihrwi_debug'] : 0;

        include BIHRWI_PLUGIN_DIR . 'admin/views/products-page.php';
    }

    public function render_imported_products_page() {
        include BIHRWI_PLUGIN_DIR . 'admin/views/imported-products-page.php';
    }

    public function render_compatibility_page() {
        include BIHRWI_PLUGIN_DIR . 'admin/views/compatibility-page.php';
    }

    public function render_orders_settings_page() {
        // Gestion de la sauvegarde des paramètres
        if ( isset( $_POST['bihrwi_save_order_settings'] ) ) {
            check_admin_referer( 'bihrwi_order_settings_action', 'bihrwi_order_settings_nonce' );
            
            update_option( 'bihrwi_auto_sync_orders', isset( $_POST['bihrwi_auto_sync_orders'] ) ? 1 : 0 );
            update_option( 'bihrwi_auto_checkout', isset( $_POST['bihrwi_auto_checkout'] ) ? 1 : 0 );
            update_option( 'bihrwi_weekly_free_shipping', isset( $_POST['bihrwi_weekly_free_shipping'] ) ? 1 : 0 );
            update_option( 'bihrwi_delivery_mode', sanitize_text_field( $_POST['bihrwi_delivery_mode'] ?? 'Default' ) );
            
            $success_message = 'Paramètres de commandes sauvegardés avec succès.';
        }

        // Récupération des options
        $auto_sync_orders      = get_option( 'bihrwi_auto_sync_orders', 1 );
        $auto_checkout         = get_option( 'bihrwi_auto_checkout', 1 );
        $weekly_free_shipping  = get_option( 'bihrwi_weekly_free_shipping', 1 );
        $delivery_mode         = get_option( 'bihrwi_delivery_mode', 'Default' );

        include BIHRWI_PLUGIN_DIR . 'admin/views/orders-settings-page.php';
    }

    // === HANDLERS FORM ===

    public function handle_authenticate() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied.' );
        }

        check_admin_referer( 'bihrwi_authenticate_action', 'bihrwi_authenticate_nonce' );

        $username   = isset( $_POST['bihrwi_username'] ) ? sanitize_text_field( wp_unslash( $_POST['bihrwi_username'] ) ) : '';
        $password   = isset( $_POST['bihrwi_password'] ) ? sanitize_text_field( wp_unslash( $_POST['bihrwi_password'] ) ) : '';
        $openai_key = isset( $_POST['bihrwi_openai_key'] ) ? sanitize_text_field( wp_unslash( $_POST['bihrwi_openai_key'] ) ) : '';

        update_option( 'bihrwi_username', $username );
        update_option( 'bihrwi_password', $password );
        update_option( 'bihrwi_openai_key', $openai_key );

        $redirect_dashboard = add_query_arg( array( 'page' => 'bihr-dashboard' ), admin_url( 'admin.php' ) );
        $redirect_auth      = add_query_arg( array( 'page' => 'bihr-auth' ), admin_url( 'admin.php' ) );
        $redirect_url       = $redirect_auth;

        // Test de l'authentification Bihr
        try {
            $token = $this->api_client->get_token();
            $this->logger->log( 'Auth: succès pour ' . $username );
            $redirect_url = add_query_arg( array( 'bihrwi_auth_success' => 1 ), $redirect_dashboard );
        } catch ( Exception $e ) {
            $this->logger->log( 'Auth: échec pour ' . $username . ' – ' . $e->getMessage() );
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_auth_error' => 1,
                    'bihrwi_msg'        => urlencode( $e->getMessage() ),
                ),
                $redirect_url
            );
        }

        // Test de la clé OpenAI si renseignée
        if ( ! empty( $openai_key ) ) {
            $ai_enrichment = new BihrWI_AI_Enrichment( $this->logger );
            $test_result = $this->test_openai_key( $openai_key );
            
            if ( $test_result === true ) {
                $this->logger->log( 'OpenAI: clé API valide et opérationnelle' );
                $redirect_url = add_query_arg( array( 'bihrwi_openai_success' => 1 ), $redirect_url );
            } else {
                $this->logger->log( 'OpenAI: erreur de validation - ' . $test_result );
                $redirect_url = add_query_arg(
                    array(
                        'bihrwi_openai_error' => 1,
                        'bihrwi_openai_msg'   => urlencode( $test_result ),
                    ),
                    $redirect_url
                );
            }
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Test la validité de la clé OpenAI
     * @return bool|string true si valide, message d'erreur sinon
     */
    protected function test_openai_key( $api_key ) {
        $endpoint = 'https://api.openai.com/v1/models';

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout' => 10,
        );

        $response = wp_remote_get( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return 'Erreur de connexion : ' . $response->get_error_message();
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( $status_code === 200 ) {
            return true;
        } elseif ( $status_code === 401 ) {
            return 'Clé API invalide ou expirée';
        } elseif ( $status_code === 429 ) {
            return 'Quota dépassé sur votre compte OpenAI';
        } else {
            $body = wp_remote_retrieve_body( $response );
            return 'Erreur HTTP ' . $status_code . ' : ' . substr( $body, 0, 100 );
        }
    }

    public function handle_clear_logs() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied.' );
        }

        check_admin_referer( 'bihrwi_clear_logs_action', 'bihrwi_clear_logs_nonce' );

        $this->logger->clear_logs();

        $redirect_url = add_query_arg(
            array(
                'page'            => 'bihr-logs',
                'bihrwi_cleared'  => 1,
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    public function handle_start_prices_generation() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied.' );
        }

        check_admin_referer( 'bihrwi_start_prices_action', 'bihrwi_start_prices_nonce' );

        $redirect_url = add_query_arg( array( 'page' => 'bihr-products' ), admin_url( 'admin.php' ) );

        try {
            // Démarre la génération du catalog Prices (l'API ajoutera /Full automatiquement)
            $ticket_id = $this->api_client->start_catalog_generation( 'Prices' );

            update_option(
                'bihrwi_prices_generation',
                array(
                    'ticket_id'  => $ticket_id,
                    'started_at' => current_time( 'mysql' ),
                )
            );

            $this->logger->log( 'Prices: génération démarrée (ticket_id=' . $ticket_id . ').' );

            // On force un cron dans 5 minutes
            wp_schedule_single_event( time() + 300, 'bihrwi_check_prices_catalog_event' );

            $redirect_url = add_query_arg( array( 'bihrwi_prices_started' => 1 ), $redirect_url );
        } catch ( Exception $e ) {
            $this->logger->log( 'Prices: erreur démarrage – ' . $e->getMessage() );
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_prices_error' => 1,
                    'bihrwi_msg'          => urlencode( $e->getMessage() ),
                ),
                $redirect_url
            );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    public function handle_import_product() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied.' );
        }

        check_admin_referer( 'bihrwi_import_product_action', 'bihrwi_import_product_nonce' );

        $product_id = isset( $_POST['bihrwi_product_id'] ) ? intval( $_POST['bihrwi_product_id'] ) : 0;

        $redirect_url = add_query_arg( array( 'page' => 'bihr-products' ), admin_url( 'admin.php' ) );

        if ( ! $product_id ) {
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_import_error' => 1,
                    'bihrwi_msg'          => urlencode( 'ID produit invalide.' ),
                ),
                $redirect_url
            );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        try {
            $wc_id       = $this->product_sync->import_to_woocommerce( $product_id );
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_import_success' => 1,
                    'imported_id'           => $wc_id,
                ),
                $redirect_url
            );
        } catch ( Exception $e ) {
            $this->logger->log( 'Import product: erreur – ' . $e->getMessage() );
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_import_error' => 1,
                    'bihrwi_msg'          => urlencode( $e->getMessage() ),
                ),
                $redirect_url
            );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Handler pour le bouton "Fusionner les catalogues"
     */
    public function handle_merge_catalogs() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission denied.' );
        }

        check_admin_referer( 'bihrwi_merge_catalogs_action', 'bihrwi_merge_catalogs_nonce' );

        $redirect_url = add_query_arg( array( 'page' => 'bihr-products' ), admin_url( 'admin.php' ) );

        try {
            $count = $this->product_sync->merge_catalogs_from_directory();
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_merge_success' => 1,
                    'bihrwi_merge_count'   => $count,
                ),
                $redirect_url
            );
        } catch ( Exception $e ) {
            $this->logger->log( 'Fusion catalogues: erreur – ' . $e->getMessage() );
            $redirect_url = add_query_arg(
                array(
                    'bihrwi_merge_error' => 1,
                    'bihrwi_msg'         => urlencode( $e->getMessage() ),
                ),
                $redirect_url
            );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }
	public function handle_reset_data() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( 'Permission denied.' );
    }

    check_admin_referer( 'bihrwi_reset_data_action', 'bihrwi_reset_data_nonce' );

    global $wpdb;

    // 1) On efface la table
    $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}bihr_products" );

    // 2) On efface les fichiers CSV
    $import_dir = WP_CONTENT_DIR . '/uploads/bihr-import/';
    if ( is_dir( $import_dir ) ) {
        foreach ( glob( $import_dir . '*.csv' ) as $file ) {
            @unlink( $file );
        }
        foreach ( glob( $import_dir . '*.zip' ) as $file ) {
            @unlink( $file );
        }
    }

    // 3) On supprime les options internes
    delete_option( 'bihrwi_prices_generation' );
    delete_transient( 'bihrwi_api_token' );

    // Redirection avec notification
    $redirect = add_query_arg(
        array(
            'page'                => 'bihr-products',
            'bihrwi_reset_success' => 1,
        ),
        admin_url( 'admin.php' )
    );

    wp_safe_redirect( $redirect );
    exit;
}

	/**
	 * Handler pour télécharger tous les catalogues nécessaires en une seule action
	 */
	public function handle_download_all_catalogs() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Permission denied.' );
		}

		check_admin_referer( 'bihrwi_download_all_action', 'bihrwi_download_all_nonce' );

		$redirect_url = add_query_arg( array( 'page' => 'bihr-products' ), admin_url( 'admin.php' ) );

		try {
			$this->logger->log( 'Téléchargement de tous les catalogues: démarrage' );

            // Liste des catalogues à télécharger
            $catalogs = array(
                'References'         => 'References',
                'ExtendedReferences' => 'ExtendedReferences',
                'Attributes'         => 'Attributes',
                'Images'             => 'Images',
                'Stocks'             => 'Stocks',
            );			$downloaded_files = array();

			foreach ( $catalogs as $name => $path ) {
				$this->logger->log( "Téléchargement du catalogue: {$name}" );

				// 1. Démarrer la génération
				$ticket_id = $this->api_client->start_catalog_generation( $path );
				$this->logger->log( "Ticket ID pour {$name}: {$ticket_id}" );

				// 2. Attendre que le fichier soit prêt (max 5 minutes)
				$max_attempts = 60; // 60 * 5 secondes = 5 minutes
				$attempt      = 0;
				$status       = 'PROCESSING';

				while ( $attempt < $max_attempts && $status === 'PROCESSING' ) {
					sleep( 5 );
					$status_response = $this->api_client->get_catalog_status( $ticket_id );
					$status          = strtoupper( $status_response['status'] ?? '' );
					$attempt++;

					$this->logger->log( "Status {$name} (tentative {$attempt}): {$status}" );
				}

				if ( $status === 'ERROR' ) {
					$error_msg = $status_response['error'] ?? 'Erreur inconnue';
					throw new Exception( "Erreur lors de la génération du catalogue {$name}: {$error_msg}" );
				}

				if ( $status !== 'DONE' ) {
					throw new Exception( "Timeout lors de la génération du catalogue {$name}" );
				}

				// 3. Télécharger le fichier
				$download_id = $status_response['downloadId'] ?? '';
				if ( empty( $download_id ) || $download_id === '00000000000000000000000000000000' ) {
					$this->logger->log( "Catalogue {$name} non disponible (downloadId vide ou nul), passage au suivant" );
					continue; // Passe au catalogue suivant
				}

				$zip_file = $this->api_client->download_catalog_file( $download_id, strtolower( $name ) );
				if ( ! $zip_file ) {
					$this->logger->log( "Échec téléchargement {$name}, passage au suivant" );
					continue; // Passe au catalogue suivant au lieu de planter
				}

				$downloaded_files[ $name ] = $zip_file;
				$this->logger->log( "Catalogue {$name} téléchargé: {$zip_file}" );
			}

			// 4. Extraire tous les fichiers ZIP dans le dossier d'import
			$import_dir = WP_CONTENT_DIR . '/uploads/bihr-import/';
			if ( ! is_dir( $import_dir ) ) {
				wp_mkdir_p( $import_dir );
			}

			$total_extracted = 0;
			foreach ( $downloaded_files as $name => $zip_file ) {
				$extracted = $this->product_sync->extract_zip_to_import_dir( $zip_file );
				$total_extracted += $extracted;
				$this->logger->log( "Extraction {$name}: {$extracted} fichiers" );
			}

			$catalogs_downloaded = count( $downloaded_files );
			$this->logger->log( "Téléchargement terminé: {$catalogs_downloaded} catalogues, {$total_extracted} fichiers CSV extraits" );

			$redirect_url = add_query_arg(
				array(
					'bihrwi_download_success'   => 1,
					'bihrwi_files_count'        => $total_extracted,
					'bihrwi_catalogs_count'     => $catalogs_downloaded,
				),
				$redirect_url
			);

		} catch ( Exception $e ) {
			$this->logger->log( 'Erreur téléchargement catalogues: ' . $e->getMessage() );

			$redirect_url = add_query_arg(
				array(
					'bihrwi_download_error' => 1,
					'bihrwi_msg'            => urlencode( $e->getMessage() ),
				),
				$redirect_url
			);
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handler AJAX pour le téléchargement de tous les catalogues
	 */
	public function ajax_download_all_catalogs() {
		check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		try {
			$this->logger->log( 'AJAX: Téléchargement de tous les catalogues' );

            // Liste des catalogues
            $catalogs = array(
                'References'         => 'References',
                'ExtendedReferences' => 'ExtendedReferences',
                'Attributes'         => 'Attributes',
                'Images'             => 'Images',
                'Stocks'             => 'Stocks',
            );		$downloaded_files = array();
		$failed_catalogs  = array();
		$max_retries      = 3; // Nombre de tentatives pour chaque catalogue

		// Première passe : essayer de télécharger tous les catalogues
		foreach ( $catalogs as $name => $path ) {
			$this->logger->log( "AJAX: Téléchargement du catalogue: {$name}" );

			try {
				$ticket_id       = $this->api_client->start_catalog_generation( $path );
				
				// Attendre 2 secondes avant de vérifier le statut (rate limit: 1/sec)
				sleep( 2 );
				
				$max_attempts    = 120; // 120 * 5 sec = 10 minutes max
				$attempt         = 0;

				// Vérifie immédiatement le statut
				$status_response = $this->api_client->get_catalog_status( $ticket_id );
				$status          = strtoupper( $status_response['status'] ?? '' );

				// Continue à vérifier tant que c'est en PROCESSING
				while ( $attempt < $max_attempts && $status === 'PROCESSING' ) {
					sleep( 5 );
					$status_response = $this->api_client->get_catalog_status( $ticket_id );
					$status          = strtoupper( $status_response['status'] ?? '' );
					$attempt++;
					
					// Log toutes les 6 tentatives (30 secondes)
					if ( $attempt % 6 === 0 ) {
						$elapsed = ( $attempt * 5 ) / 60;
						$this->logger->log( "AJAX: Status {$name}: {$status} (temps écoulé: " . number_format( $elapsed, 1 ) . " min)" );
					}
				}

				if ( $status === 'ERROR' ) {
					$error_msg = $status_response['error'] ?? 'Erreur inconnue';
					$this->logger->log( "AJAX: Erreur génération {$name}: {$error_msg}" );
					$failed_catalogs[ $name ] = $path;
					continue;
				}

				if ( $status === 'PROCESSING' ) {
					$this->logger->log( "AJAX: Catalogue {$name} toujours en PROCESSING après 10 minutes, ajout pour réessai" );
					$failed_catalogs[ $name ] = $path;
					continue;
				}

				if ( $status !== 'DONE' ) {
					$this->logger->log( "AJAX: Statut inattendu pour {$name}: {$status}" );
					$failed_catalogs[ $name ] = $path;
					continue;
				}

				$download_id = $status_response['downloadId'] ?? '';
				if ( empty( $download_id ) || $download_id === '00000000000000000000000000000000' ) {
					$this->logger->log( "AJAX: Catalogue {$name} non disponible (downloadId vide ou nul)" );
					continue; // Ne pas réessayer si le catalogue n'existe pas
				}

				$zip_file = $this->api_client->download_catalog_file( $download_id, strtolower( $name ) );
				if ( ! $zip_file ) {
					$this->logger->log( "AJAX: Échec téléchargement {$name}" );
					$failed_catalogs[ $name ] = $path;
					continue;
				}

				$downloaded_files[ $name ] = $zip_file;
				$this->logger->log( "AJAX: Catalogue {$name} téléchargé avec succès" );
				
				// Attendre 2 secondes avant le prochain catalogue (rate limit: 1/sec)
				sleep( 2 );

			} catch ( Exception $catalog_error ) {
				$this->logger->log( "AJAX: Exception pour {$name}: " . $catalog_error->getMessage() );
				$failed_catalogs[ $name ] = $path;
				
				// Attendre 2 secondes même en cas d'erreur pour éviter le rate limit
				sleep( 2 );
			}
		}

		// Réessayer les catalogues échoués
		$retry_count = 0;
		while ( ! empty( $failed_catalogs ) && $retry_count < $max_retries ) {
			$retry_count++;
			$this->logger->log( "AJAX: Nouvelle tentative ({$retry_count}/{$max_retries}) pour " . count( $failed_catalogs ) . " catalogue(s)" );
			
			// Attendre 15 secondes entre chaque série de réessais pour laisser l'API respirer
			if ( $retry_count > 1 ) {
				$this->logger->log( "AJAX: Pause de 15 secondes avant la nouvelle tentative..." );
				sleep( 15 );
			}
			
			$still_failed = array();

			foreach ( $failed_catalogs as $name => $path ) {
				$this->logger->log( "AJAX: Réessai du catalogue: {$name}" );

				try {
					$ticket_id       = $this->api_client->start_catalog_generation( $path );
					
					// Attendre 2 secondes avant de vérifier le statut (rate limit: 1/sec)
					sleep( 2 );
					
					$max_attempts    = 120; // 10 minutes max
					$attempt         = 0;

					$status_response = $this->api_client->get_catalog_status( $ticket_id );
					$status          = strtoupper( $status_response['status'] ?? '' );

					while ( $attempt < $max_attempts && $status === 'PROCESSING' ) {
						sleep( 5 );
						$status_response = $this->api_client->get_catalog_status( $ticket_id );
						$status          = strtoupper( $status_response['status'] ?? '' );
						$attempt++;
						
						// Log toutes les 6 tentatives (30 secondes)
						if ( $attempt % 6 === 0 ) {
							$elapsed = ( $attempt * 5 ) / 60;
							$this->logger->log( "AJAX: Réessai {$name}: {$status} (temps écoulé: " . number_format( $elapsed, 1 ) . " min)" );
						}
					}

					if ( $status === 'ERROR' ) {
						$this->logger->log( "AJAX: Erreur lors du réessai de {$name}" );
						$still_failed[ $name ] = $path;
						continue;
					}

					if ( $status === 'PROCESSING' ) {
						$this->logger->log( "AJAX: {$name} toujours en PROCESSING après 10 minutes (réessai)" );
						$still_failed[ $name ] = $path;
						continue;
					}

					if ( $status !== 'DONE' ) {
						$still_failed[ $name ] = $path;
						continue;
					}

					$download_id = $status_response['downloadId'] ?? '';
					if ( empty( $download_id ) || $download_id === '00000000000000000000000000000000' ) {
						continue; // Ne pas réessayer
					}

					$zip_file = $this->api_client->download_catalog_file( $download_id, strtolower( $name ) );
					if ( ! $zip_file ) {
						$still_failed[ $name ] = $path;
						continue;
					}

					$downloaded_files[ $name ] = $zip_file;
					$this->logger->log( "AJAX: Catalogue {$name} téléchargé avec succès (après réessai)" );
					
					// Attendre 2 secondes avant le prochain catalogue (rate limit: 1/sec)
					sleep( 2 );

				} catch ( Exception $catalog_error ) {
					$this->logger->log( "AJAX: Exception réessai {$name}: " . $catalog_error->getMessage() );
					$still_failed[ $name ] = $path;
					
					// Attendre 2 secondes même en cas d'erreur pour éviter le rate limit
					sleep( 2 );
				}
			}

			$failed_catalogs = $still_failed;
		}			// Extraction
			$import_dir = WP_CONTENT_DIR . '/uploads/bihr-import/';
			if ( ! is_dir( $import_dir ) ) {
				wp_mkdir_p( $import_dir );
			}

			$total_extracted = 0;
			foreach ( $downloaded_files as $name => $zip_file ) {
				$extracted        = $this->product_sync->extract_zip_to_import_dir( $zip_file );
				$total_extracted += $extracted;
			}

			$catalogs_downloaded = count( $downloaded_files );
			$this->logger->log( "AJAX: Téléchargement terminé - {$catalogs_downloaded} catalogues, {$total_extracted} fichiers" );

			wp_send_json_success( array( 
				'files_count'    => $total_extracted,
				'catalogs_count' => $catalogs_downloaded,
			) );

		} catch ( Exception $e ) {
			$this->logger->log( 'AJAX: Erreur - ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handler AJAX pour la fusion des catalogues
	 */
	public function ajax_merge_catalogs() {
		check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		try {
			$this->logger->log( 'AJAX: Fusion des catalogues' );

			$count = $this->product_sync->merge_catalogs_from_directory();

			$this->logger->log( "AJAX: Fusion terminée - {$count} produits" );

			wp_send_json_success( array( 'count' => $count ) );

		} catch ( Exception $e ) {
			$this->logger->log( 'AJAX: Erreur fusion - ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handler AJAX pour l'import d'un seul produit
	 */
	public function ajax_import_single_product() {
		check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => 'ID de produit invalide.' ) );
		}

		try {
			$this->logger->log( "AJAX: Import du produit ID {$product_id}" );

			$wc_id = $this->product_sync->import_to_woocommerce( $product_id );

			if ( $wc_id ) {
				$this->logger->log( "AJAX: Produit {$product_id} importé avec succès (WC ID: {$wc_id})" );
				wp_send_json_success( array( 
					'wc_id' => $wc_id,
					'message' => 'Produit importé avec succès.'
				) );
			} else {
				$this->logger->log( "AJAX: Échec de l'import du produit {$product_id}" );
				wp_send_json_error( array( 'message' => 'Échec de l\'import du produit.' ) );
			}

		} catch ( Exception $e ) {
			$this->logger->log( "AJAX: Erreur import produit {$product_id} - " . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Rafraîchir le stock en temps réel depuis l'API BIHR
	 */
	public function ajax_refresh_stock() {
		check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permissions insuffisantes.' ) );
		}

		$product_code = sanitize_text_field( $_POST['product_code'] ?? '' );
		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		if ( empty( $product_code ) ) {
			wp_send_json_error( array( 'message' => 'Code produit manquant.' ) );
		}

		try {
			$api = new BihrWI_API_Client( $this->logger );
			$stock_data = $api->get_real_time_stock( $product_code );

			if ( $stock_data === false ) {
				wp_send_json_error( array( 
					'message' => 'Impossible de récupérer le stock depuis l\'API BIHR.' 
				) );
			}

			$stock_level = $stock_data['stock_level'];

			// Si un product_id WooCommerce est fourni, mettre à jour le stock
			if ( $product_id > 0 ) {
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$product->set_stock_quantity( $stock_level );
					
					// Mise à jour du statut de stock
					if ( $stock_level > 0 ) {
						$product->set_stock_status( 'instock' );
					} else {
						$product->set_stock_status( 'outofstock' );
					}
					
					$product->save();
					
					$this->logger->log( sprintf(
						'Stock WooCommerce mis à jour pour %s (ID: %d): %d unités',
						$product_code,
						$product_id,
						$stock_level
					) );
				}
			} else {
				$this->logger->log( sprintf(
					'Stock temps réel récupéré pour %s: %d unités (pas de mise à jour WooCommerce)',
					$product_code,
					$stock_level
				) );
			}

			wp_send_json_success( array(
				'stock_level' => $stock_level,
				'product_code' => $product_code,
				'updated' => $product_id > 0
			) );

		} catch ( Exception $e ) {
			$this->logger->log( 'AJAX: Erreur refresh stock - ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Import de la liste des véhicules depuis VehiclesList.csv
	 */
	public function ajax_import_vehicles() {
		check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permission refusée' ) );
		}

		try {
			$compatibility = new BihrWI_Vehicle_Compatibility();
			$result = $compatibility->import_vehicles_list();
			
			if ( $result['success'] ) {
				wp_send_json_success( array(
					'message' => sprintf(
						'Import terminé : %d véhicules importés, %d échecs',
						$result['imported'],
						$result['errors']
					),
					'imported' => $result['imported'],
					'errors' => $result['errors']
				) );
			} else {
				wp_send_json_error( array( 'message' => $result['message'] ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Import de compatibilités pour une marque spécifique
	 */
	/**
	 * AJAX: Import de compatibilité par marque avec support de progression
	 */
	public function ajax_import_compatibility() {
		check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permission refusée' ) );
		}

		$brand = isset( $_POST['brand'] ) ? sanitize_text_field( $_POST['brand'] ) : '';
		$batch_start = isset( $_POST['batch_start'] ) ? intval( $_POST['batch_start'] ) : 0;
		
		if ( empty( $brand ) ) {
			wp_send_json_error( array( 'message' => 'Marque non spécifiée' ) );
		}

		try {
			$compatibility = new BihrWI_Vehicle_Compatibility();
			$result = $compatibility->import_brand_compatibility( $brand, null, $batch_start );
			
			if ( $result['success'] ) {
				wp_send_json_success( array(
					'message' => sprintf(
						'%s : %d compatibilités importées (batch)',
						$brand,
						$result['imported']
					),
					'imported'      => $result['imported'],
					'errors'        => $result['errors'],
					'brand'         => $brand,
					'progress'      => $result['progress'],
					'processed'     => $result['processed'],
					'total_lines'   => $result['total_lines'],
					'is_complete'   => $result['is_complete'],
					'next_batch'    => $result['next_batch'],
				) );
			} else {
				wp_send_json_error( array( 'message' => $result['message'] ?? 'Erreur d\'import' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Import de toutes les compatibilités (toutes les marques)
	 */
	/**
	 * AJAX: Import de toutes les compatibilités avec progression par batch
	 */
	public function ajax_import_all_compatibility() {
		check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permission refusée' ) );
		}

		$brand = isset( $_POST['brand'] ) ? sanitize_text_field( $_POST['brand'] ) : '';
		$batch_start = isset( $_POST['batch_start'] ) ? intval( $_POST['batch_start'] ) : 0;
		
		if ( empty( $brand ) ) {
			wp_send_json_error( array( 'message' => 'Marque non spécifiée' ) );
		}

		try {
			$compatibility = new BihrWI_Vehicle_Compatibility();
			$result = $compatibility->import_brand_compatibility( $brand, null, $batch_start );
			
			if ( $result['success'] ) {
				wp_send_json_success( array(
					'message' => sprintf(
						'%s : %d compatibilités importées (batch)',
						$brand,
						$result['imported']
					),
					'imported'      => $result['imported'],
					'errors'        => $result['errors'],
					'brand'         => $brand,
					'progress'      => $result['progress'],
					'processed'     => $result['processed'],
					'total_lines'   => $result['total_lines'],
					'is_complete'   => $result['is_complete'],
					'next_batch'    => $result['next_batch'],
				) );
			} else {
				wp_send_json_error( array( 'message' => $result['message'] ?? 'Erreur d\'import' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX: Créer/Recréer les tables de compatibilité
	 */
	public function ajax_create_compatibility_tables() {
		check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Permission refusée' ) );
		}

		try {
			$compatibility = new BihrWI_Vehicle_Compatibility();
			$compatibility->create_tables();
			
			wp_send_json_success( array(
				'message' => 'Tables créées avec succès !'
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

    /**
     * AJAX: Import véhicules (alias async)
     */
    public function ajax_import_vehicles_async() {
        $this->ajax_import_vehicles();
    }

    /**
     * AJAX: Purge des données de compatibilité
     */
    public function ajax_clear_compatibility() {
        check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        try {
            $compatibility = new BihrWI_Vehicle_Compatibility();
            $result = $compatibility->clear_data();

            wp_send_json_success( array(
                'message' => 'Tables vidées',
                'stats'   => $result,
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * AJAX: Upload VehiclesList.zip
     */
    public function ajax_upload_vehicles_zip() {
        check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        if ( empty( $_FILES['vehicles_zip'] ) || ! isset( $_FILES['vehicles_zip']['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => 'Aucun fichier reçu' ) );
        }

        try {
            $upload = wp_handle_upload( $_FILES['vehicles_zip'], array( 'test_form' => false ) );
            if ( isset( $upload['error'] ) ) {
                wp_send_json_error( array( 'message' => $upload['error'] ) );
            }

            $compatibility = new BihrWI_Vehicle_Compatibility();
            $unzip = $compatibility->unzip_to_import_dir( $upload['file'] );
			
            if ( ! $unzip['success'] ) {
                wp_send_json_error( array( 'message' => $unzip['message'] ) );
            }

            wp_send_json_success( array(
                'message' => 'Archive VehiclesList.zip importée et extraite',
                'path'    => $upload['file'],
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * AJAX: Upload LinksList.zip
     */
    public function ajax_upload_links_zip() {
        check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        if ( empty( $_FILES['links_zip'] ) || ! isset( $_FILES['links_zip']['tmp_name'] ) ) {
            wp_send_json_error( array( 'message' => 'Aucun fichier reçu' ) );
        }

        try {
            $upload = wp_handle_upload( $_FILES['links_zip'], array( 'test_form' => false ) );
            if ( isset( $upload['error'] ) ) {
                wp_send_json_error( array( 'message' => $upload['error'] ) );
            }

            $compatibility = new BihrWI_Vehicle_Compatibility();
            $unzip = $compatibility->unzip_to_import_dir( $upload['file'] );
			
            if ( ! $unzip['success'] ) {
                wp_send_json_error( array( 'message' => $unzip['message'] ) );
            }

            wp_send_json_success( array(
                'message' => 'Archive LinksList.zip importée et extraite',
                'path'    => $upload['file'],
            ) );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Handler POST: Import de la liste des véhicules
     */
    public function handle_import_vehicles() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission refusée' );
        }

        check_admin_referer( 'bihrwi_import_vehicles_action', 'bihrwi_import_vehicles_nonce' );

        $redirect_url = add_query_arg( array( 'page' => 'bihr-compatibility' ), admin_url( 'admin.php' ) );

        try {
            // Construire le chemin du fichier
            $upload_dir = wp_upload_dir();
            $import_dir = $upload_dir['basedir'] . '/bihr-import/';
            $file_path = $import_dir . 'VehiclesList.csv';

            if ( ! file_exists( $file_path ) ) {
                $redirect_url = add_query_arg( 'error', urlencode( 'Fichier VehiclesList.csv introuvable dans ' . $import_dir ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            }

            $compatibility = new BihrWI_Vehicle_Compatibility();
            $result = $compatibility->import_vehicles_list( $file_path );

            if ( $result['success'] ) {
                $redirect_url = add_query_arg( array(
                    'vehicles_imported' => $result['imported']
                ), $redirect_url );
            } else {
                $redirect_url = add_query_arg( 'error', urlencode( $result['message'] ), $redirect_url );
            }

        } catch ( Exception $e ) {
            $redirect_url = add_query_arg( 'error', urlencode( $e->getMessage() ), $redirect_url );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Handler POST: Import de compatibilités pour une marque
     */
    public function handle_import_compatibility() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission refusée' );
        }

        check_admin_referer( 'bihrwi_import_compatibility_action', 'bihrwi_import_compatibility_nonce' );

        $redirect_url = add_query_arg( array( 'page' => 'bihr-compatibility' ), admin_url( 'admin.php' ) );
        $brand = isset( $_POST['brand'] ) ? sanitize_text_field( $_POST['brand'] ) : '';

        if ( empty( $brand ) ) {
            $redirect_url = add_query_arg( 'error', urlencode( 'Marque non spécifiée' ), $redirect_url );
            wp_safe_redirect( $redirect_url );
            exit;
        }

        try {
            // Construire le chemin du fichier
            $upload_dir = wp_upload_dir();
            $import_dir = $upload_dir['basedir'] . '/bihr-import/';
            $file_path = $import_dir . '[' . $brand . '].csv';

            if ( ! file_exists( $file_path ) ) {
                $redirect_url = add_query_arg( 'error', urlencode( 'Fichier [' . $brand . '].csv introuvable' ), $redirect_url );
                wp_safe_redirect( $redirect_url );
                exit;
            }

            $compatibility = new BihrWI_Vehicle_Compatibility();
            $result = $compatibility->import_brand_compatibility( $brand, $file_path );

            if ( $result['success'] ) {
                $redirect_url = add_query_arg( array(
                    'compatibility_imported' => $result['imported'],
                    'brand' => urlencode( $brand )
                ), $redirect_url );
            } else {
                $redirect_url = add_query_arg( 'error', urlencode( $result['message'] ), $redirect_url );
            }

        } catch ( Exception $e ) {
            $redirect_url = add_query_arg( 'error', urlencode( $e->getMessage() ), $redirect_url );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Handler POST: Import de toutes les compatibilités
     */
    public function handle_import_all_compatibility() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission refusée' );
        }

        check_admin_referer( 'bihrwi_import_all_compatibility_action', 'bihrwi_import_all_compatibility_nonce' );

        $redirect_url = add_query_arg( array( 'page' => 'bihr-compatibility' ), admin_url( 'admin.php' ) );

        try {
            $upload_dir = wp_upload_dir();
            $import_dir = $upload_dir['basedir'] . '/bihr-import/';
			
            $compatibility = new BihrWI_Vehicle_Compatibility();
            $brands = array( 'SHIN YO', 'TECNIUM', 'V BIKE', 'V PARTS', 'VECTOR', 'VICMA' );
			
            $total_imported = 0;
            $total_errors = 0;

            foreach ( $brands as $brand ) {
                $file_path = $import_dir . '[' . $brand . '].csv';
				
                if ( file_exists( $file_path ) ) {
                    $result = $compatibility->import_brand_compatibility( $brand, $file_path );
					
                    if ( $result['success'] ) {
                        $total_imported += $result['imported'];
                        $total_errors += $result['errors'];
                    }
                }
            }

            $redirect_url = add_query_arg( array(
                'compatibility_imported' => $total_imported,
                'brand' => 'Toutes les marques'
            ), $redirect_url );

        } catch ( Exception $e ) {
            $redirect_url = add_query_arg( 'error', urlencode( $e->getMessage() ), $redirect_url );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /* =========================================================
     *   SYNCHRONISATION AUTOMATIQUE DES STOCKS
     * ======================================================= */

    /**
     * Initialise le WP-Cron pour la synchronisation automatique
     */
    public function setup_stock_sync_cron() {
        $settings = get_option( 'bihrwi_stock_sync_settings', array( 'enabled' => false ) );

        // Si désactivé, supprimer le cron
        if ( empty( $settings['enabled'] ) ) {
            $timestamp = wp_next_scheduled( 'bihrwi_auto_stock_sync' );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, 'bihrwi_auto_stock_sync' );
            }
            return;
        }

        // Vérifier si le cron est déjà planifié
        if ( ! wp_next_scheduled( 'bihrwi_auto_stock_sync' ) ) {
            $frequency = $settings['frequency'] ?? 'daily';
            $time = $settings['time'] ?? '02:00';

            // Calculer le timestamp de la première exécution
            $first_run = $this->calculate_next_sync_time( $frequency, $time );

            wp_schedule_event( $first_run, $frequency, 'bihrwi_auto_stock_sync' );
            
            $this->logger->log( "WP-Cron planifié pour synchronisation automatique: " . date( 'Y-m-d H:i:s', $first_run ) );
        }
    }

    /**
     * Calcule le timestamp de la prochaine synchronisation
     */
    private function calculate_next_sync_time( $frequency, $time = '02:00' ) {
        $current_time = current_time( 'timestamp' );

        switch ( $frequency ) {
            case 'hourly':
                return strtotime( '+1 hour', $current_time );

            case 'twicedaily':
                // Deux fois par jour: 06:00 et 18:00
                $today_morning = strtotime( 'today 06:00', $current_time );
                $today_evening = strtotime( 'today 18:00', $current_time );
                
                if ( $current_time < $today_morning ) {
                    return $today_morning;
                } elseif ( $current_time < $today_evening ) {
                    return $today_evening;
                } else {
                    return strtotime( 'tomorrow 06:00', $current_time );
                }

            case 'weekly':
                // Une fois par semaine, le dimanche à l'heure spécifiée
                list( $hour, $minute ) = explode( ':', $time );
                $next_sunday = strtotime( 'next sunday ' . $hour . ':' . $minute, $current_time );
                return $next_sunday;

            case 'daily':
            default:
                // Une fois par jour à l'heure spécifiée
                list( $hour, $minute ) = explode( ':', $time );
                $today = strtotime( 'today ' . $hour . ':' . $minute, $current_time );
                
                if ( $current_time < $today ) {
                    return $today;
                } else {
                    return strtotime( 'tomorrow ' . $hour . ':' . $minute, $current_time );
                }
        }
    }

    /**
     * Handler POST: Sauvegarde des paramètres de synchronisation
     */
    public function handle_save_stock_sync_settings() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Permission refusée' );
        }

        check_admin_referer( 'bihrwi_stock_sync_settings', 'bihrwi_stock_sync_nonce' );

        $enabled = isset( $_POST['sync_enabled'] ) ? true : false;
        $frequency = sanitize_text_field( $_POST['sync_frequency'] ?? 'daily' );
        $time = sanitize_text_field( $_POST['sync_time'] ?? '02:00' );

        $settings = array(
            'enabled' => $enabled,
            'frequency' => $frequency,
            'time' => $time,
            'last_sync' => get_option( 'bihrwi_stock_sync_settings' )['last_sync'] ?? null,
        );

        update_option( 'bihrwi_stock_sync_settings', $settings );

        // Reconfigurer le WP-Cron
        $timestamp = wp_next_scheduled( 'bihrwi_auto_stock_sync' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'bihrwi_auto_stock_sync' );
        }

        if ( $enabled ) {
            $next_run = $this->calculate_next_sync_time( $frequency, $time );
            wp_schedule_event( $next_run, $frequency, 'bihrwi_auto_stock_sync' );
            
            $this->logger->log( "Paramètres de synchronisation automatique sauvegardés. Prochaine exécution: " . date( 'Y-m-d H:i:s', $next_run ) );
        } else {
            $this->logger->log( "Synchronisation automatique désactivée." );
        }

        $redirect_url = add_query_arg( 
            array( 
                'page' => 'bihr-imported-products',
                'sync_settings_saved' => '1'
            ), 
            admin_url( 'admin.php' ) 
        );

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * AJAX: Synchronisation manuelle immédiate
     */
    public function ajax_manual_stock_sync() {
        check_ajax_referer( 'bihrwi_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission refusée' ) );
        }

        try {
            $result = $this->sync_all_products_stock();
            
            // Mettre à jour la date de dernière synchronisation
            $settings = get_option( 'bihrwi_stock_sync_settings', array() );
            $settings['last_sync'] = current_time( 'timestamp' );
            update_option( 'bihrwi_stock_sync_settings', $settings );

            wp_send_json_success( $result );

        } catch ( Exception $e ) {
            $this->logger->log( 'AJAX: Erreur synchronisation manuelle - ' . $e->getMessage() );
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }
    }

    /**
     * Exécute la synchronisation automatique (appelée par WP-Cron)
     */
    public function run_auto_stock_sync() {
        $this->logger->log( '=== Début synchronisation automatique des stocks ===' );

        try {
            $result = $this->sync_all_products_stock();

            // Mettre à jour la date de dernière synchronisation
            $settings = get_option( 'bihrwi_stock_sync_settings', array() );
            $settings['last_sync'] = current_time( 'timestamp' );
            update_option( 'bihrwi_stock_sync_settings', $settings );

            $this->logger->log( sprintf(
                'Synchronisation automatique terminée: %d produits, %d réussis, %d échoués',
                $result['total'],
                $result['success'],
                $result['failed']
            ) );

        } catch ( Exception $e ) {
            $this->logger->log( 'ERREUR synchronisation automatique: ' . $e->getMessage() );
        }

        $this->logger->log( '=== Fin synchronisation automatique des stocks ===' );
    }

    /**
     * Synchronise les stocks de tous les produits
     */
    private function sync_all_products_stock() {
        $start_time = microtime( true );
        $total = 0;
        $success = 0;
        $failed = 0;

        // Récupérer tous les produits WooCommerce avec un code BIHR
        $args = array(
            'status' => 'publish',
            'limit' => -1,
            'return' => 'ids',
        );

        $product_ids = wc_get_products( $args );
        $total = count( $product_ids );

        $this->logger->log( "Début synchronisation de $total produits..." );

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            $product_code = $product->get_sku();
            if ( empty( $product_code ) ) {
                $product_code = get_post_meta( $product_id, '_bihr_product_code', true );
            }

            if ( empty( $product_code ) ) {
                continue; // Pas de code BIHR, on skip
            }

            try {
                // Récupérer le stock depuis l'API
                $api_product = $this->api_client->get_product_by_code( $product_code );

                if ( $api_product && isset( $api_product['stock_level'] ) ) {
                    $stock_level = intval( $api_product['stock_level'] );

                    // Mettre à jour le stock WooCommerce
                    $product->set_stock_quantity( $stock_level );
                    
                    if ( $stock_level > 0 ) {
                        $product->set_stock_status( 'instock' );
                    } else {
                        $product->set_stock_status( 'outofstock' );
                    }

                    $product->save();
                    $success++;

                    // Log tous les 100 produits
                    if ( $success % 100 === 0 ) {
                        $this->logger->log( "Progression: $success/$total produits synchronisés..." );
                    }

                    // Rate limit: 1 requête par seconde
                    usleep( 1100000 ); // 1.1 seconde

                } else {
                    $failed++;
                }

            } catch ( Exception $e ) {
                $failed++;
                $this->logger->log( "Erreur sync produit $product_code: " . $e->getMessage() );
            }
        }

        $end_time = microtime( true );
        $duration = round( $end_time - $start_time, 2 );
        $duration_formatted = gmdate( 'H:i:s', (int) $duration );

        // Sauvegarder les statistiques de la dernière synchronisation
        update_option( 'bihrwi_last_stock_sync_log', array(
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'duration' => $duration_formatted,
            'timestamp' => current_time( 'timestamp' )
        ) );

        return array(
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'duration' => $duration_formatted
        );
    }

    /**
     * Toggle mode débutant/expert via AJAX
     */
    public function ajax_toggle_beginner_mode() {
        check_ajax_referer( 'bihr_toggle_mode', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $user_id = get_current_user_id();
        $enabled = ! empty( $_POST['enabled'] );

        update_user_meta( $user_id, '_bihr_beginner_mode', $enabled );

        wp_send_json_success( array(
            'mode' => $enabled ? 'beginner' : 'expert'
        ) );
    }

	
	
}

