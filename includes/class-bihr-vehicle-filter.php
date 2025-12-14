<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestion du filtre de compatibilité véhicule pour le frontend
 */
class BihrWI_Vehicle_Filter {

    protected $compatibility;
    protected $vehicles_table;
    protected $compatibility_table;

    public function __construct() {
        global $wpdb;
        $this->compatibility        = new BihrWI_Vehicle_Compatibility();
        $this->vehicles_table       = $wpdb->prefix . 'bihr_vehicles';
        $this->compatibility_table  = $wpdb->prefix . 'bihr_vehicle_compatibility';

        // Hooks frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_shortcode( 'bihr_vehicle_filter', array( $this, 'render_vehicle_filter' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_bihr_get_manufacturers', array( $this, 'ajax_get_manufacturers' ) );
        add_action( 'wp_ajax_nopriv_bihr_get_manufacturers', array( $this, 'ajax_get_manufacturers' ) );
        add_action( 'wp_ajax_bihr_get_models', array( $this, 'ajax_get_models' ) );
        add_action( 'wp_ajax_nopriv_bihr_get_models', array( $this, 'ajax_get_models' ) );
        add_action( 'wp_ajax_bihr_get_years', array( $this, 'ajax_get_years' ) );
        add_action( 'wp_ajax_nopriv_bihr_get_years', array( $this, 'ajax_get_years' ) );
        add_action( 'wp_ajax_bihr_filter_products', array( $this, 'ajax_filter_products' ) );
        add_action( 'wp_ajax_nopriv_bihr_filter_products', array( $this, 'ajax_filter_products' ) );

        // Affichage sur page produit
        add_action( 'woocommerce_single_product_summary', array( $this, 'display_product_compatibility' ), 25 );
        
        // Widget WooCommerce
        add_action( 'widgets_init', array( $this, 'register_widget' ) );

        // Session pour stocker le véhicule sélectionné
        add_action( 'init', array( $this, 'start_session' ) );
    }

    /**
     * Démarre la session PHP
     */
    public function start_session() {
        if ( ! session_id() && ! headers_sent() ) {
            session_start();
        }
    }

    /**
     * Charge les assets frontend
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'bihr-vehicle-filter',
            plugins_url( 'public/css/bihr-vehicle-filter.css', dirname( dirname( __FILE__ ) ) ),
            array(),
            '1.4.0'
        );

        wp_enqueue_script(
            'bihr-vehicle-filter',
            plugins_url( 'public/js/bihr-vehicle-filter.js', dirname( dirname( __FILE__ ) ) ),
            array( 'jquery' ),
            '1.4.0',
            true
        );

        wp_localize_script( 'bihr-vehicle-filter', 'bihrVehicleFilter', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'bihr_vehicle_filter_nonce' ),
        ) );
    }

    /**
     * Enregistre le widget
     */
    public function register_widget() {
        register_widget( 'BihrWI_Vehicle_Filter_Widget' );
    }

    /**
     * Shortcode pour afficher le filtre véhicule
     */
    public function render_vehicle_filter( $atts ) {
        $atts = shortcode_atts( array(
            'title'       => 'Trouvez vos pièces',
            'show_button' => 'yes',
        ), $atts );

        ob_start();
        ?>
        <div class="bihr-vehicle-filter-wrapper">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h3 class="bihr-filter-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>

            <form id="bihr-vehicle-filter-form" class="bihr-vehicle-filter-form">
                <div class="bihr-filter-row">
                    <div class="bihr-filter-field">
                        <label for="bihr-manufacturer">🏍️ Fabricant</label>
                        <select id="bihr-manufacturer" name="manufacturer" required>
                            <option value="">-- Sélectionnez --</option>
                        </select>
                    </div>

                    <div class="bihr-filter-field">
                        <label for="bihr-model">🏍️ Modèle</label>
                        <select id="bihr-model" name="model" disabled required>
                            <option value="">-- Sélectionnez d'abord un fabricant --</option>
                        </select>
                    </div>

                    <div class="bihr-filter-field">
                        <label for="bihr-year">📅 Année</label>
                        <select id="bihr-year" name="year" disabled required>
                            <option value="">-- Sélectionnez d'abord un modèle --</option>
                        </select>
                    </div>
                </div>

                <?php if ( $atts['show_button'] === 'yes' ) : ?>
                    <div class="bihr-filter-actions">
                        <button type="submit" class="button bihr-filter-button">
                            🔍 Voir les pièces compatibles
                        </button>
                        <button type="button" class="button bihr-reset-button" id="bihr-reset-filter">
                            🔄 Réinitialiser
                        </button>
                    </div>
                <?php endif; ?>

                <div id="bihr-filter-results" class="bihr-filter-results" style="display:none;">
                    <div class="bihr-results-header">
                        <h4>Pièces compatibles pour votre véhicule</h4>
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
     * AJAX: Récupère la liste des fabricants
     */
    public function ajax_get_manufacturers() {
        check_ajax_referer( 'bihr_vehicle_filter_nonce', 'nonce' );

        global $wpdb;

        $manufacturers = $wpdb->get_results(
            "SELECT DISTINCT manufacturer_code, manufacturer_name 
             FROM {$this->vehicles_table} 
             WHERE manufacturer_name IS NOT NULL AND manufacturer_name != ''
             ORDER BY manufacturer_name ASC",
            ARRAY_A
        );

        wp_send_json_success( array(
            'manufacturers' => $manufacturers,
        ) );
    }

    /**
     * AJAX: Récupère les modèles pour un fabricant
     */
    public function ajax_get_models() {
        check_ajax_referer( 'bihr_vehicle_filter_nonce', 'nonce' );

        $manufacturer_code = isset( $_POST['manufacturer'] ) ? sanitize_text_field( $_POST['manufacturer'] ) : '';

        if ( empty( $manufacturer_code ) ) {
            wp_send_json_error( array( 'message' => 'Fabricant requis' ) );
        }

        global $wpdb;

        $models = $wpdb->get_results(
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

        wp_send_json_success( array(
            'models' => $models,
        ) );
    }

    /**
     * AJAX: Récupère les années pour un modèle
     */
    public function ajax_get_years() {
        check_ajax_referer( 'bihr_vehicle_filter_nonce', 'nonce' );

        $manufacturer_code = isset( $_POST['manufacturer'] ) ? sanitize_text_field( $_POST['manufacturer'] ) : '';
        $model_code        = isset( $_POST['model'] ) ? sanitize_text_field( $_POST['model'] ) : '';

        if ( empty( $manufacturer_code ) || empty( $model_code ) ) {
            wp_send_json_error( array( 'message' => 'Fabricant et modèle requis' ) );
        }

        global $wpdb;

        $years = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT vehicle_year, version_name, vehicle_code
                 FROM {$this->vehicles_table} 
                 WHERE manufacturer_code = %s 
                 AND commercial_model_code = %s
                 AND vehicle_year IS NOT NULL 
                 AND vehicle_year != ''
                 ORDER BY vehicle_year DESC",
                $manufacturer_code,
                $model_code
            ),
            ARRAY_A
        );

        wp_send_json_success( array(
            'years' => $years,
        ) );
    }

    /**
     * AJAX: Filtre les produits par véhicule
     */
    public function ajax_filter_products() {
        check_ajax_referer( 'bihr_vehicle_filter_nonce', 'nonce' );

        $vehicle_code = isset( $_POST['vehicle_code'] ) ? sanitize_text_field( $_POST['vehicle_code'] ) : '';

        if ( empty( $vehicle_code ) ) {
            wp_send_json_error( array( 'message' => 'Véhicule requis' ) );
        }

        // Stocker en session
        $_SESSION['bihr_selected_vehicle'] = $vehicle_code;

        global $wpdb;

        // Récupérer les part_numbers compatibles
        $part_numbers = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT part_number 
                 FROM {$this->compatibility_table} 
                 WHERE vehicle_code = %s",
                $vehicle_code
            )
        );

        if ( empty( $part_numbers ) ) {
            wp_send_json_success( array(
                'count'    => 0,
                'products' => array(),
                'message'  => 'Aucune pièce compatible trouvée pour ce véhicule.',
            ) );
        }

        // Récupérer les produits WooCommerce correspondants
        $products = array();
        foreach ( $part_numbers as $part_number ) {
            $product_id = $this->get_product_by_sku( $part_number );
            
            if ( $product_id ) {
                $product = wc_get_product( $product_id );
                
                if ( $product ) {
                    $products[] = array(
                        'id'    => $product_id,
                        'title' => $product->get_name(),
                        'price' => $product->get_price_html(),
                        'url'   => get_permalink( $product_id ),
                        'image' => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
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

    /**
     * Trouve un produit WooCommerce par SKU
     */
    protected function get_product_by_sku( $sku ) {
        global $wpdb;

        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id 
                 FROM {$wpdb->postmeta} 
                 WHERE meta_key = '_sku' 
                 AND meta_value = %s 
                 LIMIT 1",
                $sku
            )
        );

        return $product_id;
    }

    /**
     * Affiche les informations de compatibilité sur la page produit
     */
    public function display_product_compatibility() {
        global $product, $wpdb;

        if ( ! $product ) {
            return;
        }

        $sku = $product->get_sku();
        if ( empty( $sku ) ) {
            return;
        }

        // Récupérer les véhicules compatibles
        $compatibilities = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT v.manufacturer_name, v.commercial_model_name, v.vehicle_year, v.version_name
                 FROM {$this->compatibility_table} c
                 INNER JOIN {$this->vehicles_table} v ON c.vehicle_code = v.vehicle_code
                 WHERE c.part_number = %s
                 ORDER BY v.manufacturer_name, v.commercial_model_name, v.vehicle_year DESC
                 LIMIT 20",
                $sku
            ),
            ARRAY_A
        );

        if ( empty( $compatibilities ) ) {
            return;
        }

        ?>
        <div class="bihr-product-compatibility">
            <h3>🏍️ Véhicules compatibles</h3>
            <div class="bihr-compatibility-list">
                <?php foreach ( $compatibilities as $compat ) : ?>
                    <div class="bihr-compatibility-item">
                        <strong><?php echo esc_html( $compat['manufacturer_name'] ); ?></strong>
                        <?php echo esc_html( $compat['commercial_model_name'] ); ?>
                        <span class="year">(<?php echo esc_html( $compat['vehicle_year'] ); ?>)</span>
                        <?php if ( ! empty( $compat['version_name'] ) ) : ?>
                            <span class="version"><?php echo esc_html( $compat['version_name'] ); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
            $total = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT v.vehicle_code)
                     FROM {$this->compatibility_table} c
                     INNER JOIN {$this->vehicles_table} v ON c.vehicle_code = v.vehicle_code
                     WHERE c.part_number = %s",
                    $sku
                )
            );
            if ( $total > 20 ) :
                ?>
                <p class="bihr-more-vehicles">
                    <em>+ <?php echo esc_html( $total - 20 ); ?> autres véhicules compatibles</em>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}

/**
 * Widget pour le filtre véhicule
 */
class BihrWI_Vehicle_Filter_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'bihr_vehicle_filter_widget',
            '🏍️ BIHR - Filtre Véhicule',
            array( 'description' => 'Filtre les produits par compatibilité véhicule' )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }

        echo do_shortcode( '[bihr_vehicle_filter show_button="yes"]' );

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : 'Trouvez vos pièces';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Titre:</label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance          = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        return $instance;
    }
}
