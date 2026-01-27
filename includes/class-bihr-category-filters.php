<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestion des filtres de catégories WooCommerce (product_cat) pour la page bihr-products.
 *
 * - Injecte le JS uniquement sur la page ?page=bihr-products
 * - Fournit un endpoint AJAX pour récupérer les enfants d'une catégorie
 * - Expose un helper pour savoir quel term_id est sélectionné (cat / sous-cat / sous-sous-cat)
 */
class BihrWI_Category_Filters {

    /**
     * Initialisation des hooks.
     */
    public function init() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_bihr_get_child_categories', array( $this, 'ajax_get_child_categories' ) );
    }

    /**
     * Charge le JS uniquement sur la page bihr-products et passe les données nécessaires.
     *
     * @param string $hook Nom du hook admin courant.
     */
    public function enqueue_admin_assets( $hook ) {
        // Ne charger que sur les pages du plugin et plus précisément sur bihr-products.
        if ( empty( $_GET['page'] ) || 'bihr-products' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // Récupération des catégories racine de WooCommerce.
        $root_terms = array();

        if ( taxonomy_exists( 'product_cat' ) ) {
            $terms = get_terms(
                array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => 0,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                foreach ( $terms as $term ) {
                    $root_terms[] = array(
                        'id'   => (int) $term->term_id,
                        'name' => $term->name,
                    );
                }
            }
        }

        wp_enqueue_script(
            'bihr-category-filters',
            BIHRWI_PLUGIN_URL . 'assets/admin/category-filters.js',
            array( 'jquery' ),
            BIHRWI_VERSION,
            true
        );

        wp_localize_script(
            'bihr-category-filters',
            'BihrCategoryFilters',
            array(
                'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                'nonce'            => wp_create_nonce( 'bihr_category_filters' ),
                'rootCategories'   => $root_terms,
                'selected'         => array(
                    'cat'    => self::get_selected_term_id_from_request( 'bihr_cat' ),
                    'subcat' => self::get_selected_term_id_from_request( 'bihr_subcat' ),
                    'subcat2'=> self::get_selected_term_id_from_request( 'bihr_subcat2' ),
                ),
            )
        );
    }

    /**
     * Endpoint AJAX : retourne les enfants directs d'une catégorie product_cat.
     */
    public function ajax_get_child_categories() {
        check_ajax_referer( 'bihr_category_filters', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Permission denied.', 'bihr-synch' ),
                ),
                403
            );
        }

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Taxonomy product_cat not found.', 'bihr-synch' ),
                ),
                400
            );
        }

        $parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $terms = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => $parent_id,
                'orderby'    => 'name',
                'order'      => 'ASC',
            )
        );

        if ( is_wp_error( $terms ) ) {
            wp_send_json_error(
                array(
                    'message' => $terms->get_error_message(),
                ),
                500
            );
        }

        $data = array();

        if ( ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $data[] = array(
                    'id'   => (int) $term->term_id,
                    'name' => $term->name,
                );
            }
        }

        wp_send_json_success(
            array(
                'terms' => $data,
            )
        );
    }

    /**
     * Retourne l'ID du term le plus précis sélectionné (sous-sous > sous > cat).
     *
     * @return int
     */
    public static function get_selected_term_id_from_request_priority() {
        $subsub = self::get_selected_term_id_from_request( 'bihr_subcat2' );
        if ( $subsub ) {
            return $subsub;
        }

        $sub = self::get_selected_term_id_from_request( 'bihr_subcat' );
        if ( $sub ) {
            return $sub;
        }

        return self::get_selected_term_id_from_request( 'bihr_cat' );
    }

    /**
     * Récupère un term_id depuis $_GET/$_POST pour un paramètre donné.
     *
     * @param string $param Nom du paramètre (bihr_cat / bihr_subcat / bihr_subcat2).
     * @return int
     */
    public static function get_selected_term_id_from_request( $param ) {
        if ( isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return absint( $_GET[ $param ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ( isset( $_POST[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return absint( $_POST[ $param ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        return 0;
    }
}

