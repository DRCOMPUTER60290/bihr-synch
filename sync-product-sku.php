<?php
/**
 * Script de synchronisation des SKU - Version 2.0
 * 
 * Lie les produits BIHR aux produits WooCommerce et synchronise les SKU
 * 
 * Étapes:
 * 1. Trouve les produits WooCommerce par nom
 * 2. Met à jour product_id dans wp_bihr_products
 * 3. Ajoute/met à jour le SKU (new_part_number) dans wp_postmeta
 */

// Charger WordPress
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('❌ Impossible de charger WordPress');
}

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    die('❌ Accès refusé. Vous devez être administrateur.');
}

global $wpdb;
set_time_limit(0);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Synchronisation des SKU</title>
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
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #2271b1; color: white; padding: 8px; text-align: left; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Synchronisation des SKU - Produits BIHR → WooCommerce</h1>
        
        <?php
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        if ($action == 'sync') {
            // SYNCHRONISATION
            echo '<h2>⚙️ Synchronisation en cours...</h2>';
            
            $batch_size = 500;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            // Compter le total
            $total = $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->prefix}bihr_products 
                WHERE new_part_number IS NOT NULL 
                AND new_part_number != ''
            ");
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong>' . number_format($total) . '</strong> Produits à traiter</div>';
            echo '<div class="stat-box"><strong>' . number_format($offset) . '</strong> Déjà traités</div>';
            echo '<div class="stat-box"><strong>' . number_format($total - $offset) . '</strong> Restants</div>';
            echo '</div>';
            
            $progress_percent = $total > 0 ? ($offset / $total) * 100 : 0;
            echo '<div class="progress">';
            echo '<div class="progress-bar" style="width: ' . $progress_percent . '%">' . round($progress_percent, 1) . '%</div>';
            echo '</div>';
            
            // Récupérer un batch
            $products = $wpdb->get_results($wpdb->prepare("
                SELECT id as bihr_id, product_id, product_code, new_part_number, name
                FROM {$wpdb->prefix}bihr_products
                WHERE new_part_number IS NOT NULL 
                AND new_part_number != ''
                LIMIT %d OFFSET %d
            ", $batch_size, $offset), ARRAY_A);
            
            echo '<div class="log">';
            $linked = 0;
            $sku_inserted = 0;
            $sku_updated = 0;
            $not_found = 0;
            $errors = 0;
            
            foreach ($products as $product) {
                $bihr_id = $product['bihr_id'];
                $sku = $product['new_part_number'];
                $product_code = $product['product_code'];
                $current_product_id = $product['product_id'];
                
                // Étape 1: Trouver le produit WooCommerce
                $wc_product_id = null;
                
                if (!empty($current_product_id) && is_numeric($current_product_id)) {
                    $wc_product_id = intval($current_product_id);
                } else {
                    // Chercher par Code BIHR dans postmeta
                    $wc_product_id = $wpdb->get_var($wpdb->prepare("
                        SELECT post_id 
                        FROM {$wpdb->postmeta} 
                        WHERE meta_key = '_bihr_code' 
                        AND meta_value = %s 
                        LIMIT 1
                    ", $product_code));
                    
                    if ($wc_product_id) {
                        // Mettre à jour le lien
                        $wpdb->update(
                            $wpdb->prefix . 'bihr_products',
                            array('product_id' => $wc_product_id),
                            array('id' => $bihr_id),
                            array('%d'),
                            array('%d')
                        );
                        $linked++;
                    }
                }
                
                if (!$wc_product_id) {
                    echo '<div class="log-entry warning">⚠️ BIHR #' . $bihr_id . ' : Produit WC introuvable</div>';
                    $not_found++;
                    continue;
                }
                
                // Étape 2: Synchroniser le SKU
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
                    echo '<div class="log-entry success">✅ WC #' . $wc_product_id . ' ← BIHR #' . $bihr_id . ' : SKU → ' . $sku . '</div>';
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
                        echo '<div class="log-entry success">✅ WC #' . $wc_product_id . ' ← BIHR #' . $bihr_id . ' : SKU créé → ' . $sku . '</div>';
                        $sku_inserted++;
                    } else {
                        echo '<div class="log-entry error">❌ WC #' . $wc_product_id . ' : Erreur INSERT</div>';
                        $errors++;
                    }
                }
                
                if (($linked + $sku_inserted + $sku_updated + $not_found) % 100 == 0) {
                    flush();
                    ob_flush();
                }
            }
            
            echo '</div>';
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong class="success">' . $linked . '</strong> Produits liés</div>';
            echo '<div class="stat-box"><strong class="success">' . $sku_inserted . '</strong> SKU créés</div>';
            echo '<div class="stat-box"><strong class="warning">' . $sku_updated . '</strong> SKU mis à jour</div>';
            echo '<div class="stat-box"><strong class="error">' . $not_found . '</strong> Non trouvés</div>';
            echo '</div>';
            
            $new_offset = $offset + $batch_size;
            
            if ($new_offset < $total) {
                // Continuer
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "?action=sync&offset=' . $new_offset . '";
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
            $bihr_total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bihr_products WHERE new_part_number IS NOT NULL AND new_part_number != ''");
            $wc_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
            $wc_sku_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''");
            $missing = $bihr_total - $wc_sku_count;
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong>' . number_format($bihr_total) . '</strong> Produits BIHR</div>';
            echo '<div class="stat-box"><strong>' . number_format($wc_products) . '</strong> Produits WooCommerce</div>';
            echo '<div class="stat-box"><strong class="success">' . number_format($wc_sku_count) . '</strong> SKU actuels</div>';
            echo '<div class="stat-box"><strong class="error">' . number_format($missing) . '</strong> SKU manquants</div>';
            echo '</div>';
            
            if ($missing > 0 || $wc_sku_count == 0) {
                echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">';
                echo '<h3>⚠️ Action requise</h3>';
                echo '<p>Ce script va lier les produits BIHR aux produits WooCommerce via leur nom, puis synchroniser les SKU.</p>';
                echo '</div>';
                
                echo '<button onclick="if(confirm(\'Lancer la synchronisation de ' . number_format($bihr_total) . ' produits ?\')) window.location.href=\'?action=sync\'">🚀 LANCER LA SYNCHRONISATION</button>';
            } else {
                echo '<div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;">';
                echo '<h3 class="success">✅ Tous les SKU sont synchronisés !</h3>';
                echo '</div>';
            }
            
            // Exemples
            echo '<h3>📊 Aperçu (10 premiers produits)</h3>';
            $samples = $wpdb->get_results("
                SELECT bp.id as bihr_id, bp.product_id, bp.new_part_number, bp.name,
                       wcp.ID as wc_id, pm.meta_value as current_sku
                FROM {$wpdb->prefix}bihr_products bp
                LEFT JOIN {$wpdb->posts} wcp ON wcp.post_title = bp.name AND wcp.post_type = 'product'
                LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = wcp.ID AND pm.meta_key = '_sku'
                WHERE bp.new_part_number IS NOT NULL AND bp.new_part_number != ''
                LIMIT 10
            ", ARRAY_A);
            
            echo '<table>';
            echo '<tr><th>BIHR ID</th><th>Nom Produit</th><th>SKU (new_part_number)</th><th>WC ID</th><th>SKU Actuel</th><th>Statut</th></tr>';
            
            foreach ($samples as $row) {
                $has_wc = !empty($row['wc_id']);
                $has_sku = !empty($row['current_sku']);
                
                if (!$has_wc) {
                    $status = '<span class="error">❌ Pas de produit WC</span>';
                } elseif (!$has_sku) {
                    $status = '<span class="warning">⚠️ Manque SKU</span>';
                } else {
                    $status = '<span class="success">✅ OK</span>';
                }
                
                echo '<tr>';
                echo '<td>' . $row['bihr_id'] . '</td>';
                echo '<td>' . substr($row['name'], 0, 50) . '...</td>';
                echo '<td><strong>' . $row['new_part_number'] . '</strong></td>';
                echo '<td>' . ($row['wc_id'] ?: '-') . '</td>';
                echo '<td>' . ($row['current_sku'] ?: '-') . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
    </div>
</body>
</html>
