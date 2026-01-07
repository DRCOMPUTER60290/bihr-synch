<?php
/**
 * Script de debug pour vérifier les données des véhicules
 * À placer à la racine du plugin et exécuter via URL
 */

// Charger WordPress si nécessaire
if ( ! defined( 'ABSPATH' ) ) {
    $wp_load_path = dirname( __FILE__, 3 ) . '/wp-load.php';

    if ( ! file_exists( $wp_load_path ) ) {
        exit( 'wp-load.php introuvable.' );
    }

    require_once $wp_load_path;
}

// Restreindre l'accès à l'admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Vous n\'avez pas la permission d\'accéder à ce script.', 'bihr-synch' ) );
}

global $wpdb;

$vehicles_table = $wpdb->prefix . 'bihr_vehicles';
$compatibility_table = $wpdb->prefix . 'bihr_vehicle_compatibility';

echo "<h1>🔍 Debug Filtre Véhicule</h1>";

// 1. Vérifier si les tables existent
echo "<h2>1. Tables de base de données</h2>";
$tables = $wpdb->get_results( $wpdb->prepare( "SHOW TABLES LIKE %s", $vehicles_table ) );
echo '<p>Table véhicules (' . esc_html( $vehicles_table ) . '): ' . ( $tables ? '✅ Existe' : '❌ N\'existe pas' ) . '</p>';

$tables2 = $wpdb->get_results( $wpdb->prepare( "SHOW TABLES LIKE %s", $compatibility_table ) );
echo '<p>Table compatibilités (' . esc_html( $compatibility_table ) . '): ' . ( $tables2 ? '✅ Existe' : '❌ N\'existe pas' ) . '</p>';

// 2. Compter les véhicules
echo "<h2>2. Nombre de véhicules</h2>";
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$vehicles_table}" );
echo '<p>Total véhicules: <strong>' . esc_html( $count ) . '</strong></p>';

// 3. Compter les compatibilités
$count_compat = $wpdb->get_var( "SELECT COUNT(*) FROM {$compatibility_table}" );
echo '<p>Total compatibilités: <strong>' . esc_html( $count_compat ) . '</strong></p>';

// 4. Tester la requête des fabricants
echo "<h2>3. Requête Fabricants (utilisée par le filtre)</h2>";
$manufacturers = $wpdb->get_results(
    "SELECT DISTINCT manufacturer_code, manufacturer_name 
     FROM {$vehicles_table} 
     WHERE manufacturer_name IS NOT NULL AND manufacturer_name != ''
     ORDER BY manufacturer_name ASC",
    ARRAY_A
);

echo "<p>Nombre de fabricants trouvés: <strong>" . count( $manufacturers ) . "</strong></p>";

if ( ! empty( $manufacturers ) ) {
    echo "<h3>Liste des fabricants:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Code</th><th>Nom</th></tr>";
    foreach ( $manufacturers as $manu ) {
        echo "<tr>";
        echo "<td>" . esc_html( $manu['manufacturer_code'] ) . "</td>";
        echo "<td>" . esc_html( $manu['manufacturer_name'] ) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ Aucun fabricant trouvé!</p>";
    
    // Regarder un exemple de ligne
    echo "<h3>Exemple d'une ligne de la table véhicules:</h3>";
    $sample = $wpdb->get_row( "SELECT * FROM {$vehicles_table} LIMIT 1", ARRAY_A );
    if ( $sample ) {
        echo '<pre>' . esc_html( print_r( $sample, true ) ) . '</pre>';
    } else {
        echo "<p>Table vide</p>";
    }
}

// 5. Vérifier les nonces et AJAX
echo "<h2>4. Configuration AJAX</h2>";
echo '<p>AJAX URL: <code>' . esc_url( admin_url( 'admin-ajax.php' ) ) . '</code></p>';
echo "<p>Actions enregistrées:</p>";
echo "<ul>";
echo "<li>wp_ajax_bihr_get_manufacturers</li>";
echo "<li>wp_ajax_nopriv_bihr_get_manufacturers</li>";
echo "</ul>";

// 6. Test direct de l'action AJAX (simulation)
echo "<h2>5. Test simulation AJAX</h2>";
$_POST['action'] = 'bihr_get_manufacturers';
$_POST['nonce'] = wp_create_nonce( 'bihr_vehicle_filter_nonce' );

echo '<p>Nonce créé: <code>' . esc_html( $_POST['nonce'] ) . '</code></p>';
echo "<p>Essayez ce code JavaScript dans la console du navigateur:</p>";
echo "<pre>";
echo "jQuery.ajax({
    url: '" . esc_url( admin_url( 'admin-ajax.php' ) ) . "',
    method: 'POST',
    data: {
        action: 'bihr_get_manufacturers',
        nonce: '" . esc_js( wp_create_nonce( 'bihr_vehicle_filter_nonce' ) ) . "'
    },
    success: function(response) {
        console.log('Réponse:', response);
    },
    error: function(xhr, status, error) {
        console.error('Erreur:', error);
    }
});
</pre>";

// 7. Vérifier si la classe est chargée
echo "<h2>6. Classe BihrWI_Vehicle_Filter</h2>";
if ( class_exists( 'BihrWI_Vehicle_Filter' ) ) {
    echo "<p>✅ Classe chargée</p>";
    
    // Vérifier si les hooks sont enregistrés
    global $wp_filter;
    $has_ajax = isset( $wp_filter['wp_ajax_bihr_get_manufacturers'] );
    $has_ajax_nopriv = isset( $wp_filter['wp_ajax_nopriv_bihr_get_manufacturers'] );
    
    echo "<p>Hook wp_ajax_bihr_get_manufacturers: " . ( $has_ajax ? '✅' : '❌' ) . "</p>";
    echo "<p>Hook wp_ajax_nopriv_bihr_get_manufacturers: " . ( $has_ajax_nopriv ? '✅' : '❌' ) . "</p>";
} else {
    echo "<p style='color:red;'>❌ Classe non chargée</p>";
}

echo "<hr>";
echo "<p><strong>Prochaines étapes:</strong></p>";
echo "<ol>";
echo "<li>Si les tables sont vides → Réimporter VehiclesList.csv</li>";
echo "<li>Si les fabricants sont vides → Problème dans les données CSV</li>";
echo "<li>Si les hooks ne sont pas enregistrés → Vérifier que le plugin est bien initialisé</li>";
echo "<li>Tester l'AJAX dans la console du navigateur avec le code fourni</li>";
echo "</ol>";
