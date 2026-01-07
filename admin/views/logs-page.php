<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1>Logs Bihr</h1>

    <?php
    // Préparer les infos de fuseau et l'heure locale du site
    $tz_string = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
    if ( empty( $tz_string ) ) {
        $offset = (float) get_option( 'gmt_offset', 0 );
        $sign   = $offset < 0 ? '-' : '+';
        $hours  = floor( abs( $offset ) );
        $mins   = ( abs( $offset ) - $hours ) * 60;
        $tz_string = 'UTC' . $sign . sprintf( '%02d:%02d', $hours, $mins );
    }
    $h = wp_date( 'H' );
    $i = wp_date( 'i' );
    $s = wp_date( 's' );
    $bihrwi_cleared = filter_input( INPUT_GET, 'bihrwi_cleared', FILTER_SANITIZE_NUMBER_INT );
    $bihrwi_cron_spawned = filter_input( INPUT_GET, 'bihrwi_cron_spawned', FILTER_SANITIZE_NUMBER_INT );
    ?>

    <div style="margin:10px 0; padding:8px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:6px; display:inline-block;">
        <strong>Heure du site</strong>
        <span style="margin-left:8px; font-family:monospace;" id="bihrwi-live-clock" data-h="<?php echo esc_attr( $h ); ?>" data-m="<?php echo esc_attr( $i ); ?>" data-s="<?php echo esc_attr( $s ); ?>">
            <?php echo esc_html( $h . ':' . $i . ':' . $s ); ?>
        </span>
        <span style="color:#6b7280; margin-left:6px;">(<?php echo esc_html( $tz_string ); ?>)</span>
    </div>

    <?php if ( ! empty( $bihrwi_cleared ) ) : ?>
        <div class="notice notice-success"><p>Les logs ont été effacés.</p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:15px;">
        <?php wp_nonce_field( 'bihrwi_clear_logs_action', 'bihrwi_clear_logs_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_clear_logs" />
        <?php submit_button( 'Effacer les logs', 'delete' ); ?>
    </form>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:15px;">
        <?php wp_nonce_field( 'bihrwi_spawn_cron_action', 'bihrwi_spawn_cron_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_spawn_cron" />
        <?php submit_button( '⚙️ Exécuter WP‑Cron maintenant', 'secondary' ); ?>
    </form>

    <?php if ( null !== $bihrwi_cron_spawned ) : ?>
        <div class="notice <?php echo $bihrwi_cron_spawned ? 'notice-success' : 'notice-error'; ?>"><p>
            <?php echo $bihrwi_cron_spawned ? 'WP‑Cron déclenché.' : 'Échec du déclenchement WP‑Cron.'; ?>
        </p></div>
    <?php endif; ?>

    <h2>Contenu du fichier de logs</h2>
    <textarea readonly style="width:100%;height:500px;"><?php echo esc_textarea( $log_contents ); ?></textarea>
</div>

<script>
(function(){
    const el = document.getElementById('bihrwi-live-clock');
    if(!el) return;
    let h = parseInt(el.getAttribute('data-h'),10)||0;
    let m = parseInt(el.getAttribute('data-m'),10)||0;
    let s = parseInt(el.getAttribute('data-s'),10)||0;
    function pad(n){return String(n).padStart(2,'0');}
    function tick(){
        s++;
        if(s>=60){ s=0; m++; }
        if(m>=60){ m=0; h++; }
        if(h>=24){ h=0; }
        el.textContent = pad(h)+':'+pad(m)+':'+pad(s);
    }
    setInterval(tick, 1000);
})();
</script>
