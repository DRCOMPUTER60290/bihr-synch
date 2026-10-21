<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestionnaire AJAX pour la traduction des catégories BIHR.
 * Extrait de BihrWI_Admin pour isoler les ~170 lignes de handlers catégories.
 * Instancié dans BihrWI_Admin::__construct() avec injection de BihrWI_Category_Translator.
 */
class BihrWI_Admin_Category_Ajax {

    private BihrWI_Category_Translator $translator;

    public function __construct( BihrWI_Category_Translator $translator ) {
        $this->translator = $translator;

        add_action( 'wp_ajax_bihrwi_analyze_categories',        array( $this, 'ajax_analyze_categories' ) );
        add_action( 'wp_ajax_bihrwi_apply_french_categories',   array( $this, 'ajax_apply_french_categories' ) );
        add_action( 'wp_ajax_bihrwi_prepare_categories',        array( $this, 'ajax_prepare_categories' ) );
        add_action( 'wp_ajax_bihrwi_apply_categories_chunk',    array( $this, 'ajax_apply_categories_chunk' ) );
        add_action( 'wp_ajax_bihrwi_get_category_translations', array( $this, 'ajax_get_category_translations' ) );
        add_action( 'wp_ajax_bihrwi_clear_category_mapping',    array( $this, 'ajax_clear_category_mapping' ) );
        add_action( 'wp_ajax_bihrwi_export_category_mapping',   array( $this, 'ajax_export_category_mapping' ) );
    }

    /**
     * AJAX : analyse des CSV + traduction IA (streaming JSON)
     */
    public function ajax_analyze_categories() {
        check_ajax_referer( 'bihrwi_analyze_categories_action', '_wpnonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Accès refusé', 403 );
        }

        set_time_limit( 0 );
        ignore_user_abort( true );
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'X-Accel-Buffering: no' );
        header( 'Cache-Control: no-cache' );

        $this->translator->analyze_and_translate(
            function( $type, $message, $current, $total, $extra = array() ) {
                $data = array(
                    'type'    => $type,
                    'message' => $message,
                    'current' => $current,
                    'total'   => $total,
                );
                if ( ! empty( $extra ) ) {
                    $data['extra'] = $extra;
                }
                echo wp_json_encode( $data ) . "\n";
                if ( ob_get_level() ) {
                    ob_flush();
                }
                flush();
            }
        );

        exit;
    }

    /**
     * AJAX : renomme les termes product_cat anglais en français via le mapping (streaming JSON)
     */
    public function ajax_apply_french_categories() {
        check_ajax_referer( 'bihrwi_apply_french_categories_action', '_wpnonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Accès refusé', 403 );
        }

        set_time_limit( 120 );
        ignore_user_abort( true );
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'X-Accel-Buffering: no' );
        header( 'Cache-Control: no-cache' );

        $this->translator->rename_categories_to_french(
            function( $type, $message, $current, $total, $extra = array() ) {
                $data = array(
                    'type'    => $type,
                    'message' => $message,
                    'current' => $current,
                    'total'   => $total,
                );
                if ( ! empty( $extra ) ) {
                    $data['extra'] = $extra;
                }
                echo wp_json_encode( $data ) . "\n";
                if ( ob_get_level() ) {
                    ob_flush();
                }
                flush();
            }
        );

        exit;
    }

    /**
     * AJAX : phase 1 chunked — compte les produits et pré-crée les termes WC.
     */
    public function ajax_prepare_categories() {
        check_ajax_referer( 'bihrwi_apply_french_categories_action', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Accès refusé', 403 );
        }
        set_time_limit( 300 );
        $result = $this->translator->prepare_category_apply();
        if ( isset( $result['error'] ) ) {
            wp_send_json_error( $result['error'] );
        }
        wp_send_json_success( $result );
    }

    /**
     * AJAX : phase 2 chunked — traite un lot de produits.
     */
    public function ajax_apply_categories_chunk() {
        check_ajax_referer( 'bihrwi_apply_french_categories_action', '_wpnonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Accès refusé', 403 );
        }
        set_time_limit( 60 );
        $last_id    = isset( $_POST['last_id'] ) ? max( 0, (int) $_POST['last_id'] ) : 0;
        $chunk_size = 200;
        $result     = $this->translator->apply_category_chunk( $last_id, $chunk_size );
        if ( isset( $result['error'] ) ) {
            wp_send_json_error( $result['error'] );
        }
        wp_send_json_success( $result );
    }

    /**
     * AJAX : liste paginée des traductions pour l'interface admin
     */
    public function ajax_get_category_translations() {
        check_ajax_referer( 'bihrwi_categories_nonce', '_wpnonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Accès refusé' );
        }

        $search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
        $page     = isset( $_GET['page_num'] ) ? max( 1, intval( $_GET['page_num'] ) ) : 1;
        $per_page = 50;

        $result = $this->translator->get_all_translations( $search, $page, $per_page );
        wp_send_json_success( $result );
    }

    /**
     * AJAX : suppression du cache de traductions
     */
    public function ajax_clear_category_mapping() {
        check_ajax_referer( 'bihrwi_categories_nonce', '_wpnonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Accès refusé' );
        }

        $this->translator->clear_mapping();
        wp_send_json_success( array( 'message' => 'Cache des traductions supprimé.' ) );
    }

    /**
     * AJAX : export CSV du mapping
     */
    public function ajax_export_category_mapping() {
        check_ajax_referer( 'bihrwi_categories_nonce', '_wpnonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( 'Accès refusé', 403 );
        }

        $csv = $this->translator->export_mapping_as_csv();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="bihr-categories-traductions.csv"' );
        header( 'Cache-Control: no-cache' );
        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }
}
