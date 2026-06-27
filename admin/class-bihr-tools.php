<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Tools {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_tools_pages' ), 20 );
    }

    public function register_tools_pages() {
        add_submenu_page(
            'bihr-dashboard',
            __( 'Sync SKU depuis produit', 'bihr-synch' ),
            __( 'Sync SKU produit', 'bihr-synch' ),
            'manage_options',
            'bihr-sync-sku',
            array( $this, 'render_sync_sku' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Sync SKU depuis compatibilité', 'bihr-synch' ),
            __( 'Sync SKU compat', 'bihr-synch' ),
            'manage_options',
            'bihr-sync-sku-compat',
            array( $this, 'render_sync_sku_compat' )
        );

        add_submenu_page(
            'bihr-dashboard',
            __( 'Debug filtre véhicule', 'bihr-synch' ),
            __( 'Debug véhicule', 'bihr-synch' ),
            'manage_options',
            'bihr-debug-vehicle',
            array( $this, 'render_debug_vehicle' )
        );
    }

    public function render_sync_sku() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'bihr-synch' ) );
        }
        if ( ! defined( 'BIHRWI_TOOL_SYNC_SKU' ) ) {
            define( 'BIHRWI_TOOL_SYNC_SKU', true );
        }
        require_once BIHRWI_PLUGIN_DIR . 'admin/tools/sync-product-sku.php';
    }

    public function render_sync_sku_compat() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'bihr-synch' ) );
        }
        if ( ! defined( 'BIHRWI_TOOL_SYNC_SKU_COMPAT' ) ) {
            define( 'BIHRWI_TOOL_SYNC_SKU_COMPAT', true );
        }
        require_once BIHRWI_PLUGIN_DIR . 'admin/tools/sync-sku-from-compatibility.php';
    }

    public function render_debug_vehicle() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'bihr-synch' ) );
        }
        if ( ! defined( 'BIHRWI_TOOL_DEBUG_VEHICLE' ) ) {
            define( 'BIHRWI_TOOL_DEBUG_VEHICLE', true );
        }
        require_once BIHRWI_PLUGIN_DIR . 'admin/tools/debug-vehicle-filter.php';
    }
}
