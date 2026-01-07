<?php
/**
 * Script de synchronisation des SKU depuis la table de compatibilité
 * 
 * Utilise part_number de wp_bihr_vehicle_compatibility comme SKU
 * au lieu de product_code de wp_bihr_products
 */

// Charger WordPress si nécessaire
if ( ! defined( 'ABSPATH' ) ) {
    $wp_load_path = dirname( __FILE__, 4 ) . '/wp-load.php';

    if ( file_exists( $wp_load_path ) ) {
        require_once $wp_load_path;
    } else {
        exit( '❌ Impossible de charger WordPress' );
    }
}

// Vérifier les permissions
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Accès refusé. Vous devez être administrateur.', 'bihr-synch' ) );
}

$action       = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
$offset       = isset( $_GET['offset'] ) ? max( 0, intval( wp_unslash( $_GET['offset'] ) ) ) : 0;
$nonce_action = 'bihr_sync_sku_from_compatibility';

if ( 'sync' === $action ) {
    check_admin_referer( $nonce_action );
}

global $wpdb;
set_time_limit(0);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Synchronisation SKU depuis Compatibilité</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2271b1; border-bottom: 3px solid #2271b1; padding-bottom: 10px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-box { background: #f0f6fc; padding: 15px; border-radius: 5px; border-left: 4px solid #2271b1; }
        .stat-box strong { display: block; font-size: 24px; color: #2271b1; }
        .success { color: #00a32a; }
        .warning { color: #dba617; }
        .error { color: #d63638; }
        .progress { background: #e0e0e0; border-radius: 10px; height: 30px; margin: 20px 0; overflow: hidden; }
        .progress-bar { background: linear-gradient(90deg, #2271b1, #5fa3d0); height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .log { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 11px; }
        .log-entry { padding: 4px 0; border-bottom: 1px solid #eee; }
        button { background: #2271b1; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
        button:hover { background: #135e96; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 20px; }
        th { background: #2271b1; color: white; padding: 8px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Synchronisation SKU depuis Compatibilité Véhicules</h1>
        
        <?php
        if ($action === 'sync') {
            // SYNCHRONISATION
            echo '<h2>⚙️ Synchronisation en cours...</h2>';
            
            $batch_size = 500;
            
            // Compter les produits WC avec compatibilité véhicule
            $total = $wpdb->get_var(
                "SELECT COUNT(DISTINCT pm_code.post_id)
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
                    WHERE vc.part_number = COALESCE(pm_new.meta_value, pm_sku.meta_value, pm_code.meta_value)
                )"
            );
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong>' . number_format($total) . '</strong> Produits avec compatibilité</div>';
            echo '<div class="stat-box"><strong>' . number_format($offset) . '</strong> Déjà traités</div>';
            echo '<div class="stat-box"><strong>' . number_format($total - $offset) . '</strong> Restants</div>';
            echo '</div>';
            
            $progress_percent = $total > 0 ? ($offset / $total) * 100 : 0;
            echo '<div class="progress">';
            echo '<div class="progress-bar" style="width: ' . esc_attr( $progress_percent ) . '%">' . esc_html( round( $progress_percent, 1 ) ) . '%</div>';
            echo '</div>';
            
            // Récupérer un batch de produits avec leur part_number
            $products = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT
                        pm_code.post_id as wc_product_id,
                        pm_code.meta_value as product_code,
                        pm_new.meta_value as new_part_number,
                        pm_sku.meta_value as current_sku,
                        vc.part_number,
                        p.post_title as name
                    FROM {$wpdb->postmeta} pm_code
                    INNER JOIN {$wpdb->posts} p ON p.ID = pm_code.post_id
                    LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = pm_code.post_id AND pm_new.meta_key = '_bihr_new_part_number'
                    LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_code.post_id AND pm_sku.meta_key = '_sku'
                    INNER JOIN {$wpdb->prefix}bihr_vehicle_compatibility vc ON vc.part_number = COALESCE(pm_new.meta_value, pm_sku.meta_value, pm_code.meta_value)
                    WHERE pm_code.meta_key = '_bihr_product_code'
                    AND pm_code.meta_value IS NOT NULL
                    AND pm_code.meta_value != ''
                    AND p.post_type = 'product'
                    GROUP BY pm_code.post_id
                    LIMIT %d OFFSET %d",
                    $batch_size,
                    $offset
                ),
                ARRAY_A
            );
            
            echo '<div class="log">';
            $sku_inserted = 0;
            $sku_updated = 0;
            $errors = 0;
            
            foreach ($products as $product) {
                $wc_product_id = $product['wc_product_id'];
                $product_code = $product['product_code'];
                $part_number = $product['part_number'];
                $new_part_number = $product['new_part_number'];
                $sku = $part_number; // Le SKU = part_number (pas product_code!)
                
                if (empty($sku)) {
                    echo '<div class="log-entry warning">⚠️ WC #' . esc_html( $wc_product_id ) . ' : part_number vide</div>';
                    $errors++;
                    continue;
                }
                
                // Synchroniser le SKU
                $existing_sku = $wpdb->get_var($wpdb->prepare("
                    SELECT meta_id 
                    FROM {$wpdb->postmeta} 
                    WHERE post_id = %d 
                    AND meta_key = '_sku'
                ", $wc_product_id));
                
                if ($existing_sku) {
                    // UPDATE
                    $wpdb->update(
                        $wpdb->postmeta,
                        array('meta_value' => $sku),
                        array('post_id' => $wc_product_id, 'meta_key' => '_sku'),
                        array('%s'),
                        array('%d', '%s')
                    );
                    echo '<div class="log-entry success">✅ WC #' . esc_html( $wc_product_id ) . ' : SKU mis à jour → ' . esc_html( $sku ) . ' (code: ' . esc_html( $product_code ) . ( $new_part_number ? ', new: ' . esc_html( $new_part_number ) : '' ) . ')</div>';
                    $sku_updated++;
                } else {
                    // INSERT
                    $result = $wpdb->insert(
                        $wpdb->postmeta,
                        array(
                            'post_id' => $wc_product_id,
                            'meta_key' => '_sku',
                            'meta_value' => $sku
                        ),
                        array('%d', '%s', '%s')
                    );
                    
                    if ($result) {
                        echo '<div class="log-entry success">✅ WC #' . esc_html( $wc_product_id ) . ' : SKU créé → ' . esc_html( $sku ) . ' (code: ' . esc_html( $product_code ) . ( $new_part_number ? ', new: ' . esc_html( $new_part_number ) : '' ) . ')</div>';
                        $sku_inserted++;
                    } else {
                        echo '<div class="log-entry error">❌ WC #' . esc_html( $wc_product_id ) . ' : Erreur INSERT</div>';
                        $errors++;
                    }
                }
                
                if (($sku_inserted + $sku_updated) % 100 == 0) {
                    flush();
                    ob_flush();
                }
            }
            
            echo '</div>';
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong class="success">' . esc_html( $sku_inserted ) . '</strong> SKU créés</div>';
            echo '<div class="stat-box"><strong class="warning">' . esc_html( $sku_updated ) . '</strong> SKU mis à jour</div>';
            echo '<div class="stat-box"><strong class="error">' . esc_html( $errors ) . '</strong> Erreurs</div>';
            echo '</div>';
            
            $new_offset = $offset + $batch_size;
            
            if ($new_offset < $total) {
                // Continuer
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
                echo '<p class="warning">⏳ Rechargement dans 1 seconde...</p>';
            } else {
                // Terminé
                echo '<h2 class="success">✅ SYNCHRONISATION TERMINÉE !</h2>';
                echo '<a href="?"><button>🔙 Retour</button></a>';
                
                // Vérif finale
                $final_sku = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''");
                echo '<p class="success">✅ Total SKU synchronisés : ' . number_format($final_sku) . '</p>';
            }
            
        } else {
            // PAGE D'ACCUEIL
            $wc_with_compatibility = $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(DISTINCT pm_code.post_id)
                    FROM {$wpdb->postmeta} pm_code
                    INNER JOIN {$wpdb->posts} p ON p.ID = pm_code.post_id
                    LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = pm_code.post_id AND pm_new.meta_key = %s
                    LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_code.post_id AND pm_sku.meta_key = %s
                    WHERE pm_code.meta_key = %s
                    AND pm_code.meta_value IS NOT NULL
                    AND pm_code.meta_value != ''
                    AND p.post_type = %s
                    AND EXISTS (
                        SELECT 1 FROM {$wpdb->prefix}bihr_vehicle_compatibility vc
                        WHERE vc.part_number = COALESCE(pm_new.meta_value, pm_sku.meta_value, pm_code.meta_value)
                    )
                    ",
                    '_bihr_new_part_number',
                    '_sku',
                    '_bihr_product_code',
                    'product'
                )
            );
            
            $wc_products = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s", 'product', 'publish' ) );
            $wc_sku_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''", '_sku' ) );
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong>' . number_format($wc_with_compatibility) . '</strong> Produits WC avec compatibilité</div>';
            echo '<div class="stat-box"><strong>' . number_format($wc_products) . '</strong> Total produits WooCommerce</div>';
            echo '<div class="stat-box"><strong class="success">' . number_format($wc_sku_count) . '</strong> SKU actuels</div>';
            echo '</div>';
            
            echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">';
            echo '<h3>⚠️ Important</h3>';
            echo '<p>Ce script synchronise les SKU en utilisant le <strong>part_number</strong> de la table de compatibilité véhicules, pas le product_code BIHR.</p>';
            echo '<p>Cela permet au filtre véhicule de fonctionner correctement car il cherche : <code>WHERE _sku = part_number</code></p>';
            echo '</div>';
            
            $sync_url = wp_nonce_url( add_query_arg( 'action', 'sync' ), $nonce_action );
            echo '<button onclick="if(confirm(\'Lancer la synchronisation de ' . esc_js( number_format( $wc_with_compatibility ) ) . ' produits ?\')) window.location.href=' . wp_json_encode( $sync_url ) . '">🚀 LANCER LA SYNCHRONISATION</button>';
            
            // Exemples
            echo '<h3>📊 Aperçu (10 premiers produits)</h3>';
            $samples = $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT 
                        pm_code.post_id as wc_id,
                        p.post_title as name,
                        pm_code.meta_value as product_code,
                        pm_new.meta_value as new_part_number,
                        pm_sku.meta_value as current_sku,
                        vc.part_number
                    FROM {$wpdb->postmeta} pm_code
                    INNER JOIN {$wpdb->posts} p ON p.ID = pm_code.post_id
                    LEFT JOIN {$wpdb->postmeta} pm_new ON pm_new.post_id = pm_code.post_id AND pm_new.meta_key = %s
                    LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_code.post_id AND pm_sku.meta_key = %s
                    LEFT JOIN {$wpdb->prefix}bihr_vehicle_compatibility vc ON vc.part_number = COALESCE(pm_new.meta_value, pm_sku.meta_value, pm_code.meta_value)
                    WHERE pm_code.meta_key = %s
                    AND pm_code.meta_value IS NOT NULL
                    AND pm_code.meta_value != ''
                    AND p.post_type = %s
                    GROUP BY pm_code.post_id
                    LIMIT 10
                    ",
                    '_bihr_new_part_number',
                    '_sku',
                    '_bihr_product_code',
                    'product'
                ),
                ARRAY_A
            );
            
            echo '<table>';
            echo '<tr><th>WC ID</th><th>Nom Produit</th><th>Code BIHR</th><th>NewPartNumber</th><th>Part Number</th><th>SKU Actuel</th><th>Statut</th></tr>';
            
            foreach ($samples as $row) {
                $current_sku = $row['current_sku'];
                $part_number = $row['part_number'];
                
                if (empty($part_number)) {
                    $status = '<span class="error">❌ Pas de part_number</span>';
                } elseif ($current_sku == $part_number) {
                    $status = '<span class="success">✅ OK</span>';
                } else {
                    $status = '<span class="warning">⚠️ SKU différent</span>';
                }
                
                echo '<tr>';
                echo '<td>' . esc_html( $row['wc_id'] ) . '</td>';
                echo '<td>' . esc_html( substr( $row['name'], 0, 40 ) ) . '...</td>';
                echo '<td>' . esc_html( $row['product_code'] ) . '</td>';
                echo '<td>' . ( ! empty( $row['new_part_number'] ) ? esc_html( $row['new_part_number'] ) : '-' ) . '</td>';
                echo '<td><strong>' . ( ! empty( $part_number ) ? esc_html( $part_number ) : '-' ) . '</strong></td>';
                echo '<td>' . ( ! empty( $current_sku ) ? esc_html( $current_sku ) : '-' ) . '</td>';
                echo '<td>' . wp_kses_post( $status ) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
    </div>
</body>
</html>
