<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestion du filtre de produits par catégories pour le frontend
 */
class BihrWI_Product_Filter {

    protected $product_sync;

    public function __construct() {
        $this->product_sync = new BihrWI_Product_Sync( new BihrWI_Logger() );

        // Hooks frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_shortcode( 'bihr_product_filter', array( $this, 'render_product_filter' ) );
        
        // AJAX handlers (public + logged in)
        add_action( 'wp_ajax_bihr_get_product_cat_level1', array( $this, 'ajax_get_cat_level1' ) );
        add_action( 'wp_ajax_nopriv_bihr_get_product_cat_level1', array( $this, 'ajax_get_cat_level1' ) );
        add_action( 'wp_ajax_bihr_get_product_cat_level2', array( $this, 'ajax_get_cat_level2' ) );
        add_action( 'wp_ajax_nopriv_bihr_get_product_cat_level2', array( $this, 'ajax_get_cat_level2' ) );
        add_action( 'wp_ajax_bihr_get_product_cat_level3', array( $this, 'ajax_get_cat_level3' ) );
        add_action( 'wp_ajax_nopriv_bihr_get_product_cat_level3', array( $this, 'ajax_get_cat_level3' ) );
        add_action( 'wp_ajax_bihr_filter_products_by_category', array( $this, 'ajax_filter_products_by_category' ) );
        add_action( 'wp_ajax_nopriv_bihr_filter_products_by_category', array( $this, 'ajax_filter_products_by_category' ) );
    }

    /**
     * Charge les assets frontend
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'bihr-product-filter-css',
            BIHRWI_PLUGIN_URL . 'public/css/bihr-product-filter.css',
            array(),
            BIHRWI_VERSION
        );

        wp_enqueue_script(
            'bihr-product-filter-js',
            BIHRWI_PLUGIN_URL . 'public/js/bihr-product-filter.js',
            array( 'jquery' ),
            BIHRWI_VERSION,
            true
        );

        wp_localize_script(
            'bihr-product-filter-js',
            'bihrProductFilterData',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'bihr_product_filter_nonce' ),
            )
        );
    }

    /**
     * Shortcode pour afficher le filtre produit
     */
    public function render_product_filter( $atts ) {
        $atts = shortcode_atts( array(
            'title'       => 'Filtrez nos produits',
            'show_button' => 'yes',
        ), $atts );

        ob_start();
        ?>
        <div class="bihr-product-filter-wrapper">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h3 class="bihr-filter-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>

            <form id="bihr-product-filter-form" class="bihr-product-filter-form">
                <div class="bihr-filter-row">
                    <div class="bihr-filter-field">
                        <label for="bihr-cat-l1">📦 Catégorie Niveau 1</label>
                        <select id="bihr-cat-l1" name="cat_l1">
                            <option value="">-- Toutes les catégories --</option>
                        </select>
                    </div>

                    <div class="bihr-filter-field">
                        <label for="bihr-cat-l2">📦 Catégorie Niveau 2</label>
                        <select id="bihr-cat-l2" name="cat_l2" disabled>
                            <option value="">-- Sélectionnez d'abord un niveau 1 --</option>
                        </select>
                    </div>

                    <div class="bihr-filter-field">
                        <label for="bihr-cat-l3">📦 Catégorie Niveau 3</label>
                        <select id="bihr-cat-l3" name="cat_l3" disabled>
                            <option value="">-- Sélectionnez d'abord un niveau 2 --</option>
                        </select>
                    </div>
                </div>

                <?php if ( $atts['show_button'] === 'yes' ) : ?>
                    <div class="bihr-filter-actions">
                        <button type="submit" class="button bihr-filter-button">
                            🔍 Voir les produits
                        </button>
                        <button type="button" class="button bihr-reset-button" id="bihr-reset-product-filter">
                            🔄 Réinitialiser
                        </button>
                    </div>
                <?php endif; ?>

                <div id="bihr-product-filter-results" class="bihr-product-filter-results" style="display:none;">
                    <div class="bihr-results-header">
                        <h4>Produits trouvés</h4>
                        <span class="bihr-results-count"></span>
                    </div>
                    <div class="bihr-results-content"></div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Récupère la liste des catégories niveau 1
     */
    public function ajax_get_cat_level1() {
        check_ajax_referer( 'bihr_product_filter_nonce', 'nonce' );

        $categories = $this->product_sync->get_distinct_cat_level1();

        wp_send_json_success( array(
            'categories' => $categories,
        ) );
    }

    /**
     * AJAX: Récupère les catégories niveau 2 pour un niveau 1 donné
     */
    public function ajax_get_cat_level2() {
        check_ajax_referer( 'bihr_product_filter_nonce', 'nonce' );

        $cat_l1 = isset( $_POST['cat_l1'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_l1'] ) ) : '';

        if ( empty( $cat_l1 ) ) {
            wp_send_json_error( array( 'message' => 'Catégorie niveau 1 requise' ) );
        }

        $categories = $this->product_sync->get_distinct_cat_level2( $cat_l1 );

        wp_send_json_success( array(
            'categories' => $categories,
        ) );
    }

    /**
     * AJAX: Récupère les catégories niveau 3 pour un couple (l1, l2)
     */
    public function ajax_get_cat_level3() {
        check_ajax_referer( 'bihr_product_filter_nonce', 'nonce' );

        $cat_l1 = isset( $_POST['cat_l1'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_l1'] ) ) : '';
        $cat_l2 = isset( $_POST['cat_l2'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_l2'] ) ) : '';

        if ( empty( $cat_l1 ) || empty( $cat_l2 ) ) {
            wp_send_json_error( array( 'message' => 'Catégories niveau 1 et 2 requises' ) );
        }

        $categories = $this->product_sync->get_distinct_cat_level3( $cat_l1, $cat_l2 );

        wp_send_json_success( array(
            'categories' => $categories,
        ) );
    }

    /**
     * AJAX: Filtre les produits WooCommerce selon les catégories sélectionnées
     */
    public function ajax_filter_products_by_category() {
        check_ajax_referer( 'bihr_product_filter_nonce', 'nonce' );

        $cat_l1 = isset( $_POST['cat_l1'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_l1'] ) ) : '';
        $cat_l2 = isset( $_POST['cat_l2'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_l2'] ) ) : '';
        $cat_l3 = isset( $_POST['cat_l3'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_l3'] ) ) : '';

        if ( empty( $cat_l1 ) && empty( $cat_l2 ) && empty( $cat_l3 ) ) {
            wp_send_json_error( array( 'message' => 'Veuillez sélectionner au moins une catégorie' ) );
        }

        // Récupérer les IDs de produits BIHR correspondants
        $bihr_product_ids = $this->product_sync->get_all_filtered_product_ids(
            '', // search
            '', // stock_filter
            '', // price_min
            '', // price_max
            '', // category_filter
            $cat_l1,
            $cat_l2,
            $cat_l3
        );

        if ( empty( $bihr_product_ids ) ) {
            wp_send_json_success( array(
                'count'    => 0,
                'products' => array(),
                'message'  => 'Aucun produit trouvé pour ces catégories.',
            ) );
        }

        // Récupérer les produits WooCommerce correspondants via les meta _bihr_product_code
        global $wpdb;
        $placeholders = implode( ',', array_fill( 0, count( $bihr_product_ids ), '%d' ) );
        
        // Récupérer les product_code depuis wp_bihr_products
        $table_name = $wpdb->prefix . 'bihr_products';
        $product_codes = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT product_code FROM `{$table_name}` WHERE id IN ($placeholders)",
                ...$bihr_product_ids
            )
        );

        if ( empty( $product_codes ) ) {
            wp_send_json_success( array(
                'count'    => 0,
                'products' => array(),
                'message'  => 'Aucun produit WooCommerce trouvé.',
            ) );
        }

        // Trouver les produits WooCommerce par _bihr_product_code
        $products = array();
        foreach ( $product_codes as $product_code ) {
            $product_id = wc_get_product_id_by_sku( $product_code );
            
            // Si pas trouvé par SKU, chercher par meta
            if ( ! $product_id ) {
                $product_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_bihr_product_code' AND meta_value = %s LIMIT 1",
                        $product_code
                    )
                );
            }

            if ( $product_id ) {
                $product = wc_get_product( $product_id );
                
                if ( $product && $product->is_visible() ) {
                    $products[] = array(
                        'id'    => $product_id,
                        'title' => $product->get_name(),
                        'price' => $product->get_price_html(),
                        'url'   => get_permalink( $product_id ),
                        'image' => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src(),
                        'sku'   => $product->get_sku(),
                    );
                }
            }
        }

        wp_send_json_success( array(
            'count'    => count( $products ),
            'products' => $products,
        ) );
    }
}
