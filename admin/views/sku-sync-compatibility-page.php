<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_woocommerce' ) ) {
    wp_die( esc_html__( 'Accès refusé.', 'BIHR-SYNCH-main' ) );
}

global $wpdb;

$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
$offset = isset( $_GET['offset'] ) ? max( 0, (int) $_GET['offset'] ) : 0;

$batch_size = 500;
$nonce_action = 'bihrwi_sku_sync_compat';
$nonce = wp_create_nonce( $nonce_action );

// Expression SQL de la clé de correspondance vers la compatibilité
// Priorité : NewPartNumber (meta) -> SKU actuel -> Code BIHR
$compat_lookup_expr = 'COALESCE(pm_new.meta_value, pm_sku.meta_value, pm_code.meta_value)';
$match_key_expr     = $compat_lookup_expr; // clé de correspondance utilisée pour la recherche
// SECURITE : $compat_lookup_expr et $match_key_expr sont des expressions SQL définies en dur, sans aucune entrée utilisateur.
// Elles ne contiennent aucune concaténation de variable dynamique ou issue de l'utilisateur.

$base_url = admin_url( 'admin.php?page=bihr-sku-sync-compat' );
$sync_url = add_query_arg(
    array(
        'action'   => 'sync',
        'offset'   => 0,
        '_wpnonce' => $nonce,
    ),
    $base_url
);

?>
<div class="wrap">
    <h1><?php esc_html_e( 'Synchronisation SKU depuis Compatibilité Véhicules', 'BIHR-SYNCH-main' ); ?></h1>

    <?php
    if ( $action === 'sync' ) {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), $nonce_action ) ) {
              echo '<div class="notice notice-error"><p>' . esc_html__( 'Nonce invalide. Rechargez la page et réessayez.', 'BIHR-SYNCH-main' ) . '</p></div>';
        } else {
            echo '<h2>' . esc_html__( 'Synchronisation en cours…', 'BIHR-SYNCH-main' ) . '</h2>';

            // Total produits WooCommerce avec compatibilité
            // SECURITE : $compat_lookup_expr est une expression SQL définie en dur, sans entrée utilisateur.
            $total = (int) $wpdb->get_var("
                SELECT COUNT(DISTINCT pm_code.post_id)
                FROM {$wpdb->postmeta} pm_code
                INNER JOIN {$wpdb->posts} p ON p.ID = pm_code.post_id
                LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = pm_code.post_id AND pm_new.meta_key = '_bihr_new_part_number'
                LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_code.post_id AND pm_sku.meta_key = '_sku'
                WHERE pm_code.meta_key = '_bihr_product_code'
                AND pm_code.meta_value IS NOT NULL
                AND pm_code.meta_value != ''
                AND p.post_type = 'product'
                AND EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}bihr_vehicle_compatibility vc
                    WHERE vc.part_number = {$compat_lookup_expr}
                )
            " );

            $already = min( $offset, $total );
            $remaining = max( 0, $total - $already );
            $progress_percent = $total > 0 ? ( $already / $total ) * 100 : 0;

            ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin:20px 0;">
                <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                    <strong style="display:block;font-size:24px;color:#2271b1;"><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
                    <?php esc_html_e( 'Produits avec compatibilité', 'BIHR-SYNCH-main' ); ?>
                </div>
                <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                    <strong style="display:block;font-size:24px;color:#2271b1;"><?php echo esc_html( number_format_i18n( $already ) ); ?></strong>
                    <?php esc_html_e( 'Déjà traités', 'BIHR-SYNCH-main' ); ?>
                </div>
                <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                    <strong style="display:block;font-size:24px;color:#2271b1;"><?php echo esc_html( number_format_i18n( $remaining ) ); ?></strong>
                    <?php esc_html_e( 'Restants', 'BIHR-SYNCH-main' ); ?>
                </div>
            </div>

            <div style="background:#e0e0e0;border-radius:10px;height:30px;margin:20px 0;overflow:hidden;">
                <div style="background:linear-gradient(90deg,#2271b1,#5fa3d0);height:100%;width:<?php echo esc_attr( $progress_percent ); ?>%;transition:width .3s;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;">
                    <?php echo esc_html( round( $progress_percent, 1 ) ); ?>%
                </div>
            </div>

            <?php
            $products = $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT DISTINCT
                        pm_code.post_id as wc_product_id,
                        pm_code.meta_value as product_code,
                        pm_new.meta_value as new_part_number,
                        pm_sku.meta_value as current_sku,
                        vc.part_number,
                        {$match_key_expr} AS match_key, // SECURITE : $match_key_expr est une expression SQL définie en dur, sans entrée utilisateur.
                        p.post_title as name
                    FROM {$wpdb->postmeta} pm_code
                    INNER JOIN {$wpdb->posts} p ON p.ID = pm_code.post_id
                    LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = pm_code.post_id AND pm_new.meta_key = '_bihr_new_part_number'
                    LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_code.post_id AND pm_sku.meta_key = '_sku'
                    INNER JOIN {$wpdb->prefix}bihr_vehicle_compatibility vc ON vc.part_number = {$compat_lookup_expr} // SECURITE : $compat_lookup_expr est une expression SQL définie en dur, sans entrée utilisateur.
                    WHERE pm_code.meta_key = '_bihr_product_code'
                    AND pm_code.meta_value IS NOT NULL
                    AND pm_code.meta_value != ''
                    AND p.post_type = 'product'
                    GROUP BY pm_code.post_id
                    LIMIT %d OFFSET %d
                    ",
                    $batch_size,
                    $offset
                ),
                ARRAY_A
            );

            echo '<div style="background:#f9f9f9;border:1px solid #ddd;padding:15px;border-radius:5px;max-height:400px;overflow-y:auto;font-family:monospace;font-size:11px;">';

            $sku_inserted = 0;
            $sku_updated  = 0;
            $errors       = 0;

            foreach ( $products as $product ) {
                $wc_product_id   = (int) $product['wc_product_id'];
                $product_code    = (string) $product['product_code'];
                $part_number     = (string) $product['part_number'];
                if ( $part_number === '' && ! empty( $product['match_key'] ) ) {
                    $part_number = (string) $product['match_key'];
                }
                $new_part_number = isset( $product['new_part_number'] ) ? (string) $product['new_part_number'] : '';
                $sku             = $part_number;

                if ( $sku === '' ) {
                    echo '<div style="padding:4px 0;border-bottom:1px solid #eee;color:#dba617;">⚠️ WC #' . esc_html( $wc_product_id ) . ' : part_number vide</div>';
                    $errors++;
                    continue;
                }

                $existing_sku = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_sku'",
                        $wc_product_id
                    )
                );

                if ( $existing_sku ) {
                    $wpdb->update(
                        $wpdb->postmeta,
                        array( 'meta_value' => $sku ),
                        array( 'post_id' => $wc_product_id, 'meta_key' => '_sku' ),
                        array( '%s' ),
                        array( '%d', '%s' )
                    );

                    echo '<div style="padding:4px 0;border-bottom:1px solid #eee;color:#00a32a;">✅ WC #' . esc_html( $wc_product_id ) . ' : SKU mis à jour → ' . esc_html( $sku ) . ' (code: ' . esc_html( $product_code ) . ( $new_part_number ? ', new: ' . esc_html( $new_part_number ) : '' ) . ')</div>';
                    $sku_updated++;
                } else {
                    $result = $wpdb->insert(
                        $wpdb->postmeta,
                        array(
                            'post_id'    => $wc_product_id,
                            'meta_key'   => '_sku',
                            'meta_value' => $sku,
                        ),
                        array( '%d', '%s', '%s' )
                    );

                    if ( $result ) {
                        echo '<div style="padding:4px 0;border-bottom:1px solid #eee;color:#00a32a;">✅ WC #' . esc_html( $wc_product_id ) . ' : SKU créé → ' . esc_html( $sku ) . ' (code: ' . esc_html( $product_code ) . ( $new_part_number ? ', new: ' . esc_html( $new_part_number ) : '' ) . ')</div>';
                        $sku_inserted++;
                    } else {
                        echo '<div style="padding:4px 0;border-bottom:1px solid #eee;color:#d63638;">❌ WC #' . esc_html( $wc_product_id ) . ' : Erreur INSERT</div>';
                        $errors++;
                    }
                }

                if ( ( $sku_inserted + $sku_updated ) % 100 === 0 ) {
                    if ( function_exists( 'flush' ) ) {
                        flush();
                    }
                }
            }

            echo '</div>';

            ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin:20px 0;">
                <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                    <strong style="display:block;font-size:24px;color:#00a32a;"><?php echo esc_html( number_format_i18n( $sku_inserted ) ); ?></strong>
                    <?php esc_html_e( 'SKU créés', 'BIHR-SYNCH-main' ); ?>
                </div>
                <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                    <strong style="display:block;font-size:24px;color:#dba617;"><?php echo esc_html( number_format_i18n( $sku_updated ) ); ?></strong>
                    <?php esc_html_e( 'SKU mis à jour', 'BIHR-SYNCH-main' ); ?>
                </div>
                <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                    <strong style="display:block;font-size:24px;color:#d63638;"><?php echo esc_html( number_format_i18n( $errors ) ); ?></strong>
                    <?php esc_html_e( 'Erreurs', 'BIHR-SYNCH-main' ); ?>
                </div>
            </div>

            <?php
            $new_offset = $offset + $batch_size;
            if ( $new_offset < $total ) {
                $continue_url = add_query_arg(
                    array(
                        'action'   => 'sync',
                        'offset'   => $new_offset,
                        '_wpnonce' => sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
                    ),
                    $base_url
                );

                echo '<script>setTimeout(function(){ window.location.href = ' . wp_json_encode( $continue_url ) . '; }, 1000);</script>';
                echo '<p class="notice notice-warning" style="display:inline-block;padding:8px 12px;">' . esc_html__( 'Rechargement automatique dans 1 seconde…', 'BIHR-SYNCH-main' ) . '</p>';
            } else {
                $final_sku = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''" );
                echo '<h2 style="color:#00a32a;">' . esc_html__( 'Synchronisation terminée !', 'BIHR-SYNCH-main' ) . '</h2>';
                echo '<p style="color:#00a32a;">' . esc_html__( 'Total SKU synchronisés :', 'BIHR-SYNCH-main' ) . ' ' . esc_html( number_format_i18n( $final_sku ) ) . '</p>';
                echo '<a class="button" href="' . esc_url( $base_url ) . '">' . esc_html__( 'Retour', 'BIHR-SYNCH-main' ) . '</a>';
            }
        }
    } else {
        $wc_with_compatibility = (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT pm_code.post_id)
            FROM {$wpdb->postmeta} pm_code
            INNER JOIN {$wpdb->posts} p ON p.ID = pm_code.post_id
            LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = pm_code.post_id AND pm_new.meta_key = '_bihr_new_part_number'
            LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_code.post_id AND pm_sku.meta_key = '_sku'
            WHERE pm_code.meta_key = '_bihr_product_code'
            AND pm_code.meta_value IS NOT NULL
            AND pm_code.meta_value != ''
            AND p.post_type = 'product'
            AND EXISTS (
                SELECT 1 FROM {$wpdb->prefix}bihr_vehicle_compatibility vc
                WHERE vc.part_number = {$compat_lookup_expr} // SECURITE : $compat_lookup_expr est une expression SQL définie en dur, sans entrée utilisateur.
            )
        ");

        $wc_products   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'" );
        $wc_sku_count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''" );

        ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin:20px 0;">
            <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                <strong style="display:block;font-size:24px;color:#2271b1;"><?php echo esc_html( number_format_i18n( $wc_with_compatibility ) ); ?></strong>
                <?php esc_html_e( 'Produits WC avec compatibilité', 'BIHR-SYNCH-main' ); ?>
            </div>
            <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                <strong style="display:block;font-size:24px;color:#2271b1;"><?php echo esc_html( number_format_i18n( $wc_products ) ); ?></strong>
                <?php esc_html_e( 'Total produits WooCommerce', 'BIHR-SYNCH-main' ); ?>
            </div>
            <div style="background:#f0f6fc;padding:15px;border-radius:5px;border-left:4px solid #2271b1;">
                <strong style="display:block;font-size:24px;color:#00a32a;"><?php echo esc_html( number_format_i18n( $wc_sku_count ) ); ?></strong>
                <?php esc_html_e( 'SKU actuels', 'BIHR-SYNCH-main' ); ?>
            </div>
        </div>

        <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:15px;margin:20px 0;">
            <h3 style="margin-top:0;">⚠️ <?php esc_html_e( 'Important', 'BIHR-SYNCH-main' ); ?></h3>
            <p><?php esc_html_e( 'Cette page synchronise les SKU en utilisant le part_number de la table de compatibilité véhicules, pas le product_code BIHR.', 'BIHR-SYNCH-main' ); ?></p>
                <p><?php esc_html_e( "Le match se fait dans l'ordre : NewPartNumber → SKU actuel → Code BIHR.", 'BIHR-SYNCH-main' ); ?></p>
        </div>

        <p>
            <a class="button button-primary" href="<?php echo esc_url( $sync_url ); ?>" onclick="return confirm('<?php echo esc_js( sprintf( __( 'Lancer la synchronisation de %s produits ?', 'BIHR-SYNCH-main' ), number_format_i18n( $wc_with_compatibility ) ) ); ?>');">
                🚀 <?php esc_html_e( 'LANCER LA SYNCHRONISATION', 'BIHR-SYNCH-main' ); ?>
            </a>
        </p>

        <h3>📊 <?php esc_html_e( 'Aperçu (10 premiers produits)', 'BIHR-SYNCH-main' ); ?></h3>
        <?php
        $samples = $wpdb->get_results("
            SELECT 
                pm_code.post_id as wc_id,
                p.post_title as name,
                pm_code.meta_value as product_code,
                pm_new.meta_value as new_part_number,
                pm_sku.meta_value as current_sku,
                vc.part_number,
                {$match_key_expr} AS match_key
            FROM {$wpdb->postmeta} pm_code
            INNER JOIN {$wpdb->posts} p ON p.ID = pm_code.post_id
            LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = pm_code.post_id AND pm_new.meta_key = '_bihr_new_part_number'
            LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_code.post_id AND pm_sku.meta_key = '_sku'
            LEFT JOIN {$wpdb->prefix}bihr_vehicle_compatibility vc ON vc.part_number = {$compat_lookup_expr}
            WHERE pm_code.meta_key = '_bihr_product_code'
            AND pm_code.meta_value IS NOT NULL
            AND pm_code.meta_value != ''
            AND p.post_type = 'product'
            GROUP BY pm_code.post_id
            LIMIT 10
        ", ARRAY_A );

        echo '<table class="widefat striped" style="max-width:1400px;">';
        echo '<thead><tr>';
        echo '<th>WC ID</th><th>Nom Produit</th><th>Code BIHR</th><th>NewPartNumber</th><th>Part Number</th><th>SKU Actuel</th><th>Statut</th>';
        echo '</tr></thead><tbody>';

        foreach ( $samples as $row ) {
            $current_sku = isset( $row['current_sku'] ) ? (string) $row['current_sku'] : '';
            $part_number = isset( $row['part_number'] ) ? (string) $row['part_number'] : '';
            if ( $part_number === '' && ! empty( $row['match_key'] ) ) {
                $part_number = (string) $row['match_key'];
            }

            if ( $part_number === '' ) {
                $status = '<span style="color:#d63638;">❌ ' . esc_html__( 'Pas de part_number', 'BIHR-SYNCH-main' ) . '</span>';
            } elseif ( $current_sku === $part_number ) {
                $status = '<span style="color:#00a32a;">✅ ' . esc_html__( 'OK', 'BIHR-SYNCH-main' ) . '</span>';
            } else {
                $status = '<span style="color:#dba617;">⚠️ ' . esc_html__( 'SKU différent', 'BIHR-SYNCH-main' ) . '</span>';
            }

            echo '<tr>';
            echo '<td>' . esc_html( $row['wc_id'] ) . '</td>';
            echo '<td>' . esc_html( mb_substr( (string) $row['name'], 0, 60 ) ) . '</td>';
            echo '<td>' . esc_html( (string) $row['product_code'] ) . '</td>';
            echo '<td>' . esc_html( (string) ( $row['new_part_number'] ?? '' ) ) . '</td>';
            echo '<td><strong>' . esc_html( $part_number ?: '-' ) . '</strong></td>';
            echo '<td>' . esc_html( $current_sku ?: '-' ) . '</td>';
            echo '<td>' . wp_kses_post( $status ) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
    ?>
</div>
