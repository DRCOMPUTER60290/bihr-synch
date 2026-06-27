<?php
if ( ! defined( 'BIHRWI_TOOL_DEBUG_VEHICLE' ) ) {
    exit;
}

global $wpdb;

$vehicles_table       = $wpdb->prefix . 'bihr_vehicles';
$compatibility_table  = $wpdb->prefix . 'bihr_vehicle_compatibility';
$products_table       = $wpdb->prefix . 'bihr_products';
?>
<style>
.bihr-debug-page h1 { color: #2271b1; border-bottom: 3px solid #2271b1; padding-bottom: 10px; }
.bihr-debug-page h2 { color: #135e96; margin-top: 30px; }
.bihr-debug-page .ok { color: #00a32a; font-weight: bold; }
.bihr-debug-page .ko { color: #d63638; font-weight: bold; }
.bihr-debug-page table { width: 100%; border-collapse: collapse; margin: 15px 0; }
.bihr-debug-page th { background: #2271b1; color: white; padding: 8px; text-align: left; }
.bihr-debug-page td { padding: 6px; border-bottom: 1px solid #ddd; }
.bihr-debug-page tr:hover { background: #f0f6fc; }
.bihr-debug-page .summary { background: #f0f6fc; padding: 15px; border-radius: 5px; border-left: 4px solid #2271b1; margin: 15px 0; }
</style>

<div class="wrap bihr-debug-page">
    <h1><?php esc_html_e( 'Debug Filtre Véhicule', 'bihr-synch' ); ?></h1>

    <h2><?php esc_html_e( '1. Tables de base de données', 'bihr-synch' ); ?></h2>
    <?php
    $tables = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', $vehicles_table ) );
    echo '<p>' . esc_html( $vehicles_table ) . ' : <span class="' . ( $tables ? 'ok' : 'ko' ) . '">' . ( $tables ? '✅ Existe' : '❌ N\'existe pas' ) . '</span></p>';

    $tables2 = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', $compatibility_table ) );
    echo '<p>' . esc_html( $compatibility_table ) . ' : <span class="' . ( $tables2 ? 'ok' : 'ko' ) . '">' . ( $tables2 ? '✅ Existe' : '❌ N\'existe pas' ) . '</span></p>';
    ?>

    <h2><?php esc_html_e( '2. Nombre d\'enregistrements', 'bihr-synch' ); ?></h2>
    <?php
    $count_vehicles = $wpdb->get_var( "SELECT COUNT(*) FROM {$vehicles_table}" );
    $count_compat   = $wpdb->get_var( "SELECT COUNT(*) FROM {$compatibility_table}" );
    $count_products = $wpdb->get_var( "SELECT COUNT(*) FROM {$products_table}" );
    ?>
    <div class="summary">
        <p><?php printf( esc_html__( 'Véhicules: %s', 'bihr-synch' ), '<strong>' . esc_html( number_format( $count_vehicles ) ) . '</strong>' ); ?></p>
        <p><?php printf( esc_html__( 'Compatibilités: %s', 'bihr-synch' ), '<strong>' . esc_html( number_format( $count_compat ) ) . '</strong>' ); ?></p>
        <p><?php printf( esc_html__( 'Produits BIHR: %s', 'bihr-synch' ), '<strong>' . esc_html( number_format( $count_products ) ) . '</strong>' ); ?></p>
    </div>

    <h2><?php esc_html_e( '3. Fabricants disponibles', 'bihr-synch' ); ?></h2>
    <?php
    $manufacturers = $wpdb->get_results(
        "SELECT DISTINCT manufacturer_code, manufacturer_name FROM {$vehicles_table} ORDER BY manufacturer_name LIMIT 50"
    );
    if ( $manufacturers ) {
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>' . esc_html__( 'Code', 'bihr-synch' ) . '</th><th>' . esc_html__( 'Nom', 'bihr-synch' ) . '</th></tr></thead><tbody>';
        foreach ( $manufacturers as $m ) {
            echo '<tr><td>' . esc_html( $m->manufacturer_code ) . '</td><td><strong>' . esc_html( $m->manufacturer_name ) . '</strong></td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p class="ko">' . esc_html__( 'Aucun fabricant trouvé', 'bihr-synch' ) . '</p>';
    }
    ?>

    <h2><?php esc_html_e( '4. Test requête AJAX', 'bihr-synch' ); ?></h2>
    <?php
    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'bihr_vehicle_filter_nonce' );
    ?>
    <p><?php esc_html_e( 'URL AJAX:', 'bihr-synch' ); ?> <code><?php echo esc_url( $ajax_url ); ?></code></p>
    <p><?php esc_html_e( 'Nonce:', 'bihr-synch' ); ?> <code><?php echo esc_html( $nonce ); ?></code></p>
    <p>
        <button class="button button-primary" onclick="bihrDebugTestAjax()">
            <?php esc_html_e( 'Tester la récupération des fabricants', 'bihr-synch' ); ?>
        </button>
    </p>
    <pre id="bihr-debug-ajax-result" style="background: #f0f0f0; padding: 15px; border-radius: 5px; min-height: 50px; max-height: 400px; overflow: auto;"><?php esc_html_e( 'Cliquez sur le bouton pour tester...', 'bihr-synch' ); ?></pre>

    <script>
    function bihrDebugTestAjax() {
        var resultEl = document.getElementById('bihr-debug-ajax-result');
        resultEl.textContent = '<?php echo esc_js( __( 'Chargement...', 'bihr-synch' ) ); ?>';
        
        var data = new FormData();
        data.append('action', 'bihr_get_manufacturers');
        data.append('_ajax_nonce', '<?php echo esc_js( $nonce ); ?>');
        data.append('nonce', '<?php echo esc_js( $nonce ); ?>');

        fetch('<?php echo esc_url( $ajax_url ); ?>', {
            method: 'POST',
            body: data
        })
        .then(function(r) { return r.text(); })
        .then(function(text) {
            try {
                var json = JSON.parse(text);
                resultEl.textContent = JSON.stringify(json, null, 2);
            } catch(e) {
                resultEl.textContent = '<?php echo esc_js( __( 'Réponse non-JSON:', 'bihr-synch' ) ); ?> ' + text;
            }
        })
        .catch(function(err) {
            resultEl.textContent = '<?php echo esc_js( __( 'Erreur:', 'bihr-synch' ) ); ?> ' + err;
        });
    }
    </script>

    <h2><?php esc_html_e( '5. Schéma des tables', 'bihr-synch' ); ?></h2>
    <?php
    $columns = $wpdb->get_results( "SHOW COLUMNS FROM {$vehicles_table}" );
    if ( $columns ) {
        echo '<h4>' . esc_html( $vehicles_table ) . '</h4>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>' . esc_html__( 'Colonne', 'bihr-synch' ) . '</th><th>' . esc_html__( 'Type', 'bihr-synch' ) . '</th><th>' . esc_html__( 'Null', 'bihr-synch' ) . '</th><th>' . esc_html__( 'Clé', 'bihr-synch' ) . '</th><th>' . esc_html__( 'Défaut', 'bihr-synch' ) . '</th></tr></thead><tbody>';
        foreach ( $columns as $col ) {
            echo '<tr><td>' . esc_html( $col->Field ) . '</td><td>' . esc_html( $col->Type ) . '</td><td>' . esc_html( $col->Null ) . '</td><td>' . esc_html( $col->Key ) . '</td><td>' . esc_html( $col->Default ?? '' ) . '</td></tr>';
        }
        echo '</tbody></table>';
    }
    ?>
</div>
