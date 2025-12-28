<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1>⚙️ Diagnostic WP‑Cron</h1>

    <?php
    $crons = _get_cron_array();
    $wp_cron_disabled = ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON );
    $loopback_status = 'Inconnu';
    $loopback_test = wp_remote_get( admin_url( 'admin-ajax.php' ), array( 'timeout' => 3, 'blocking' => true ) );
    if ( is_wp_error( $loopback_test ) ) {
        $loopback_status = '❌ Bloqué (' . $loopback_test->get_error_message() . ')';
    } else {
        $loopback_status = '✅ Fonctionnel';
    }
    ?>

    <div style="margin:20px 0; padding:15px; background:#f0f6fc; border:1px solid #0073aa; border-radius:6px;">
        <h2 style="margin-top:0;">État du système</h2>
        <table style="width:100%; max-width:600px;">
            <tr style="background:#fff;">
                <td style="padding:8px; border-bottom:1px solid #ddd;"><strong>WP‑Cron</strong></td>
                <td style="padding:8px; border-bottom:1px solid #ddd;">
                    <?php echo $wp_cron_disabled ? '❌ Désactivé (DISABLE_WP_CRON=true)' : '✅ Actif'; ?>
                </td>
            </tr>
            <tr style="background:#fff;">
                <td style="padding:8px; border-bottom:1px solid #ddd;"><strong>Requêtes loopback</strong></td>
                <td style="padding:8px; border-bottom:1px solid #ddd;"><?php echo $loopback_status; ?></td>
            </tr>
            <tr style="background:#fff;">
                <td style="padding:8px; border-bottom:1px solid #ddd;"><strong>Heure du serveur</strong></td>
                <td style="padding:8px; border-bottom:1px solid #ddd;"><?php echo esc_html( current_time( 'mysql' ) ); ?></td>
            </tr>
            <tr style="background:#fff;">
                <td style="padding:8px;"><strong>Heure UTC</strong></td>
                <td style="padding:8px;"><?php echo esc_html( gmdate( 'Y-m-d H:i:s' ) ); ?></td>
            </tr>
        </table>
    </div>

    <h2>Événements WP‑Cron BIHR planifiés</h2>

    <?php
    $bihr_events = array();
    if ( $crons ) {
        foreach ( $crons as $timestamp => $cron ) {
            foreach ( $cron as $hook => $details ) {
                if ( strpos( $hook, 'bihrwi' ) !== false ) {
                    $bihr_events[] = array(
                        'hook'      => $hook,
                        'timestamp' => $timestamp,
                        'overdue'   => time() > $timestamp,
                        'schedule'  => $details[0]['schedule'] ?? 'single',
                        'next'      => wp_date( 'Y-m-d H:i:s', $timestamp ),
                    );
                }
            }
        }
    }
    ?>

    <?php if ( empty( $bihr_events ) ) : ?>
        <div class="notice notice-warning"><p>
            ℹ️ Aucun événement BIHR planifié trouvé. Allez dans la page <strong>Produits</strong> et enregistrez le planning Prices.
        </p></div>
    <?php else : ?>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Événement (hook)</th>
                    <th>Prochain déclenchement</th>
                    <th>État</th>
                    <th>Récurrence</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $bihr_events as $event ) : ?>
                    <tr style="background-color: <?php echo $event['overdue'] ? '#fecaca' : '#dcfce7'; ?>;">
                        <td><code><?php echo esc_html( $event['hook'] ); ?></code></td>
                        <td><?php echo esc_html( $event['next'] ); ?></td>
                        <td>
                            <?php if ( $event['overdue'] ) : ?>
                                <span style="color:#b91c1c;">⚠️ EN RETARD</span>
                            <?php else : ?>
                                <span style="color:#16a34a;">✅ OK</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( ucfirst( $event['schedule'] ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2 style="margin-top:30px;">Recommandations</h2>

    <?php if ( $wp_cron_disabled ) : ?>
        <div class="notice notice-warning"><p>
            <strong>WP‑Cron est désactivé.</strong> Vous devez configurer un cron serveur pour déclencher les tâches.<br>
            Ajoutez à votre crontab:<br>
            <code>*/5 * * * * curl -s <?php echo esc_html( site_url( 'wp-cron.php' ) ); ?> > /dev/null 2>&1</code>
        </p></div>
    <?php elseif ( strpos( $loopback_status, 'Bloqué' ) !== false ) : ?>
        <div class="notice notice-warning"><p>
            <strong>Les requêtes loopback sont bloquées.</strong> WP‑Cron ne peut pas se déclencher automatiquement.<br>
            Utilisez l'une de ces solutions:<br>
            1. Cliquez sur <strong>⚙️ Exécuter WP‑Cron maintenant</strong> sur la page Logs (manuel).<br>
            2. Configurez un vrai cron serveur (recommandé):<br>
            <code>*/5 * * * * curl -s <?php echo esc_html( site_url( 'wp-cron.php' ) ); ?> > /dev/null 2>&1</code><br>
            3. Ou via WP‑CLI: <code>wp cron event run --due-now</code> (toutes les 5 min)
        </p></div>
    <?php else : ?>
        <div class="notice notice-success"><p>
            ✅ Votre système WP‑Cron semble fonctionnel. Les tâches doivent se déclencher automatiquement.
        </p></div>
    <?php endif; ?>

    <h2 style="margin-top:30px;">Guide d'installation d'un cron serveur</h2>

    <p><strong>Accès à cPanel/WHM :</strong></p>
    <ol style="line-height:1.8;">
        <li>Allez dans <strong>Cron Jobs</strong></li>
        <li>Sélectionnez <strong>Ajouter une nouvelle tâche cron</strong></li>
        <li>Choisissez <strong>Tous les 5 minutes</strong></li>
        <li>Collez la commande:<br>
            <code style="display:block; background:#f0f0f0; padding:8px; margin:8px 0; border-radius:4px;">
            curl -s <?php echo esc_html( site_url( 'wp-cron.php' ) ); ?> > /dev/null 2>&1
            </code>
        </li>
        <li>Cliquez sur <strong>Ajouter une nouvelle tâche cron</strong></li>
    </ol>

    <p><strong>Via SSH/Ligne de commande :</strong></p>
    <ol style="line-height:1.8;">
        <li>Ouvrez votre terminal ou SSH</li>
        <li>Tapez: <code>crontab -e</code></li>
        <li>Ajoutez la ligne:<br>
            <code style="display:block; background:#f0f0f0; padding:8px; margin:8px 0; border-radius:4px;">
            */5 * * * * curl -s <?php echo esc_html( site_url( 'wp-cron.php' ) ); ?> > /dev/null 2>&1
            </code>
        </li>
        <li>Sauvegardez (Ctrl+X, Y, Enter)</li>
    </ol>

</div>
