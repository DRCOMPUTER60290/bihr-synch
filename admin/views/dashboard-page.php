<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Récupérer le statut de débutant
$user_id = get_current_user_id();
$is_beginner_mode = get_user_meta( $user_id, '_bihr_beginner_mode', true );
if ( $is_beginner_mode === '' ) {
    $is_beginner_mode = true; // Défaut: mode débutant
}

// Récupérer les infos d'authentification
$auth_token = get_option( 'bihrwi_api_token' );
$is_authenticated = ! empty( $auth_token );
$show_auth_success = isset( $_GET['bihrwi_auth_success'] );

// Récupérer les statistiques
$products_count = wp_count_posts( 'product' )->publish;
$orders_count = wp_count_posts( 'shop_order' )->publish;
$synced_products = (int) get_option( 'bihrwi_synced_products_count', 0 );
?>

<div class="wrap bihr-dashboard">
    <?php if ( $show_auth_success ) : ?>
    <div class="notice notice-success is-dismissible" style="margin-top:16px;">
        <p><strong>Connexion BIHR réussie.</strong> Vous êtes maintenant connecté.</p>
    </div>
    <?php endif; ?>

    <!-- Header avec toggle mode -->
    <div class="bihr-header">
        <h1>🚀 <?php esc_html_e( 'BIHR WooCommerce Importer', 'bihr-woocommerce-importer' ); ?></h1>
        
        <div class="bihr-mode-toggle">
            <label>
                <input type="checkbox" id="bihr_beginner_mode" <?php checked( $is_beginner_mode, true ); ?>>
                <span class="toggle-label">
                    <?php echo $is_beginner_mode ? '🎓 Mode Débutant' : '⚙️ Mode Expert'; ?>
                </span>
            </label>
        </div>
    </div>

    <!-- Bienvenue pour nouveaux utilisateurs -->
    <?php if ( ! $is_authenticated ) : ?>
    <div class="bihr-welcome-banner">
        <div class="welcome-content">
            <h2>👋 Bienvenue dans BIHR WooCommerce Importer !</h2>
            <p>Connectez-vous à l'API BIHR pour commencer à synchroniser vos produits et commandes.</p>
            <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-auth', admin_url( 'admin.php' ) ) ); ?>" class="button button-primary button-large">
                🔐 Se connecter à BIHR
            </a>
        </div>
        <div class="welcome-icon">🔌</div>
    </div>
    <?php else : ?>
    <div class="bihr-connected-banner">
        <div class="welcome-content">
            <h2>✅ Connecté à BIHR</h2>
            <p>Vous êtes authentifié. Vous pouvez importer et synchroniser.</p>
            <a class="button button-success button-large" aria-disabled="true">✅ Connecté</a>
        </div>
        <div class="welcome-icon">🔗</div>
    </div>
    <?php endif; ?>

    <!-- Statut du plugin -->
    <div class="bihr-status-cards">
        <!-- Authentification -->
        <div class="status-card <?php echo $is_authenticated ? 'status-ok' : 'status-warning'; ?>">
            <div class="status-icon"><?php echo $is_authenticated ? '✅' : '⚠️'; ?></div>
            <div class="status-content">
                <h3><?php esc_html_e( 'Authentification', 'bihr-woocommerce-importer' ); ?></h3>
                <p>
                    <?php 
                    if ( $is_authenticated ) {
                        esc_html_e( 'Connecté à l\'API BIHR', 'bihr-woocommerce-importer' );
                    } else {
                        esc_html_e( 'Non connecté', 'bihr-woocommerce-importer' );
                    }
                    ?>
                </p>
            </div>
            <?php if ( $is_authenticated ) : ?>
            <div class="status-actions">
                <a class="status-action button-success" aria-disabled="true">✅ <?php esc_html_e( 'Connecté', 'bihr-woocommerce-importer' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-auth', admin_url( 'admin.php' ) ) ); ?>" class="status-action">
                    <?php esc_html_e( 'Modifier', 'bihr-woocommerce-importer' ); ?>
                </a>
            </div>
            <?php else : ?>
            <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-auth', admin_url( 'admin.php' ) ) ); ?>" class="status-action button-primary">
                <?php esc_html_e( 'Se connecter', 'bihr-woocommerce-importer' ); ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Produits -->
        <div class="status-card status-info">
            <div class="status-icon">📦</div>
            <div class="status-content">
                <h3><?php esc_html_e( 'Produits', 'bihr-woocommerce-importer' ); ?></h3>
                <p>
                    <strong><?php echo intval( $synced_products ); ?></strong> / <?php echo intval( $products_count ); ?> 
                    <?php esc_html_e( 'produits BIHR', 'bihr-woocommerce-importer' ); ?>
                </p>
            </div>
            <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-products', admin_url( 'admin.php' ) ) ); ?>" class="status-action">
                <?php esc_html_e( 'Gérer', 'bihr-woocommerce-importer' ); ?>
            </a>
        </div>

        <!-- Commandes -->
        <div class="status-card status-info">
            <div class="status-icon">🛒</div>
            <div class="status-content">
                <h3><?php esc_html_e( 'Commandes', 'bihr-woocommerce-importer' ); ?></h3>
                <p>
                    <strong><?php echo intval( $orders_count ); ?></strong> 
                    <?php esc_html_e( 'commandes actives', 'bihr-woocommerce-importer' ); ?>
                </p>
            </div>
            <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-orders-settings', admin_url( 'admin.php' ) ) ); ?>" class="status-action">
                <?php esc_html_e( 'Voir', 'bihr-woocommerce-importer' ); ?>
            </a>
        </div>
    </div>

    <!-- Section débutants -->
    <div class="bihr-beginner-section" <?php echo ! $is_beginner_mode ? 'style="display:none;"' : ''; ?>>
        <h2>🎯 Commencer en 3 étapes</h2>
        
        <div class="beginner-steps">
            <!-- Étape 1 -->
            <div class="step-card step-1 <?php echo $is_authenticated ? 'step-completed' : 'step-active'; ?>">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3><?php esc_html_e( 'Se connecter à BIHR', 'bihr-woocommerce-importer' ); ?></h3>
                    <p><?php esc_html_e( 'Authentifiez-vous avec vos identifiants BIHR pour accéder à vos produits.', 'bihr-woocommerce-importer' ); ?></p>
                    <?php if ( ! $is_authenticated ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-auth', admin_url( 'admin.php' ) ) ); ?>" class="button button-primary">
                        🔐 <?php esc_html_e( 'Se connecter', 'bihr-woocommerce-importer' ); ?>
                    </a>
                    <?php else : ?>
                    <a class="button button-success" aria-disabled="true">✅ <?php esc_html_e( 'Connecté', 'bihr-woocommerce-importer' ); ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Étape 2 -->
            <div class="step-card step-2 <?php echo $is_authenticated ? 'step-active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3><?php esc_html_e( 'Importer les produits', 'bihr-woocommerce-importer' ); ?></h3>
                    <p><?php esc_html_e( 'Téléchargez le catalogue BIHR dans WooCommerce en quelques clics.', 'bihr-woocommerce-importer' ); ?></p>
                    <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-products', admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary" <?php echo ! $is_authenticated ? 'disabled' : ''; ?>>
                        📥 <?php esc_html_e( 'Importer', 'bihr-woocommerce-importer' ); ?>
                    </a>
                </div>
            </div>

            <!-- Étape 3 -->
            <div class="step-card step-3">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3><?php esc_html_e( 'Configurer les synchronisations', 'bihr-woocommerce-importer' ); ?></h3>
                    <p><?php esc_html_e( 'Activez la synchronisation automatique des stocks et commandes.', 'bihr-woocommerce-importer' ); ?></p>
                    <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-orders-settings', admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary">
                        ⚙️ <?php esc_html_e( 'Configurer', 'bihr-woocommerce-importer' ); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu principal simplifié (mode débutant) -->
    <div class="bihr-quick-actions" <?php echo ! $is_beginner_mode ? 'style="display:none;"' : ''; ?>>
        <h2>📋 Actions Rapides</h2>
        <div class="actions-grid">
            <div class="action-card">
                <div class="action-icon">🔐</div>
                <h3><?php esc_html_e( 'Authentification', 'bihr-woocommerce-importer' ); ?></h3>
                <p><?php esc_html_e( 'Gérer votre connexion BIHR', 'bihr-woocommerce-importer' ); ?></p>
                <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-auth', admin_url( 'admin.php' ) ) ); ?>" class="button">
                    <?php esc_html_e( 'Ouvrir', 'bihr-woocommerce-importer' ); ?>
                </a>
            </div>

            <div class="action-card">
                <div class="action-icon">📥</div>
                <h3><?php esc_html_e( 'Importer Produits', 'bihr-woocommerce-importer' ); ?></h3>
                <p><?php esc_html_e( 'Importer le catalogue BIHR', 'bihr-woocommerce-importer' ); ?></p>
                <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-products', admin_url( 'admin.php' ) ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Importer', 'bihr-woocommerce-importer' ); ?>
                </a>
            </div>

            <div class="action-card">
                <div class="action-icon">🚗</div>
                <h3><?php esc_html_e( 'Compatibilités Véhicules', 'bihr-woocommerce-importer' ); ?></h3>
                <p><?php esc_html_e( 'Gérer les filtres par véhicule', 'bihr-woocommerce-importer' ); ?></p>
                <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-vehicle-compatibility', admin_url( 'admin.php' ) ) ); ?>" class="button">
                    <?php esc_html_e( 'Gérer', 'bihr-woocommerce-importer' ); ?>
                </a>
            </div>

            <div class="action-card">
                <div class="action-icon">🛒</div>
                <h3><?php esc_html_e( 'Synchroniser Commandes', 'bihr-woocommerce-importer' ); ?></h3>
                <p><?php esc_html_e( 'Configurer la synchro BIHR', 'bihr-woocommerce-importer' ); ?></p>
                <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-orders-settings', admin_url( 'admin.php' ) ) ); ?>" class="button">
                    <?php esc_html_e( 'Configurer', 'bihr-woocommerce-importer' ); ?>
                </a>
            </div>

            <div class="action-card">
                <div class="action-icon">📊</div>
                <h3><?php esc_html_e( 'Logs & Monitoring', 'bihr-woocommerce-importer' ); ?></h3>
                <p><?php esc_html_e( 'Voir l\'historique des opérations', 'bihr-woocommerce-importer' ); ?></p>
                <a href="<?php echo esc_url( add_query_arg( 'page', 'bihr-logs', admin_url( 'admin.php' ) ) ); ?>" class="button">
                    <?php esc_html_e( 'Voir Logs', 'bihr-woocommerce-importer' ); ?>
                </a>
            </div>

            <div class="action-card">
                <div class="action-icon">❓</div>
                <h3><?php esc_html_e( 'Aide & Support', 'bihr-woocommerce-importer' ); ?></h3>
                <p><?php esc_html_e( 'Accéder à la documentation', 'bihr-woocommerce-importer' ); ?></p>
                <a href="#" class="button" id="bihr-open-help">
                    <?php esc_html_e( 'Ouvrir', 'bihr-woocommerce-importer' ); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Section expert (cachée par défaut) -->
    <div class="bihr-expert-section" <?php echo $is_beginner_mode ? 'style="display:none;"' : ''; ?>>
        <h2>⚙️ Panel Expert</h2>
        <p><?php esc_html_e( 'Toutes les pages du plugin sont accessibles via le menu de gauche.', 'bihr-woocommerce-importer' ); ?></p>
    </div>

    <!-- Tips & Tricks -->
    <div class="bihr-tips-section">
        <h3>💡 Conseils Utiles</h3>
        <ul class="tips-list">
            <li>
                <strong><?php esc_html_e( 'Première synchronisation :', 'bihr-woocommerce-importer' ); ?></strong>
                <?php esc_html_e( 'Elle peut prendre quelques minutes selon le nombre de produits.', 'bihr-woocommerce-importer' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Stocks en temps réel :', 'bihr-woocommerce-importer' ); ?></strong>
                <?php esc_html_e( 'Activez la synchronisation automatique dans les paramètres.', 'bihr-woocommerce-importer' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Problèmes ? :', 'bihr-woocommerce-importer' ); ?></strong>
                <?php esc_html_e( 'Consultez les logs pour diagnostiquer les erreurs.', 'bihr-woocommerce-importer' ); ?>
            </li>
            <li>
                <strong><?php esc_html_e( 'Documentation :', 'bihr-woocommerce-importer' ); ?></strong>
                <?php esc_html_e( 'Lisez nos guides complets pour bien démarrer.', 'bihr-woocommerce-importer' ); ?>
            </li>
        </ul>
    </div>
</div>

<style>
.bihr-dashboard {
    max-width: 1200px;
}

.bihr-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.bihr-header h1 {
    margin: 0;
    font-size: 32px;
}

.bihr-mode-toggle {
    display: flex;
    align-items: center;
}

.bihr-mode-toggle label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 600;
    padding: 10px 16px;
    background: #f3f4f6;
    border-radius: 8px;
}

.bihr-mode-toggle input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.bihr-welcome-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.bihr-connected-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #10b981, #047857);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.welcome-content h2 {
    color: white;
    margin-top: 0;
}

.welcome-icon {
    font-size: 80px;
    opacity: 0.3;
}

.bihr-status-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.status-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.status-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.status-card.status-ok {
    border-left: 4px solid #10b981;
}

.status-card.status-warning {
    border-left: 4px solid #f59e0b;
}

.status-card.status-info {
    border-left: 4px solid #2563eb;
}

.status-icon {
    font-size: 40px;
    min-width: 60px;
    text-align: center;
}

.status-content {
    flex: 1;
}

.status-content h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.status-content p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.status-action {
    padding: 8px 16px;
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.status-action:hover {
    background: #e5e7eb;
}

.status-action.button-primary {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.status-action.button-primary:hover {
    background: #1e40af;
}

.status-action.button-success,
.button-success {
    background: #10b981;
    color: white;
    border-color: #10b981;
    cursor: default;
}

.status-action.button-success:hover,
.button-success:hover {
    background: #059669;
}

.beginner-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.step-card {
    position: relative;
    padding: 24px;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.step-card.step-completed {
    background: #f0fdf4;
    border-color: #10b981;
}

.step-card.step-active {
    background: #eff6ff;
    border-color: #2563eb;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.step-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #2563eb;
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 16px;
}

.step-card.step-completed .step-number {
    background: #10b981;
}

.step-content h3 {
    margin: 0 0 12px 0;
    font-size: 16px;
    color: #1f2937;
}

.step-content p {
    margin: 0 0 16px 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

.step-badge {
    display: inline-block;
    padding: 6px 12px;
    background: #d1fae5;
    color: #065f46;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.action-card {
    padding: 24px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s ease;
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.action-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.action-card h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
    color: #1f2937;
}

.action-card p {
    margin: 0 0 16px 0;
    font-size: 14px;
    color: #6b7280;
}

.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tips-list li {
    padding: 12px;
    background: #f0f9ff;
    border-left: 4px solid #2563eb;
    margin-bottom: 12px;
    border-radius: 6px;
    font-size: 14px;
    line-height: 1.6;
    color: #1f2937;
}

.tips-list strong {
    color: #2563eb;
}
</style>

<script>
(function($) {
    // Toggle mode débutant/expert
    $('#bihr_beginner_mode').on('change', function() {
        const isChecked = $(this).is(':checked');
        const label = $(this).closest('label').find('.toggle-label');
        
        // Mettre à jour le label
        if (isChecked) {
            label.text('🎓 Mode Débutant');
        } else {
            label.text('⚙️ Mode Expert');
        }
        
        // Afficher/masquer les sections
        $('.bihr-beginner-section, .bihr-quick-actions').toggle();
        $('.bihr-expert-section').toggle();
        
        // Sauvegarder en base de données
        $.post(ajaxurl, {
            action: 'bihr_toggle_beginner_mode',
            enabled: isChecked ? 1 : 0,
            nonce: '<?php echo esc_js( wp_create_nonce( 'bihr_toggle_mode' ) ); ?>'
        });
    });
    
    // Ouvrir l'aide
    $('#bihr-open-help').on('click', function(e) {
        e.preventDefault();
        alert('📖 Consultez la documentation:\n\nhttps://github.com/DRCOMPUTER60290/BIHR-SYNCH');
    });
    
    console.log('[BIHR] Dashboard chargé');
})(jQuery);
</script>
