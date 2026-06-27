<?php
if ( ! defined( 'BIHRWI_TOOL_SYNC_SKU_COMPAT' ) ) {
    exit;
}

global $wpdb;

$action       = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
$offset       = isset( $_GET['offset'] ) ? max( 0, intval( wp_unslash( $_GET['offset'] ) ) ) : 0;
$nonce_action = 'bihr_sync_sku_from_compatibility';

if ( 'sync' === $action ) {
    check_admin_referer( $nonce_action );
}

$compatibility_table = $wpdb->prefix . 'bihr_vehicle_compatibility';
$products_table      = $wpdb->prefix . 'bihr_products';
?>
<style>
.bihr-tool-page .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
.bihr-tool-page .stat-box { background: #f0f6fc; padding: 15px; border-radius: 5px; border-left: 4px solid #2271b1; }
.bihr-tool-page .stat-box strong { display: block; font-size: 24px; color: #2271b1; }
.bihr-tool-page .success { color: #00a32a; }
.bihr-tool-page .warning { color: #dba617; }
.bihr-tool-page .error { color: #d63638; }
.bihr-tool-page .progress { background: #e0e0e0; border-radius: 10px; height: 30px; margin: 20px 0; overflow: hidden; }
.bihr-tool-page .progress-bar { background: linear-gradient(90deg, #2271b1, #5fa3d0); height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
.bihr-tool-page .log { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 11px; }
.bihr-tool-page .log-entry { padding: 4px 0; border-bottom: 1px solid #eee; }
.bihr-tool-page table { width: 100%; border-collapse: collapse; font-size: 12px; }
.bihr-tool-page th { background: #2271b1; color: white; padding: 8px; text-align: left; }
.bihr-tool-page td { padding: 6px; border-bottom: 1px solid #ddd; }
</style>

<div class="wrap bihr-tool-page">
    <h1><?php esc_html_e( 'Synchronisation des SKU depuis la table de compatibilité', 'bihr-synch' ); ?></h1>

    <?php
    if ( 'sync' === $action ) {
        echo '<h2>' . esc_html__( 'Synchronisation en cours...', 'bihr-synch' ) . '</h2>';

        $batch_size = 500;

        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT bp.id)
                FROM {$products_table} bp
                INNER JOIN {$compatibility_table} vc ON vc.part_number = bp.new_part_number
                WHERE bp.product_id IS NOT NULL
                AND bp.product_id > 0"
            )
        );

        echo '<div class="stats">';
        echo '<div class="stat-box"><strong>' . esc_html( number_format( $total ) ) . '</strong> ' . esc_html__( 'Produits à traiter', 'bihr-synch' ) . '</div>';
        echo '<div class="stat-box"><strong>' . esc_html( number_format( $offset ) ) . '</strong> ' . esc_html__( 'Déjà traités', 'bihr-synch' ) . '</div>';
        echo '<div class="stat-box"><strong>' . esc_html( number_format( $total - $offset ) ) . '</strong> ' . esc_html__( 'Restants', 'bihr-synch' ) . '</div>';
        echo '</div>';

        $progress_percent = $total > 0 ? ( $offset / $total ) * 100 : 0;
        echo '<div class="progress">';
        echo '<div class="progress-bar" style="width: ' . esc_attr( (string) $progress_percent ) . '%">' . esc_html( round( $progress_percent, 1 ) ) . '%</div>';
        echo '</div>';

        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT bp.id, bp.product_code, bp.new_part_number, bp.product_id
                FROM {$products_table} bp
                INNER JOIN {$compatibility_table} vc ON vc.part_number = bp.new_part_number
                WHERE bp.product_id IS NOT NULL
                AND bp.product_id > 0
                LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            ),
            ARRAY_A
        );

        echo '<div class="log">';
        $sku_updated = 0;
        $errors      = 0;

        foreach ( $products as $product ) {
            $wc_product_id   = $product['product_id'];
            $product_code    = $product['product_code'];
            $new_part_number = $product['new_part_number'];
            $sku             = ! empty( $new_part_number ) ? $new_part_number : $product_code;

            $result = $wpdb->update(
                $wpdb->postmeta,
                array( 'meta_value' => $sku ),
                array(
                    'post_id'  => $wc_product_id,
                    'meta_key' => '_sku',
                ),
                array( '%s' ),
                array( '%d', '%s' )
            );

            if ( false !== $result ) {
                echo '<div class="log-entry success">' . sprintf(
                    esc_html__( 'WC #%1$d : SKU → %2$s', 'bihr-synch' ),
                    intval( $wc_product_id ),
                    esc_html( $sku )
                ) . '</div>';
                $sku_updated++;
            } else {
                echo '<div class="log-entry error">' . sprintf(
                    esc_html__( 'WC #%d : Erreur mise à jour SKU', 'bihr-synch' ),
                    intval( $wc_product_id )
                ) . '</div>';
                $errors++;
            }

            if ( ( $sku_updated + $errors ) % 100 === 0 ) {
                flush();
                ob_flush();
            }
        }

        echo '</div>';

        echo '<div class="stats">';
        echo '<div class="stat-box"><strong class="success">' . esc_html( (string) $sku_updated ) . '</strong> ' . esc_html__( 'SKU mis à jour', 'bihr-synch' ) . '</div>';
        echo '<div class="stat-box"><strong class="error">' . esc_html( (string) $errors ) . '</strong> ' . esc_html__( 'Erreurs', 'bihr-synch' ) . '</div>';
        echo '</div>';

        $new_offset = $offset + $batch_size;

        if ( $new_offset < $total ) {
            $continue_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'sync',
                        'offset' => $new_offset,
                    )
                ),
                $nonce_action
            );

            echo '<script>
                setTimeout(function() {
                    window.location.href = ' . wp_json_encode( $continue_url ) . ';
                }, 1000);
            </script>';
            echo '<p class="warning">' . esc_html__( 'Rechargement dans 1 seconde...', 'bihr-synch' ) . '</p>';
        } else {
            echo '<h2 class="success">' . esc_html__( 'SYNCHRONISATION TERMINÉE !', 'bihr-synch' ) . '</h2>';
            echo '<a href="?page=bihr-sync-sku-compat"><button class="button button-primary">' . esc_html__( 'Retour', 'bihr-synch' ) . '</button></a>';
        }
    } else {
        $total_to_process = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT bp.id)
                FROM {$products_table} bp
                INNER JOIN {$compatibility_table} vc ON vc.part_number = bp.new_part_number
                WHERE bp.product_id IS NOT NULL
                AND bp.product_id > 0"
            )
        );

        echo '<div class="stats">';
        echo '<div class="stat-box"><strong>' . esc_html( number_format( $total_to_process ) ) . '</strong> ' . esc_html__( 'Produits à traiter', 'bihr-synch' ) . '</div>';
        echo '</div>';

        if ( $total_to_process > 0 ) {
            echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">';
            echo '<h3>' . esc_html__( 'Action requise', 'bihr-synch' ) . '</h3>';
            echo '<p>' . esc_html__( 'Ce script va synchroniser les SKU des produits WooCommerce en utilisant le part_number de la table de compatibilité.', 'bihr-synch' ) . '</p>';
            echo '</div>';

            $sync_url = wp_nonce_url( add_query_arg( 'action', 'sync' ), $nonce_action );
            echo '<button class="button button-primary button-hero" onclick="if(confirm(\'' . esc_js(
                sprintf(
                    __( 'Lancer la synchronisation de %s produits ?', 'bihr-synch' ),
                    number_format( $total_to_process )
                )
            ) . '\')) window.location.href=' . wp_json_encode( $sync_url ) . '">'
                . esc_html__( 'LANCER LA SYNCHRONISATION', 'bihr-synch' ) . '</button>';
        } else {
            echo '<div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;">';
            echo '<h3 class="success">' . esc_html__( 'Aucun produit à traiter', 'bihr-synch' ) . '</h3>';
            echo '</div>';
        }
    }
    ?>
</div>
