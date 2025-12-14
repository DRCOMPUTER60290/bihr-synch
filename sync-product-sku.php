<?php
/**
 * Script de synchronisation des SKU
 * 
 * Ce script synchronise les article_number de wp_bihr_products
 * vers les SKU des produits WooCommerce (wp_postmeta)
 * 
 * Exécution : https://votresite.com/wp-content/plugins/BIHR-SYNCH-main/sync-product-sku.php
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
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #2271b1; 
            border-bottom: 3px solid #2271b1;
            padding-bottom: 10px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f0f6fc;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2271b1;
        }
        .stat-box strong {
            display: block;
            font-size: 24px;
            color: #2271b1;
        }
        .success { color: #00a32a; }
        .warning { color: #dba617; }
        .error { color: #d63638; }
        .progress {
            background: #e0e0e0;
            border-radius: 10px;
            height: 30px;
            margin: 20px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #2271b1, #5fa3d0);
            height: 100%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .log {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 5px;
        }
        button:hover {
            background: #135e96;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Synchronisation des SKU - Produits BIHR</h1>
        
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
                WHERE product_id IS NOT NULL 
                AND product_id > 0
            ");
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong>' . number_format($total) . '</strong> Produits à synchroniser</div>';
            echo '<div class="stat-box"><strong>' . number_format($offset) . '</strong> Déjà traités</div>';
            echo '<div class="stat-box"><strong>' . number_format($total - $offset) . '</strong> Restants</div>';
            echo '</div>';
            
            $progress_percent = $total > 0 ? ($offset / $total) * 100 : 0;
            echo '<div class="progress">';
            echo '<div class="progress-bar" style="width: ' . $progress_percent . '%">' . round($progress_percent, 1) . '%</div>';
            echo '</div>';
            
            // Récupérer un batch
            $products = $wpdb->get_results($wpdb->prepare("
                SELECT product_id, article_number, new_part_number, ean13_code
                FROM {$wpdb->prefix}bihr_products
                WHERE product_id IS NOT NULL 
                AND product_id > 0
                LIMIT %d OFFSET %d
            ", $batch_size, $offset), ARRAY_A);
            
            echo '<div class="log">';
            $updated = 0;
            $inserted = 0;
            $errors = 0;
            
            foreach ($products as $product) {
                $product_id = $product['product_id'];
                $sku = !empty($product['new_part_number']) ? $product['new_part_number'] : $product['article_number'];
                
                if (empty($sku)) {
                    echo '<div class="log-entry error">❌ Produit #' . $product_id . ' : SKU vide</div>';
                    $errors++;
                    continue;
                }
                
                // Vérifier si le SKU existe déjà
                $existing = $wpdb->get_var($wpdb->prepare("
                    SELECT meta_id 
                    FROM {$wpdb->postmeta} 
                    WHERE post_id = %d 
                    AND meta_key = '_sku'
                ", $product_id));
                
                if ($existing) {
                    // UPDATE
                    $result = $wpdb->update(
                        $wpdb->postmeta,
                        array('meta_value' => $sku),
                        array('post_id' => $product_id, 'meta_key' => '_sku'),
                        array('%s'),
                        array('%d', '%s')
                    );
                    
                    if ($result !== false) {
                        echo '<div class="log-entry success">✅ Produit #' . $product_id . ' : SKU mis à jour → ' . $sku . '</div>';
                        $updated++;
                    } else {
                        echo '<div class="log-entry error">❌ Produit #' . $product_id . ' : Erreur UPDATE</div>';
                        $errors++;
                    }
                } else {
                    // INSERT
                    $result = $wpdb->insert(
                        $wpdb->postmeta,
                        array(
                            'post_id' => $product_id,
                            'meta_key' => '_sku',
                            'meta_value' => $sku
                        ),
                        array('%d', '%s', '%s')
                    );
                    
                    if ($result) {
                        echo '<div class="log-entry success">✅ Produit #' . $product_id . ' : SKU créé → ' . $sku . '</div>';
                        $inserted++;
                    } else {
                        echo '<div class="log-entry error">❌ Produit #' . $product_id . ' : Erreur INSERT</div>';
                        $errors++;
                    }
                }
                
                // Flush pour affichage en temps réel
                if ($updated + $inserted + $errors % 50 == 0) {
                    flush();
                    ob_flush();
                }
            }
            
            echo '</div>';
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong class="success">' . $inserted . '</strong> SKU créés</div>';
            echo '<div class="stat-box"><strong class="warning">' . $updated . '</strong> SKU mis à jour</div>';
            echo '<div class="stat-box"><strong class="error">' . $errors . '</strong> Erreurs</div>';
            echo '</div>';
            
            $new_offset = $offset + $batch_size;
            
            if ($new_offset < $total) {
                // Continuer
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "?action=sync&offset=' . $new_offset . '";
                    }, 1000);
                </script>';
                echo '<p class="warning">⏳ Batch terminé. Rechargement automatique dans 1 seconde...</p>';
            } else {
                // Terminé
                echo '<h2 class="success">✅ SYNCHRONISATION TERMINÉE !</h2>';
                echo '<p>Total : <strong>' . number_format($inserted + $updated) . '</strong> SKU synchronisés</p>';
                echo '<a href="?"><button>🔙 Retour</button></a>';
                
                // Vérification finale
                $sku_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''");
                echo '<p class="success">✅ Vérification : ' . number_format($sku_count) . ' produits ont maintenant un SKU</p>';
            }
            
        } else {
            // PAGE D'ACCUEIL
            $bihr_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bihr_products WHERE product_id IS NOT NULL AND product_id > 0");
            $wc_sku_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value != ''");
            $missing = $bihr_products - $wc_sku_count;
            
            echo '<div class="stats">';
            echo '<div class="stat-box"><strong>' . number_format($bihr_products) . '</strong> Produits BIHR importés</div>';
            echo '<div class="stat-box"><strong class="success">' . number_format($wc_sku_count) . '</strong> SKU WooCommerce</div>';
            echo '<div class="stat-box"><strong class="error">' . number_format($missing) . '</strong> SKU manquants</div>';
            echo '</div>';
            
            if ($missing > 0) {
                echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">';
                echo '<h3>⚠️ Action requise</h3>';
                echo '<p>Il manque <strong>' . number_format($missing) . '</strong> SKU dans WooCommerce.</p>';
                echo '<p>Ce script va synchroniser les article_number/new_part_number de wp_bihr_products vers les SKU WooCommerce.</p>';
                echo '</div>';
                
                echo '<button onclick="if(confirm(\'Lancer la synchronisation de ' . number_format($missing) . ' SKU ?\')) window.location.href=\'?action=sync\'">🚀 LANCER LA SYNCHRONISATION</button>';
            } else {
                echo '<div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;">';
                echo '<h3 class="success">✅ Tous les SKU sont synchronisés !</h3>';
                echo '<p>Aucune action nécessaire.</p>';
                echo '</div>';
            }
            
            // Exemple de correspondance
            echo '<h3>📊 Exemple de produits</h3>';
            $samples = $wpdb->get_results("
                SELECT p.product_id, p.article_number, p.new_part_number, pm.meta_value as current_sku
                FROM {$wpdb->prefix}bihr_products p
                LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.product_id AND pm.meta_key = '_sku'
                WHERE p.product_id IS NOT NULL AND p.product_id > 0
                LIMIT 10
            ", ARRAY_A);
            
            echo '<table style="width:100%; border-collapse: collapse;">';
            echo '<tr style="background: #2271b1; color: white;">
                    <th style="padding:10px; text-align:left;">Product ID</th>
                    <th style="padding:10px; text-align:left;">Article Number</th>
                    <th style="padding:10px; text-align:left;">New Part Number</th>
                    <th style="padding:10px; text-align:left;">SKU Actuel</th>
                  </tr>';
            
            foreach ($samples as $row) {
                $status = empty($row['current_sku']) ? '<span class="error">❌ Manquant</span>' : '<span class="success">✅ OK</span>';
                echo '<tr style="border-bottom: 1px solid #ddd;">';
                echo '<td style="padding:8px;">' . $row['product_id'] . '</td>';
                echo '<td style="padding:8px;">' . $row['article_number'] . '</td>';
                echo '<td style="padding:8px;">' . $row['new_part_number'] . '</td>';
                echo '<td style="padding:8px;">' . ($row['current_sku'] ?: '-') . ' ' . $status . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
    </div>
</body>
</html>
