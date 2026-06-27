<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Variables fournies par BihrWI_Admin::render_products_page()
 *
 * @var array $products
 * @var int   $current_page
 * @var int   $per_page
 * @var int   $total
 * @var int   $total_pages
 * @var string $filter_search
 * @var string $filter_stock
 * @var string $filter_price_min
 * @var string $filter_price_max
 * @var string $filter_category
 * @var string $sort_by
 */

// Initialiser les variables de filtre si elles n'existent pas
if ( ! isset( $filter_search ) ) {
    $filter_search = '';
}
if ( ! isset( $filter_stock ) ) {
    $filter_stock = '';
}
if ( ! isset( $filter_price_min ) ) {
    $filter_price_min = '';
}
if ( ! isset( $filter_price_max ) ) {
    $filter_price_max = '';
}
if ( ! isset( $filter_category ) ) {
    $filter_category = '';
}
if ( ! isset( $sort_by ) ) {
    $sort_by = '';
}

$bihrwi_merge_success      = filter_input( INPUT_GET, 'bihrwi_merge_success', FILTER_SANITIZE_NUMBER_INT );
$bihrwi_merge_count        = filter_input( INPUT_GET, 'bihrwi_merge_count', FILTER_SANITIZE_NUMBER_INT );
$bihrwi_merge_error        = filter_input( INPUT_GET, 'bihrwi_merge_error', FILTER_SANITIZE_NUMBER_INT );
$bihrwi_import_success     = filter_input( INPUT_GET, 'bihrwi_import_success', FILTER_SANITIZE_NUMBER_INT );
$bihrwi_import_error       = filter_input( INPUT_GET, 'bihrwi_import_error', FILTER_SANITIZE_NUMBER_INT );
$bihrwi_imported_id        = filter_input( INPUT_GET, 'imported_id', FILTER_SANITIZE_NUMBER_INT );
$bihrwi_msg                = filter_input( INPUT_GET, 'bihrwi_msg', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$bihrwi_check_status       = filter_input( INPUT_GET, 'bihrwi_check_status', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$bihrwi_prices_started     = filter_input( INPUT_GET, 'bihrwi_prices_started', FILTER_SANITIZE_NUMBER_INT );
$bihrwi_prices_error       = filter_input( INPUT_GET, 'bihrwi_prices_error', FILTER_SANITIZE_NUMBER_INT );
$bihrwi_prices_schedule_saved = filter_input( INPUT_GET, 'bihrwi_prices_schedule_saved', FILTER_SANITIZE_NUMBER_INT );
$merge_error               = filter_input( INPUT_GET, 'merge_error', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$total_products_param      = filter_input( INPUT_GET, 'total_products', FILTER_SANITIZE_NUMBER_INT );

$status_data      = get_option( 'bihrwi_prices_generation', array() );
$prices_schedule  = get_option( 'bihrwi_prices_schedule', array( 'enabled' => false, 'weekday' => 'monday', 'interval' => 'weekly', 'time' => '02:00' ) );
$next_prices_cron = wp_next_scheduled( 'bihrwi_auto_prices_generation' );
$wp_cron_disabled = ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON );
$prices_last_run  = get_option( 'bihrwi_prices_last_run', '' );
?>

<div class="wrap">
    <h1>Bihr Import – Produits Bihr</h1>

    <?php if ( ! empty( $bihrwi_debug ) && current_user_can( 'manage_woocommerce' ) ) : ?>
        <div class="notice notice-info" style="padding:10px;">
            <p style="margin:0;"><strong>BIHR Debug</strong></p>
            <p style="margin:6px 0 0;">
                Page: <strong><?php echo intval( $current_page ); ?></strong> / <strong><?php echo intval( $total_pages ); ?></strong>
                — Total: <strong><?php echo intval( $total ); ?></strong>
                — Per page: <strong><?php echo intval( $per_page ); ?></strong>
            </p>
            <p style="margin:6px 0 0;">
                Filtres: search=<code><?php echo esc_html( $filter_search ); ?></code>,
                stock=<code><?php echo esc_html( $filter_stock ); ?></code>,
                price_min=<code><?php echo esc_html( $filter_price_min ); ?></code>,
                price_max=<code><?php echo esc_html( $filter_price_max ); ?></code>,
                category=<code><?php echo esc_html( $filter_category ); ?></code>,
                sort_by=<code><?php echo esc_html( $sort_by ); ?></code>
            </p>
            <?php if ( ! empty( $debug_count_last_error ) || ! empty( $debug_products_last_error ) ) : ?>
                <p style="margin:6px 0 0; color:#b91c1c;"><strong>DB errors:</strong>
                    <code><?php echo esc_html( trim( (string) $debug_count_last_error ) ); ?></code>
                    <code><?php echo esc_html( trim( (string) $debug_products_last_error ) ); ?></code>
                </p>
            <?php endif; ?>
            <details style="margin-top:8px;">
                <summary>Requêtes SQL</summary>
                <div style="margin-top:6px; font-family: monospace; font-size: 12px; white-space: pre-wrap;">
                    <div><strong>COUNT</strong>: <?php echo esc_html( (string) $debug_count_last_query ); ?></div>
                    <div style="margin-top:6px;"><strong>PRODUCTS</strong>: <?php echo esc_html( (string) $debug_products_last_query ); ?></div>
                </div>
            </details>
        </div>
    <?php endif; ?>

    <?php
    /* =======================
     *  NOTIFICATIONS (GET)
     * ======================= */

    // Fusion catalogues
    if ( ! empty( $bihrwi_merge_success ) ) : ?>
        <div class="notice notice-success"><p>
            Fusion des catalogues terminée. <?php echo intval( $bihrwi_merge_count ); ?> produits fusionnés.
        </p></div>
    <?php endif; ?>

    <?php if ( ! empty( $bihrwi_merge_error ) ) : ?>
        <div class="notice notice-error"><p>
            Erreur lors de la fusion des catalogues :
            <?php echo esc_html( (string) $bihrwi_msg ); ?>
        </p></div>
    <?php endif; ?>

    <!-- Import produit -->
    <?php if ( ! empty( $bihrwi_import_success ) ) : ?>
        <div class="notice notice-success"><p>
            Produit importé dans WooCommerce (ID : <?php echo intval( $bihrwi_imported_id ); ?>).
        </p></div>
    <?php endif; ?>

    <?php if ( ! empty( $bihrwi_import_error ) ) : ?>
        <div class="notice notice-error"><p>
            Erreur lors de l’import du produit :
            <?php echo esc_html( (string) $bihrwi_msg ); ?>
        </p></div>
    <?php endif; ?>

    <!-- Statut vérification manuelle du catalog Prices -->
    <?php if ( ! empty( $bihrwi_check_status ) ) : ?>
        <?php if ( 'processing' === $bihrwi_check_status ) : ?>
            <div class="notice notice-warning"><p>
                Le fichier Prices est toujours en cours de génération (PROCESSING).
            </p></div>
        <?php elseif ( 'done_and_merged' === $bihrwi_check_status ) : ?>
            <div class="notice notice-success"><p>
                <strong>✓ Succès complet !</strong><br>
                Le catalogue Prices a été téléchargé et automatiquement fusionné avec les autres catalogues.<br>
                <strong><?php echo intval( $total_products_param ); ?> produits</strong> sont maintenant disponibles.
            </p></div>
        <?php elseif ( 'done_merge_failed' === $bihrwi_check_status ) : ?>
            <div class="notice notice-warning"><p>
                Le catalogue Prices a été téléchargé, mais la fusion automatique a échoué.<br>
                Vous pouvez essayer de lancer la fusion manuellement ci-dessous.
            </p></div>
        <?php elseif ( 'done' === $bihrwi_check_status ) : ?>
            <div class="notice notice-success"><p>
                Le fichier Prices est prêt et a été téléchargé.
                <?php if ( ! empty( $merge_error ) ) : ?>
                    <br><strong>Note:</strong> La fusion automatique a rencontré une erreur : <?php echo esc_html( (string) $merge_error ); ?>
                <?php endif; ?>
            </p></div>
        <?php elseif ( 'error' === $bihrwi_check_status ) : ?>
            <div class="notice notice-error"><p>
                Erreur lors de la génération du fichier Prices :
                <?php echo esc_html( (string) $bihrwi_msg ); ?>
            </p></div>
        <?php elseif ( 'downloadfail' === $bihrwi_check_status ) : ?>
            <div class="notice notice-error"><p>
                Le fichier Prices est marqué comme prêt, mais le téléchargement a échoué.
            </p></div>
        <?php elseif ( 'exception' === $bihrwi_check_status ) : ?>
            <div class="notice notice-error"><p>
                Erreur inattendue lors de la vérification du catalog Prices :
                <?php echo esc_html( (string) $bihrwi_msg ); ?>
            </p></div>
        <?php elseif ( 'noticket' === $bihrwi_check_status ) : ?>
            <div class="notice notice-error"><p>
                Aucun TicketID en cours. Lance d’abord la génération du catalog Prices.
            </p></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( ! empty( $bihrwi_prices_started ) ) : ?>
        <div class="notice notice-success"><p>
            Génération du catalog Prices démarrée. Le statut sera vérifié automatiquement par WP-Cron.
        </p></div>
    <?php endif; ?>

    <?php if ( ! empty( $bihrwi_prices_error ) ) : ?>
        <div class="notice notice-error"><p>
            Erreur lors du démarrage du catalog Prices :
            <?php echo esc_html( (string) $bihrwi_msg ); ?>
        </p></div>
    <?php endif; ?>

    <?php if ( ! empty( $bihrwi_prices_schedule_saved ) ) : ?>
        <div class="notice notice-success"><p>
            Planning du catalog Prices enregistré.
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_reset_success'] ) ) : ?>
        <div class="notice notice-success"><p>
            Toutes les données ont été effacées avec succès.
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_download_success'] ) ) : ?>
        <div class="notice notice-success"><p>
            Téléchargement terminé ! 
            <?php 
            $catalogs = isset( $_GET['bihrwi_catalogs_count'] ) ? intval( $_GET['bihrwi_catalogs_count'] ) : 0;
            $files = isset( $_GET['bihrwi_files_count'] ) ? intval( $_GET['bihrwi_files_count'] ) : 0;
            echo $catalogs > 0 ? esc_html( $catalogs ) . ' catalogue(s) téléchargé(s), ' : '';
            echo esc_html( $files ) . ' fichier(s) CSV extrait(s) dans le dossier d\'import.';
            ?>
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_download_error'] ) ) : ?>
        <div class="notice notice-error"><p>
            Erreur lors du téléchargement des catalogues :
            <?php 
            $error_msg = isset( $_GET['bihrwi_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['bihrwi_msg'] ) ) : '';
            echo esc_html( $error_msg ); 
            ?>
        </p></div>
    <?php endif; ?>


    <!-- =========================================================
         0. FILTRE CATÉGORIES — Whitelist des catégories à importer
    ========================================================== -->

    <?php
    $bihrwi_whitelist_saved = filter_input( INPUT_GET, 'bihrwi_whitelist_saved', FILTER_SANITIZE_NUMBER_INT );
    if ( ! empty( $bihrwi_whitelist_saved ) ) : ?>
        <div class="notice notice-success"><p>Filtre de catégories sauvegardé. Relancez une fusion pour appliquer.</p></div>
    <?php endif; ?>

    <h2>0. Filtre des catégories à importer</h2>

    <div class="bihr-section">
        <p>
            Limitez l'import aux catégories BIHR de niveau 1 souhaitées. Si <strong>aucune case n'est cochée</strong>,
            toutes les catégories sont importées. Après modification, <strong>relancez une fusion des catalogues</strong>
            pour appliquer le filtre.
        </p>

        <?php if ( empty( $available_cat_l1 ) ) : ?>
            <p><em>Aucune catégorie disponible. Effectuez d'abord une fusion de catalogues.</em></p>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="bihrwi_save_cat_whitelist">
                <?php wp_nonce_field( 'bihrwi_cat_whitelist_action', 'bihrwi_cat_whitelist_nonce' ); ?>

                <div style="display:flex; flex-wrap:wrap; gap:8px 24px; margin-bottom:16px;">
                    <?php foreach ( $available_cat_l1 as $cat_row ) :
                        $cat_val = is_array( $cat_row ) ? reset( $cat_row ) : $cat_row;
                        $cat_val = trim( (string) $cat_val );
                        if ( $cat_val === '' ) continue;
                        $checked = in_array( $cat_val, $cat_l1_whitelist, true ) ? 'checked' : '';
                    ?>
                        <label style="display:flex; align-items:center; gap:6px; white-space:nowrap;">
                            <input type="checkbox" name="bihrwi_cat_l1_whitelist[]"
                                   value="<?php echo esc_attr( $cat_val ); ?>" <?php echo $checked; ?>>
                            <?php echo esc_html( $cat_val ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p class="submit" style="margin:0;">
                    <button type="submit" class="button button-primary">Enregistrer le filtre</button>
                    <?php if ( ! empty( $cat_l1_whitelist ) ) : ?>
                        <span style="margin-left:12px; color:#666; font-size:13px;">
                            Filtre actif : <?php echo esc_html( count( $cat_l1_whitelist ) ); ?> catégorie(s) sélectionnée(s)
                        </span>
                    <?php else : ?>
                        <span style="margin-left:12px; color:#666; font-size:13px;">Tout importer (aucun filtre)</span>
                    <?php endif; ?>
                </p>
            </form>
        <?php endif; ?>
    </div>

    <!-- =========================================================
         1. FUSION DES CATALOGUES CSV
    ========================================================== -->

    <h2>1. Fusion des catalogues CSV</h2>

    <div class="bihr-section">
        <h3>Option A : Téléchargement automatique depuis l'API Bihr</h3>
        <p>
            Télécharge automatiquement le catalogue <code>Extended</code> (cat-extended-full-*.zip, CSV)
            depuis l'API Bihr et l'extrait dans le dossier d'import.
            <br><strong>⚠️ Cette opération peut prendre plusieurs minutes.</strong>
        </p>

        <form method="post" id="bihr-download-all-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'bihrwi_download_all_action', 'bihrwi_download_all_nonce' ); ?>
            <input type="hidden" name="action" value="bihrwi_download_all_catalogs" />
            <?php submit_button( '📥 Télécharger le catalogue Extended (cat-extended-full)', 'primary large', 'submit', false ); ?>
        </form>

        <div id="bihr-download-progress" class="bihr-progress-container">
            <div class="bihr-progress-bar-wrapper">
                <div id="bihr-download-progress-bar" class="bihr-progress-bar"></div>
            </div>
            <div id="bihr-download-progress-text" class="bihr-progress-text">Initialisation...</div>
        </div>
    </div>

    <div class="bihr-section">
        <p>
            Place tous les fichiers CSV Bihr nécessaires (<code>references</code>, <code>extended</code>
            (contenu de <code>cat-extended-full-*.zip</code>), <code>prices</code>, <code>images</code>,
            <code>inventory</code>, <code>attributes</code>) dans
            <code>wp-content/uploads/bihr-import/</code>, puis clique sur le bouton ci-dessous.
        </p>

        <form method="post" id="bihr-merge-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'bihrwi_merge_catalogs_action', 'bihrwi_merge_catalogs_nonce' ); ?>
            <input type="hidden" name="action" value="bihrwi_merge_catalogs" />
            <?php submit_button( 'Fusionner les catalogues', 'secondary', 'bihr-merge-catalogs-button', false ); ?>
        </form>

        <div id="bihr-merge-progress" class="bihr-progress-container" style="display:none; margin-top:15px;">

            <!-- Indicateur de phase -->
            <div id="bihr-merge-phase" style="
                display: flex; align-items: center; gap: 12px;
                margin-bottom: 10px; font-weight: 600; font-size: 13px; color: #1d2327;
            ">
                <span id="bihr-phase-icon" style="font-size:18px;">📂</span>
                <span id="bihr-phase-label">Phase 1 / 2 — Lecture des catalogues CSV</span>
                <span id="bihr-phase-counter" style="margin-left:auto; color:#666; font-weight:400;"></span>
            </div>

            <!-- Barre de progression -->
            <div class="bihr-progress-bar-wrapper">
                <div id="bihr-merge-progress-bar" class="bihr-progress-bar"></div>
            </div>
            <div id="bihr-merge-progress-text" class="bihr-progress-text" style="margin-bottom:12px;">Initialisation...</div>

            <!-- Tableau de fichiers style installeur -->
            <div id="bihr-merge-file-list" style="
                height: 260px; overflow-y: auto;
                background: #0d1117; color: #c9d1d9;
                border-radius: 6px; border: 1px solid #30363d;
                font-family: 'Consolas', 'Monaco', monospace; font-size: 12px;
                padding: 8px 12px; line-height: 1.7;
            ">
                <div style="color:#58a6ff; margin-bottom:6px; border-bottom:1px solid #21262d; padding-bottom:6px;">
                    BIHR Catalog Merge — en attente de démarrage...
                </div>
            </div>

            <!-- Statistiques résumé (visible après fin) -->
            <div id="bihr-merge-summary" style="display:none; margin-top:12px; padding:10px 14px;
                background:#f0f6fc; border-left:4px solid #0969da; border-radius:4px; font-size:13px;">
            </div>

        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {

        var mergeButton          = $('#bihr-merge-catalogs-button');
        var mergeProgressContainer = $('#bihr-merge-progress');
        var mergeProgressBar     = $('#bihr-merge-progress-bar');
        var mergeProgressText    = $('#bihr-merge-progress-text');
        var mergePhaseIcon       = $('#bihr-phase-icon');
        var mergePhaseLabel      = $('#bihr-phase-label');
        var mergePhaseCounter    = $('#bihr-phase-counter');
        var mergeFileList        = $('#bihr-merge-file-list');
        var mergeSummary         = $('#bihr-merge-summary');

        // Compteurs
        var totalFiles = 0, doneFiles = 0, totalProducts = 0;

        // Garder la trace du dernier texte traité (évite de re-traiter les mêmes lignes)
        var lastResponseLength = 0;
        var mergeCompleted = false;
        var activeFileRow = null; // Référence à la ligne en cours

        function escHtml(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(String(text || '')));
            return div.innerHTML;
        }

        // Extrait le nom court du catalogue depuis le nom de fichier
        // Ex: "cat-extended-full-FR01-...-ARAI.csv" → "ARAI"
        function shortName(filename) {
            var m = filename.match(/_([^_]+)\.csv$/i);
            return m ? m[1] : filename;
        }

        function processMergeLines(fullText) {
            // Traiter uniquement les nouvelles données
            var newText = fullText.slice(lastResponseLength);
            lastResponseLength = fullText.length;
            if (!newText) return;

            var lines = newText.split('\n');
            lines.forEach(function(line) {
                try {
                    if (line.trim() === '') return;
                    var d = JSON.parse(line);

                    if (d.type === 'file_start') {
                        // ── Phase 1 : lecture CSV ──
                        totalFiles = d.total;
                        doneFiles  = d.current - 1;

                        // Mettre à jour le header de phase
                        mergePhaseIcon.text('📂');
                        mergePhaseLabel.text('Phase 1 / 2 — Lecture des catalogues CSV');
                        mergePhaseCounter.text(d.current + ' / ' + d.total);

                        // Barre de progression phase 1 (max 50% réservé à la sauvegarde)
                        var pct = (d.current / d.total) * 50;
                        mergeProgressBar.css({'width': pct + '%', 'background': '#2271b1'}).text(d.current + '/' + d.total);
                        mergeProgressText.text('Lecture : ' + escHtml(d.filename));

                        // Ajouter une ligne "en cours" dans le tableau
                        var rowId = 'mf-' + d.current;
                        var rowHtml = '<div id="' + rowId + '" style="display:flex;align-items:center;gap:8px;padding:2px 0;">'
                            + '<span class="bihr-file-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #30363d;border-top-color:#58a6ff;border-radius:50%;animation:bihrSpin .8s linear infinite;flex-shrink:0;"></span>'
                            + '<span style="color:#e6edf3;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escHtml(d.filename) + '">'
                            + escHtml(shortName(d.filename))
                            + '</span>'
                            + '<span style="color:#484f58;font-size:11px;">...</span>'
                            + '</div>';
                        mergeFileList.append(rowHtml);
                        activeFileRow = $('#' + rowId);

                        // Auto-scroll
                        mergeFileList.scrollTop(mergeFileList[0].scrollHeight);

                    } else if (d.type === 'file_done') {
                        // ── Terminer la ligne en cours ──
                        doneFiles++;
                        totalProducts = d.count;

                        if (activeFileRow) {
                            activeFileRow.html(
                                '<span style="color:#3fb950;flex-shrink:0;">✔</span>'
                                + '<span style="color:#8b949e;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escHtml(d.filename) + '">'
                                + escHtml(shortName(d.filename))
                                + '</span>'
                                + '<span style="color:#3fb950;font-size:11px;white-space:nowrap;">' + d.count.toLocaleString() + ' produits</span>'
                            ).css('display', 'flex').css('align-items','center').css('gap','8px').css('padding','2px 0');
                            activeFileRow = null;
                        }

                        mergeFileList.scrollTop(mergeFileList[0].scrollHeight);

                    } else if (d.type === 'progress') {
                        // ── Phase 2 : sauvegarde DB ──
                        mergePhaseIcon.text('💾');
                        mergePhaseLabel.text('Phase 2 / 2 — Sauvegarde en base de données');

                        var savePct = d.total > 0 ? (d.current / d.total) * 50 : 0;
                        var totalPct = 50 + savePct; // Phase 1 = 0-50%, Phase 2 = 50-100%
                        mergeProgressBar.css({'width': totalPct + '%', 'background': '#0969da'})
                            .text(Math.round(totalPct) + '%');

                        mergePhaseCounter.text(d.current.toLocaleString() + ' / ' + d.total.toLocaleString());
                        mergeProgressText.text(d.message);

                        // Ajouter une ligne de sauvegarde dans le tableau (une seule fois)
                        if (!$('#bihr-save-row').length) {
                            mergeFileList.append(
                                '<div style="border-top:1px solid #21262d;margin:6px 0 4px;"></div>'
                                + '<div id="bihr-save-row" style="display:flex;align-items:center;gap:8px;padding:2px 0;">'
                                + '<span class="bihr-file-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #30363d;border-top-color:#0969da;border-radius:50%;animation:bihrSpin .8s linear infinite;flex-shrink:0;"></span>'
                                + '<span style="color:#79c0ff;flex:1;">Sauvegarde en base de données...</span>'
                                + '<span id="bihr-save-count" style="color:#0969da;font-size:11px;white-space:nowrap;"></span>'
                                + '</div>'
                            );
                            mergeFileList.scrollTop(mergeFileList[0].scrollHeight);
                        }
                        $('#bihr-save-count').text(d.current.toLocaleString() + ' / ' + d.total.toLocaleString());

                    } else if (d.type === 'complete') {
                        mergeCompleted = true;

                        // Finaliser la ligne de sauvegarde
                        $('#bihr-save-row').html(
                            '<span style="color:#3fb950;flex-shrink:0;">✔</span>'
                            + '<span style="color:#8b949e;flex:1;">Sauvegarde terminée</span>'
                            + '<span style="color:#3fb950;font-size:11px;white-space:nowrap;">'
                            + (d.current || totalProducts).toLocaleString() + ' produits</span>'
                        ).css('display','flex').css('align-items','center').css('gap','8px').css('padding','2px 0');

                        mergeProgressBar.css({'width': '100%', 'background': '#00a32a'}).text('100%');
                        mergePhaseIcon.text('✅');
                        mergePhaseLabel.text('Fusion terminée !');
                        mergePhaseCounter.text('');
                        mergeProgressText.text('Redirection en cours...');

                        // Résumé final
                        mergeSummary.html(
                            '<strong>' + (d.current || totalProducts).toLocaleString() + ' produits</strong> fusionnés depuis '
                            + doneFiles + ' fichiers CSV. Redirection...'
                        ).show();

                        setTimeout(function() {
                            window.location.href = d.redirect_url;
                        }, 1800);

                    } else if (d.type === 'error') {
                        mergeCompleted = true;
                        mergeProgressBar.css({'width': '100%', 'background': '#da3633'}).text('Erreur');
                        mergePhaseIcon.text('❌');
                        mergePhaseLabel.text('Erreur lors de la fusion');
                        mergeProgressText.text(d.message);
                        mergeFileList.append(
                            '<div style="color:#f85149;margin-top:8px;">❌ ' + escHtml(d.message) + '</div>'
                        );
                        mergeButton.prop('disabled', false).text('Fusionner les catalogues');
                    }

                } catch (parseErr) {
                    // Ligne partielle ou non-JSON — normal en streaming
                }
            });
        }

        mergeButton.on('click', function(e) {
            e.preventDefault();

            // Réinitialiser l'état
            mergeCompleted    = false;
            lastResponseLength = 0;
            totalFiles = doneFiles = totalProducts = 0;
            activeFileRow = null;

            mergeButton.prop('disabled', true).text('Fusion en cours...');
            mergeProgressContainer.show();
            mergeProgressBar.css({'width': '0%', 'background': '#2271b1'}).text('0%');
            mergeProgressText.text('Démarrage...');
            mergePhaseIcon.text('⏳');
            mergePhaseLabel.text('Initialisation de la fusion...');
            mergePhaseCounter.text('');
            mergeFileList.html(
                '<div style="color:#58a6ff;margin-bottom:6px;border-bottom:1px solid #21262d;padding-bottom:6px;">'
                + '▶ BIHR Catalog Merge démarré</div>'
            );
            mergeSummary.hide().html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'text',
                data: {
                    action: 'bihrwi_merge_catalogs_ajax',
                    _wpnonce: $('#bihr-merge-form input[name="bihrwi_merge_catalogs_nonce"]').val()
                },
                xhrFields: {
                    onprogress: function(e) {
                        processMergeLines(e.currentTarget.responseText);
                    }
                },
                success: function(response) {
                    processMergeLines(response);
                },
                error: function(xhr, status, error) {
                    if (mergeCompleted) return;
                    mergeProgressBar.css({'width': '100%', 'background': '#da3633'}).text('Erreur');
                    mergePhaseIcon.text('❌');
                    mergePhaseLabel.text('Connexion interrompue');
                    mergeProgressText.text('Erreur AJAX : ' + (error || status || 'connexion perdue'));
                    mergeFileList.append(
                        '<div style="color:#f85149;margin-top:8px;">❌ Erreur AJAX : ' + escHtml(error || status) + '</div>'
                    );
                    mergeButton.prop('disabled', false).text('Fusionner les catalogues');
                }
            });
        });

    });
    </script>

    <style>
    @keyframes bihrSpin {
        to { transform: rotate(360deg); }
    }
    </style>

    <div class="bihr-section" style="margin-top: 20px;">
        <h3>Recalculer les catégories depuis cat-ref-full</h3>
        <p>
            Recalcule les colonnes <code>cat_l1</code>, <code>cat_l2</code>, <code>cat_l3</code> 
            depuis le fichier <code>cat-ref-full-*.csv</code> le plus récent.
            <br><strong>⚠️ Cette opération met à jour uniquement les catégories CategoryPath, pas la colonne "category".</strong>
        </p>

        <button type="button" id="bihr-rebuild-cat-levels-btn" class="button button-secondary">
            🔄 Recalculer catégories (cat-ref-full)
        </button>

        <div id="bihr-rebuild-cat-progress" class="bihr-progress-container" style="display:none; margin-top: 15px;">
            <div class="bihr-progress-bar-wrapper">
                <div id="bihr-rebuild-cat-progress-bar" class="bihr-progress-bar"></div>
            </div>
            <div id="bihr-rebuild-cat-progress-text" class="bihr-progress-text">Initialisation...</div>
        </div>
    </div>

    <!-- Bouton pour effacer les données -->
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir effacer toutes les données de la table wp_bihr_products ?');">
        <?php wp_nonce_field( 'bihrwi_reset_data_action', 'bihrwi_reset_data_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_reset_data" />
        <?php submit_button( 'Effacer les données', 'delete', '', false ); ?>
    </form>

    <hr />

    <!-- =========================================================
         1.5 TRADUCTION DES CATÉGORIES BIHR → FRANÇAIS
    ========================================================== -->
    <h2>1.5 Traduction des catégories</h2>

    <div class="bihr-section">
        <p>
            Analyse les fichiers <code>cat-extended-full-*.csv</code>, extrait toutes les catégories uniques,
            génère les traductions françaises via l'IA et crée l'arborescence WooCommerce correspondante.
            <br><strong>⚠️ Une clé OpenAI est requise pour la traduction automatique. Sans clé, seules les catégories du cache sont utilisées.</strong>
        </p>

        <button type="button" id="bihr-analyze-categories-btn" class="button button-primary">
            🏷️ Analyser et traduire les catégories
        </button>
        &nbsp;
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihr-categories-fr' ) ); ?>" class="button button-secondary">
            👁️ Voir les traductions
        </a>

        <div id="bihr-analyze-progress" class="bihr-progress-container" style="display:none; margin-top:15px;">

            <div id="bihr-analyze-phase" style="
                display:flex; align-items:center; gap:12px;
                margin-bottom:10px; font-weight:600; font-size:13px; color:#1d2327;
            ">
                <span id="bihr-analyze-icon" style="font-size:18px;">📂</span>
                <span id="bihr-analyze-label">Démarrage...</span>
                <span id="bihr-analyze-counter" style="margin-left:auto; color:#666; font-weight:400;"></span>
            </div>

            <div class="bihr-progress-bar-wrapper">
                <div id="bihr-analyze-bar" class="bihr-progress-bar"></div>
            </div>
            <div id="bihr-analyze-text" class="bihr-progress-text" style="margin-bottom:12px;">Initialisation...</div>

            <div id="bihr-analyze-log" style="
                height:220px; overflow-y:auto;
                background:#0d1117; color:#c9d1d9;
                border-radius:6px; border:1px solid #30363d;
                font-family:'Consolas','Monaco',monospace; font-size:12px;
                padding:8px 12px; line-height:1.7;
            ">
                <div style="color:#58a6ff; border-bottom:1px solid #21262d; padding-bottom:6px; margin-bottom:4px;">
                    BIHR Category Translator — en attente...
                </div>
            </div>

            <div id="bihr-analyze-summary" style="display:none; margin-top:12px; padding:10px 14px;
                background:#f0f6fc; border-left:4px solid #0969da; border-radius:4px; font-size:13px;">
            </div>
        </div>
    </div>

    <div class="bihr-section" style="margin-top:16px;">
        <p>
            Applique les traductions françaises à tous les produits WooCommerce BIHR existants.
            Met à jour les métadonnées <code>_bihr_category1_fr</code> et affecte les catégories WooCommerce françaises.
        </p>

        <button type="button" id="bihr-apply-french-btn" class="button button-secondary">
            ✅ Appliquer les catégories françaises aux produits
        </button>

        <div id="bihr-apply-progress" class="bihr-progress-container" style="display:none; margin-top:15px;">

            <div id="bihr-apply-phase" style="
                display:flex; align-items:center; gap:12px;
                margin-bottom:10px; font-weight:600; font-size:13px; color:#1d2327;
            ">
                <span id="bihr-apply-icon" style="font-size:18px;">⚙️</span>
                <span id="bihr-apply-label">Démarrage...</span>
                <span id="bihr-apply-counter" style="margin-left:auto; color:#666; font-weight:400;"></span>
            </div>

            <div class="bihr-progress-bar-wrapper">
                <div id="bihr-apply-bar" class="bihr-progress-bar"></div>
            </div>
            <div id="bihr-apply-text" class="bihr-progress-text" style="margin-bottom:12px;">Initialisation...</div>

            <div id="bihr-apply-summary" style="display:none; margin-top:12px; padding:10px 14px;
                background:#f0f6fc; border-left:4px solid #0969da; border-radius:4px; font-size:13px;">
            </div>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {

        var analyzeNonce = '<?php echo esc_js( wp_create_nonce( 'bihrwi_analyze_categories_action' ) ); ?>';
        var applyNonce   = '<?php echo esc_js( wp_create_nonce( 'bihrwi_apply_french_categories_action' ) ); ?>';
        var ajaxUrl      = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

        function escHtml(text) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(String(text || '')));
            return d.innerHTML;
        }

        // ─── Analyser et traduire ───────────────────────────────────────────

        var analyzeBtn       = $('#bihr-analyze-categories-btn');
        var analyzeContainer = $('#bihr-analyze-progress');
        var analyzeBar       = $('#bihr-analyze-bar');
        var analyzeText      = $('#bihr-analyze-text');
        var analyzeIcon      = $('#bihr-analyze-icon');
        var analyzeLabel     = $('#bihr-analyze-label');
        var analyzeCounter   = $('#bihr-analyze-counter');
        var analyzeLog       = $('#bihr-analyze-log');
        var analyzeSummary   = $('#bihr-analyze-summary');

        var analyzeLastLen = 0;

        function processAnalyzeLines(fullText) {
            var newText = fullText.slice(analyzeLastLen);
            analyzeLastLen = fullText.length;
            if (!newText) return;

            newText.split('\n').forEach(function(line) {
                try {
                    if (!line.trim()) return;
                    var d = JSON.parse(line);

                    if (d.type === 'file_start') {
                        analyzeIcon.text('📂');
                        analyzeLabel.text('Lecture des fichiers CSV');
                        analyzeCounter.text(d.current + ' / ' + d.total);
                        analyzeBar.css({width: (d.current / d.total * 30) + '%', background: '#2271b1'})
                            .text(d.current + '/' + d.total);
                        analyzeText.text('Lecture : ' + escHtml(d.message));
                        analyzeLog.append(
                            '<div style="display:flex;gap:8px;padding:2px 0;">'
                            + '<span class="bihr-file-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #30363d;border-top-color:#58a6ff;border-radius:50%;animation:bihrSpin .8s linear infinite;flex-shrink:0;"></span>'
                            + '<span style="color:#e6edf3;">' + escHtml(d.message) + '</span>'
                            + '</div>'
                        );
                        analyzeLog.scrollTop(analyzeLog[0].scrollHeight);

                    } else if (d.type === 'file_done') {
                        analyzeLog.find('.bihr-file-spinner').last().replaceWith('<span style="color:#3fb950;flex-shrink:0;">✔</span>');

                    } else if (d.type === 'status') {
                        analyzeIcon.text('🔄');
                        analyzeLabel.text(d.message);
                        analyzeBar.css({width: (d.current / d.total * 100) + '%', background:'#0969da'})
                            .text(Math.round(d.current / d.total * 100) + '%');
                        analyzeLog.append('<div style="color:#79c0ff;padding:2px 0;">ℹ ' + escHtml(d.message) + '</div>');
                        analyzeLog.scrollTop(analyzeLog[0].scrollHeight);

                    } else if (d.type === 'translate_progress') {
                        analyzeIcon.text('🤖');
                        analyzeLabel.text('Traduction IA en cours');
                        analyzeCounter.text(d.current + ' / ' + d.total);
                        analyzeBar.css({width: (30 + (d.current / d.total * 40)) + '%', background:'#8250df'}).text('');
                        analyzeText.text(d.message);
                        analyzeLog.append('<div style="color:#d2a8ff;padding:2px 0;">🤖 ' + escHtml(d.message) + '</div>');
                        analyzeLog.scrollTop(analyzeLog[0].scrollHeight);

                    } else if (d.type === 'wc_progress') {
                        analyzeIcon.text('🗂️');
                        analyzeLabel.text('Catégories WooCommerce');
                        analyzeBar.css({width: (70 + (d.current / d.total * 30)) + '%', background:'#1a7f37'}).text('');
                        analyzeText.text(d.message);

                    } else if (d.type === 'warning') {
                        analyzeLog.append('<div style="color:#e3b341;padding:2px 0;">⚠ ' + escHtml(d.message) + '</div>');
                        analyzeLog.scrollTop(analyzeLog[0].scrollHeight);

                    } else if (d.type === 'complete') {
                        analyzeBar.css({width:'100%', background:'#00a32a'}).text('100%');
                        analyzeIcon.text('✅');
                        analyzeLabel.text('Terminé !');
                        analyzeCounter.text('');
                        analyzeText.text(d.message);
                        analyzeBtn.prop('disabled', false).text('🏷️ Analyser et traduire les catégories');

                        if (d.extra) {
                            var e = d.extra;
                            analyzeSummary.html(
                                '<strong>Catégories BIHR détectées :</strong> ' + (e.categories_detected || 0) + '<br>'
                                + '<strong>Chaînes uniques :</strong> ' + (e.unique_strings || 0) + '<br>'
                                + '<strong>Nouvelles traductions :</strong> ' + (e.new_translations || 0) + '<br>'
                                + '<strong>Traductions du cache :</strong> ' + (e.cached_translations || 0) + '<br>'
                                + '<strong>Catégories WooCommerce créées :</strong> ' + (e.wc_categories || 0) + '<br>'
                                + '<strong>Durée :</strong> ' + (e.elapsed || '?') + 's'
                            ).show();
                        }

                    } else if (d.type === 'error') {
                        analyzeBar.css({width:'100%', background:'#da3633'}).text('Erreur');
                        analyzeIcon.text('❌');
                        analyzeLabel.text('Erreur');
                        analyzeText.text(d.message);
                        analyzeBtn.prop('disabled', false).text('🏷️ Analyser et traduire les catégories');
                        analyzeLog.append('<div style="color:#f85149;margin-top:4px;">❌ ' + escHtml(d.message) + '</div>');
                        analyzeLog.scrollTop(analyzeLog[0].scrollHeight);
                    }
                } catch(e) {}
            });
        }

        analyzeBtn.on('click', function() {
            analyzeLastLen = 0;
            analyzeContainer.show();
            analyzeLog.html('<div style="color:#58a6ff;border-bottom:1px solid #21262d;padding-bottom:6px;margin-bottom:4px;">BIHR Category Translator — démarrage...</div>');
            analyzeSummary.hide();
            analyzeBtn.prop('disabled', true).text('⏳ Analyse en cours...');
            analyzeBar.css({width:'2%', background:'#2271b1'}).text('');
            analyzeCounter.text('');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: { action: 'bihrwi_analyze_categories', _wpnonce: analyzeNonce },
                xhrFields: {
                    onprogress: function() {
                        processAnalyzeLines(this.responseText);
                    }
                },
                complete: function(xhr) {
                    processAnalyzeLines(xhr.responseText);
                    analyzeBtn.prop('disabled', false);
                }
            });
        });

        // ─── Appliquer les catégories françaises ───────────────────────────

        var applyBtn       = $('#bihr-apply-french-btn');
        var applyContainer = $('#bihr-apply-progress');
        var applyBar       = $('#bihr-apply-bar');
        var applyText      = $('#bihr-apply-text');
        var applyIcon      = $('#bihr-apply-icon');
        var applyLabel     = $('#bihr-apply-label');
        var applyCounter   = $('#bihr-apply-counter');
        var applySummary   = $('#bihr-apply-summary');
        var applyLastLen   = 0;

        function processApplyLines(fullText) {
            var newText = fullText.slice(applyLastLen);
            applyLastLen = fullText.length;
            if (!newText) return;

            newText.split('\n').forEach(function(line) {
                try {
                    if (!line.trim()) return;
                    var d = JSON.parse(line);

                    if (d.type === 'status') {
                        applyIcon.text('⚙️');
                        applyLabel.text(d.message);
                        applyText.text(d.message);

                    } else if (d.type === 'progress') {
                        var pct = d.total > 0 ? Math.round(d.current / d.total * 100) : 0;
                        applyBar.css({width: pct + '%', background:'#0969da'}).text(pct + '%');
                        applyCounter.text(d.current.toLocaleString() + ' / ' + d.total.toLocaleString());
                        applyText.text(d.message);

                    } else if (d.type === 'complete') {
                        applyBar.css({width:'100%', background:'#00a32a'}).text('100%');
                        applyIcon.text('✅');
                        applyLabel.text('Terminé !');
                        applyCounter.text('');
                        applyText.text(d.message);
                        applyBtn.prop('disabled', false).text('✅ Appliquer les catégories françaises aux produits');

                        if (d.extra) {
                            var e = d.extra;
                            applySummary.html(
                                '<strong>Produits traités :</strong> ' + (e.total || 0) + '<br>'
                                + '<strong>Produits mis à jour :</strong> ' + (e.updated || 0) + '<br>'
                                + '<strong>Durée :</strong> ' + (e.elapsed || '?') + 's'
                            ).show();
                        }

                    } else if (d.type === 'error') {
                        applyBar.css({width:'100%', background:'#da3633'}).text('Erreur');
                        applyIcon.text('❌');
                        applyLabel.text('Erreur');
                        applyText.text(d.message);
                        applyBtn.prop('disabled', false);
                    }
                } catch(e) {}
            });
        }

        applyBtn.on('click', function() {
            applyLastLen = 0;
            applyContainer.show();
            applySummary.hide();
            applyBtn.prop('disabled', true).text('⏳ Application en cours...');
            applyBar.css({width:'2%', background:'#2271b1'}).text('');
            applyCounter.text('');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: { action: 'bihrwi_apply_french_categories', _wpnonce: applyNonce },
                xhrFields: {
                    onprogress: function() {
                        processApplyLines(this.responseText);
                    }
                },
                complete: function(xhr) {
                    processApplyLines(xhr.responseText);
                    applyBtn.prop('disabled', false);
                }
            });
        });

    });
    </script>

    <hr />

    <!-- =========================================================
         2. CATALOG PRICES (ASYNC)
    ========================================================== -->
    <h2>2. Catalog Prices (gestion asynchrone)</h2>

    <p>
        Le catalog <strong>Prices</strong> est spécifique à ton compte et peut prendre 30 à 60 minutes
        pour être généré lors de la première demande de la journée. Pour éviter les timeouts,
        la génération est surveillée en tâche de fond via WP-Cron.
    </p>

    <!-- Bouton pour démarrer la génération -->
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'bihrwi_start_prices_action', 'bihrwi_start_prices_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_start_prices_generation" />
        <?php submit_button( 'Lancer la génération du catalog Prices', 'secondary' ); ?>
    </form>

    <!-- Bouton pour exécuter immédiatement la tâche cron Prices -->
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;">
        <?php wp_nonce_field( 'bihrwi_run_prices_cron_now_action', 'bihrwi_run_prices_cron_now_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_run_prices_cron_now" />
        <?php submit_button( '⚙️ Exécuter la tâche planifiée Prices maintenant', 'secondary' ); ?>
    </form>

    <!-- Bouton pour vérifier immédiatement -->
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;">
        <?php wp_nonce_field( 'bihrwi_check_prices_now_action', 'bihrwi_check_prices_now_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_check_prices_now" />
        <?php submit_button( 'Vérifier maintenant si le catalog Prices est prêt', 'secondary' ); ?>
    </form>

    <!-- Planification automatique Prices -->
    <div class="bihr-section" style="margin-top:20px;">
        <h3>🗓️ Planifier la génération du catalog Prices</h3>
        <p>Choisissez un jour et une fréquence (toutes les semaines ou toutes les 2 semaines). Le plugin lancera la génération automatiquement.</p>
        <?php if ( isset($is_premium) && !$is_premium ) : ?>
            <div class="bihr-section" style="opacity:0.5; pointer-events:none; user-select:none; position:relative;">
                <div style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:2;"></div>
                <div class="notice notice-warning" style="pointer-events:auto;opacity:1;position:relative;z-index:3;"><p>La planification automatique du catalog Prices est réservée à la version Pro.<br><a href="<?php echo esc_url( bwi_fs()->get_upgrade_url() ); ?>" target="_blank" style="font-weight:bold;">Mettre à niveau vers le plan Pro</a> pour activer cette fonctionnalité.</p></div>
                <form method="post" action="#" class="bihr-filters-form">
                    <fieldset disabled style="border:0;padding:0;margin:0;">
                        <div class="bihr-filters-grid">
                            <div class="bihr-filter-field" style="grid-column: span 2;">
                                <label>
                                    <input type="checkbox" disabled />
                                    Activer la planification automatique
                                </label>
                            </div>
                            <div class="bihr-filter-field">
                                <label for="prices_schedule_weekday">Jour</label>
                                <select id="prices_schedule_weekday" disabled><option>Lundi</option></select>
                            </div>
                            <div class="bihr-filter-field">
                                <label for="prices_schedule_interval">Fréquence</label>
                                <select id="prices_schedule_interval" disabled><option>Chaque semaine</option></select>
                            </div>
                            <div class="bihr-filter-field">
                                <label for="prices_schedule_time">Heure</label>
                                <input type="time" id="prices_schedule_time" value="02:00" disabled />
                            </div>
                        </div>
                        <div class="bihr-filters-actions">
                            <button class="button button-primary" disabled>Enregistrer le planning Prices</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        <?php else : ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bihr-filters-form">
            <?php wp_nonce_field( 'bihrwi_prices_schedule_action', 'bihrwi_prices_schedule_nonce' ); ?>
            <input type="hidden" name="action" value="bihrwi_save_prices_schedule" />

            <div class="bihr-filters-grid">
                <div class="bihr-filter-field" style="grid-column: span 2;">
                    <label>
                        <input type="checkbox" name="prices_schedule_enabled" <?php checked( ! empty( $prices_schedule['enabled'] ) ); ?> />
                        Activer la planification automatique
                    </label>
                </div>

                <div class="bihr-filter-field">
                    <label for="prices_schedule_weekday">Jour</label>
                    <select name="prices_schedule_weekday" id="prices_schedule_weekday">
                        <?php
                        $days = array(
                            'monday'    => 'Lundi',
                            'tuesday'   => 'Mardi',
                            'wednesday' => 'Mercredi',
                            'thursday'  => 'Jeudi',
                            'friday'    => 'Vendredi',
                            'saturday'  => 'Samedi',
                            'sunday'    => 'Dimanche',
                        );
                        foreach ( $days as $key => $label ) {
                            printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $prices_schedule['weekday'] ?? 'monday', $key, false ), esc_html( $label ) );
                        }
                        ?>
                    </select>
                </div>

                <div class="bihr-filter-field">
                    <label for="prices_schedule_interval">Fréquence</label>
                    <select name="prices_schedule_interval" id="prices_schedule_interval">
                        <option value="weekly" <?php selected( $prices_schedule['interval'] ?? 'weekly', 'weekly' ); ?>>Chaque semaine</option>
                        <option value="biweekly" <?php selected( $prices_schedule['interval'] ?? 'weekly', 'biweekly' ); ?>>Toutes les 2 semaines</option>
                    </select>
                </div>

                <div class="bihr-filter-field">
                    <label for="prices_schedule_time">Heure</label>
                    <input type="time" name="prices_schedule_time" id="prices_schedule_time" value="<?php echo esc_attr( $prices_schedule['time'] ?? '02:00' ); ?>" />
                </div>
            </div>

            <div class="bihr-filters-actions">
                <?php submit_button( 'Enregistrer le planning Prices', 'primary', 'submit', false ); ?>
            </div>
        </form>
        <?php endif; ?>
        <p style="margin-top:8px;">
            <strong>Prochaine exécution planifiée :</strong>
            <?php if ( $next_prices_cron ) : ?>
                <?php echo esc_html( date_i18n( 'l d/m/Y H:i', $next_prices_cron ) ); ?>
                <?php if ( time() >= $next_prices_cron ) : ?>
                    <span style="color:#b45309;">(échéance dépassée — en attente de déclenchement)</span>
                <?php endif; ?>
            <?php else : ?>
                <em>Aucune planification active.</em>
            <?php endif; ?>
        </p>

        <div class="notice <?php echo esc_attr( $wp_cron_disabled ? 'notice-warning' : 'notice-success' ); ?>" style="padding:8px;">
            <p style="margin:0;">
                <strong>WP‑Cron :</strong>
                <?php echo esc_html( $wp_cron_disabled ? 'désactivé (DISABLE_WP_CRON) — utilisez un cron serveur' : 'actif' ); ?>
                <?php if ( ! empty( $prices_last_run ) ) : ?>
                    — Dernière exécution Prices : <em><?php echo esc_html( $prices_last_run ); ?></em>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <p>
        <?php if ( ! empty( $status_data['ticket_id'] ) ) : ?>
            <strong>TicketID actuel :</strong> <?php echo esc_html( $status_data['ticket_id'] ); ?><br />
            <?php if ( ! empty( $status_data['started_at'] ) ) : ?>
                <em>Démarré le : <?php echo esc_html( $status_data['started_at'] ); ?></em><br />
            <?php endif; ?>
            <?php if ( ! empty( $status_data['last_status'] ) ) : ?>
                <strong>Dernier statut :</strong> <?php echo esc_html( $status_data['last_status'] ); ?><br />
            <?php endif; ?>
            <?php if ( ! empty( $status_data['last_checked'] ) ) : ?>
                <em>Dernière vérification cron :</em> <?php echo esc_html( $status_data['last_checked'] ); ?><br />
            <?php endif; ?>
            Le plugin vérifie automatiquement le statut toutes les 5 minutes via WP-Cron
            et télécharge le fichier dès qu’il est prêt.
        <?php else : ?>
            Aucune génération de catalog Prices en cours actuellement.
        <?php endif; ?>
    </p>

    <hr />

    <!-- =========================================================
         3. PREVIEW TABLE wp_bihr_products
    ========================================================== -->

    <h2>3. Prévisualisation des produits Bihr (table wp_bihr_products)</h2>

    <p>
        <strong>Total :</strong> <?php echo intval( $total ); ?> produits
        <?php if ( ! empty( $filter_search ) || ! empty( $filter_stock ) || $filter_price_min !== '' || $filter_price_max !== '' || ! empty( $filter_category ) || ! empty( $sort_by ) ) : ?>
            (filtrés)
        <?php endif; ?>
    </p>

    <!-- Information sur l'enrichissement IA -->
    <?php
    $ai_enrichment = new BihrWI_AI_Enrichment( null );
    if ( ! $ai_enrichment->is_enabled() ) :
    ?>
        <div class="notice notice-info" style="margin: 20px 0;">
            <p>
                <strong>💡 Astuce :</strong> Vous pouvez activer l'enrichissement IA pour générer automatiquement des descriptions courtes et longues lors de l'importation des produits. 
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihr-auth' ) ); ?>">Configurez votre clé OpenAI ici</a>.
            </p>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="bihr-section">
        <h3>🔍 Filtres de recherche</h3>

        <!-- BOUTONS PRESET 2 ROUES -->
        <?php
        $base_url = admin_url( 'admin.php' );
        $vpa = 'VEHICLE PARTS & ACCESSORIES';
        $ta  = 'TIRES & ACCESSORIES';
        $ll  = 'LIQUIDS & LUBRICANTS';
        $bic_not = 'BICYCLE PARTS & ACCESSORIES||BICYCLE';

        $presets = array(
            array(
                'label'      => '🏍 Tout 2 roues',
                'cat_l1'     => $vpa . '||' . $ta . '||' . $ll,
                'cat_l2'     => '',
                'cat_l2_not' => $bic_not,
                'color'      => '#0073aa',
            ),
            array(
                'label'      => '🏍 Pneus Moto',
                'cat_l1'     => $ta,
                'cat_l2'     => 'MOTORCYCLE',
                'cat_l2_not' => '',
                'color'      => '#0073aa',
            ),
            array(
                'label'      => '🛵 Pneus Scooter',
                'cat_l1'     => $ta,
                'cat_l2'     => 'SCOOTER',
                'cat_l2_not' => '',
                'color'      => '#0073aa',
            ),
            array(
                'label'      => '🚵 Pneus Quad/ATV',
                'cat_l1'     => $ta,
                'cat_l2'     => 'ATV',
                'cat_l2_not' => '',
                'color'      => '#0073aa',
            ),
            array(
                'label'      => '🔧 Pièces mécaniques',
                'cat_l1'     => $vpa,
                'cat_l2'     => '',
                'cat_l2_not' => 'BICYCLE PARTS & ACCESSORIES',
                'color'      => '#46b450',
            ),
            array(
                'label'      => '🛢 Huiles & Liquides',
                'cat_l1'     => $ll,
                'cat_l2'     => '',
                'cat_l2_not' => '',
                'color'      => '#46b450',
            ),
        );

        $active_preset = '';
        foreach ( $presets as $p ) {
            if ( $p['cat_l1'] === $filter_cat_l1 && $p['cat_l2'] === $filter_cat_l2 && $p['cat_l2_not'] === $filter_cat_l2_not ) {
                $active_preset = $p['label'];
            }
        }
        ?>
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; align-items:center;">
            <strong style="margin-right:4px; font-size:13px; color:#444;">Raccourcis :</strong>
            <?php foreach ( $presets as $p ) :
                $url = add_query_arg( array(
                    'page'                    => 'bihr-products',
                    'cat_l1'                  => rawurlencode( $p['cat_l1'] ),
                    'cat_l2'                  => rawurlencode( $p['cat_l2'] ),
                    'cat_l3'                  => '',
                    'cat_l2_not'              => rawurlencode( $p['cat_l2_not'] ),
                    'bihrwi_filter_nonce_field' => wp_create_nonce( 'bihrwi_filter_nonce' ),
                ), admin_url( 'admin.php' ) );
                $is_active = ( $active_preset === $p['label'] );
            ?>
                <a href="<?php echo esc_url( $url ); ?>"
                   style="display:inline-block; padding:5px 12px; border-radius:4px; font-size:13px; text-decoration:none; border:2px solid <?php echo esc_attr( $p['color'] ); ?>;
                          background:<?php echo $is_active ? esc_attr( $p['color'] ) : '#fff'; ?>;
                          color:<?php echo $is_active ? '#fff' : esc_attr( $p['color'] ); ?>; font-weight:600;">
                    <?php echo esc_html( $p['label'] ); ?>
                </a>
            <?php endforeach; ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihr-products' ) ); ?>"
               style="display:inline-block; padding:5px 12px; border-radius:4px; font-size:13px; text-decoration:none; border:2px solid #999;
                      background:<?php echo empty( $active_preset ) && empty( $filter_cat_l1 ) && empty( $filter_cat_l2 ) ? '#999' : '#fff'; ?>;
                      color:<?php echo empty( $active_preset ) && empty( $filter_cat_l1 ) && empty( $filter_cat_l2 ) ? '#fff' : '#666'; ?>; font-weight:600;">
                ✕ Tout afficher
            </a>
        </div>

        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="bihr-filters-form" id="bihr-products-filters">
            <input type="hidden" name="page" value="bihr-products" />
            <input type="hidden" name="cat_l2_not" id="cat_l2_not_value" value="<?php echo esc_attr( isset( $filter_cat_l2_not ) ? $filter_cat_l2_not : '' ); ?>" />
            <?php wp_nonce_field( 'bihrwi_filter_nonce', 'bihrwi_filter_nonce_field' ); ?>
            
            <!-- Recherche (largeur complète) -->
            <div class="bihr-filter-field bihr-filter-search">
                <label for="search">
                    Recherche (code, NewPartNumber, nom, description)
                </label>
                <input type="text" 
                       name="search" 
                       id="search" 
                       value="<?php echo esc_attr( $filter_search ); ?>" 
                       placeholder="Saisir un mot-clé..." />
            </div>

            <!-- Grille des filtres 2-3 colonnes -->
            <div class="bihr-filters-grid">
                <div class="bihr-filter-field">
                    <label for="stock_filter">
                        Stock
                    </label>
                    <select name="stock_filter" id="stock_filter">
                        <option value="">Tous</option>
                        <option value="in_stock" <?php selected( $filter_stock, 'in_stock' ); ?>>En stock</option>
                        <option value="out_of_stock" <?php selected( $filter_stock, 'out_of_stock' ); ?>>Hors stock</option>
                    </select>
                </div>

                <div class="bihr-filter-field">
                    <label for="price_min">
                        Prix minimum (€)
                    </label>
                    <input type="number" 
                           name="price_min" 
                           id="price_min" 
                           value="<?php echo esc_attr( $filter_price_min ); ?>" 
                           placeholder="0.00"
                           step="0.01"
                           min="0" />
                </div>

                <div class="bihr-filter-field">
                    <label for="price_max">
                        Prix maximum (€)
                    </label>
                    <input type="number" 
                           name="price_max" 
                           id="price_max" 
                           value="<?php echo esc_attr( $filter_price_max ); ?>" 
                           placeholder="9999.99"
                           step="0.01"
                           min="0" />
                </div>

                <div class="bihr-filter-field">
                    <label for="category_filter">
                        Catégorie (Bihr)
                    </label>
                    <select name="category_filter" id="category_filter">
                        <option value="">Toutes</option>
                        <?php if ( ! empty( $available_categories ) ) : ?>
                            <?php foreach ( $available_categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_category, $cat ); ?>>
                                    <?php echo esc_html( $cat ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Filtres hiérarchiques basés sur les niveaux CategoryPath (cat_l1 / cat_l2 / cat_l3) -->
                <div class="bihr-filter-field">
                    <label>
                        Niveau 1 (CategoryPath)
                    </label>
                    <!-- Valeur réelle envoyée au backend (une seule valeur pour garder les performances actuelles) -->
                    <input type="hidden" name="cat_l1" id="cat_l1_value" value="<?php echo esc_attr( $filter_cat_l1 ); ?>" />
                    <div id="cat_l1_box" class="bihr-cat-checkbox-list" style="max-height: 160px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 6px; background: #fff;">
                        <?php if ( ! empty( $available_cat_l1 ) ) : ?>
                            <?php foreach ( $available_cat_l1 as $cat_l1_value ) : ?>
                                <label class="bihr-cat-checkbox-item" style="display:block; margin-bottom:2px;">
                                    <input type="checkbox"
                                           class="bihr-cat-l1-checkbox"
                                           data-value="<?php echo esc_attr( $cat_l1_value ); ?>"
                                           <?php checked( $filter_cat_l1, $cat_l1_value ); ?> />
                                    <?php echo esc_html( $cat_l1_value ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <em style="color:#666;">Aucune catégorie disponible.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bihr-filter-field">
                    <label>
                        Niveau 2
                    </label>
                    <input type="hidden" name="cat_l2" id="cat_l2_value" value="<?php echo esc_attr( $filter_cat_l2 ); ?>" />
                    <div id="cat_l2_box" class="bihr-cat-checkbox-list" style="max-height: 160px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 6px; background: #fff; <?php echo empty( $available_cat_l2 ) ? 'opacity:0.6;' : ''; ?>">
                        <?php if ( ! empty( $available_cat_l2 ) ) : ?>
                            <?php foreach ( $available_cat_l2 as $cat_l2_value ) : ?>
                                <label class="bihr-cat-checkbox-item" style="display:block; margin-bottom:2px;">
                                    <input type="checkbox"
                                           class="bihr-cat-l2-checkbox"
                                           data-value="<?php echo esc_attr( $cat_l2_value ); ?>"
                                           <?php checked( $filter_cat_l2, $cat_l2_value ); ?> />
                                    <?php echo esc_html( $cat_l2_value ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <em style="color:#666;">Choisissez d'abord un Niveau 1.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bihr-filter-field">
                    <label>
                        Niveau 3
                    </label>
                    <input type="hidden" name="cat_l3" id="cat_l3_value" value="<?php echo esc_attr( $filter_cat_l3 ); ?>" />
                    <div id="cat_l3_box" class="bihr-cat-checkbox-list" style="max-height: 160px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 6px; background: #fff; <?php echo empty( $available_cat_l3 ) ? 'opacity:0.6;' : ''; ?>">
                        <?php if ( ! empty( $available_cat_l3 ) ) : ?>
                            <?php foreach ( $available_cat_l3 as $cat_l3_value ) : ?>
                                <label class="bihr-cat-checkbox-item" style="display:block; margin-bottom:2px;">
                                    <input type="checkbox"
                                           class="bihr-cat-l3-checkbox"
                                           data-value="<?php echo esc_attr( $cat_l3_value ); ?>"
                                           <?php checked( $filter_cat_l3, $cat_l3_value ); ?> />
                                    <?php echo esc_html( $cat_l3_value ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <em style="color:#666;">Choisissez d'abord un Niveau 2.</em>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bihr-filter-field">
                    <label for="sort_by">
                        Trier par
                    </label>
                    <select name="sort_by" id="sort_by">
                        <option value="">Par défaut (ID)</option>
                        <option value="name_asc" <?php selected( $sort_by, 'name_asc' ); ?>>Nom (A → Z)</option>
                        <option value="name_desc" <?php selected( $sort_by, 'name_desc' ); ?>>Nom (Z → A)</option>
                        <option value="price_asc" <?php selected( $sort_by, 'price_asc' ); ?>>Prix croissant</option>
                        <option value="price_desc" <?php selected( $sort_by, 'price_desc' ); ?>>Prix décroissant</option>
                        <option value="stock_asc" <?php selected( $sort_by, 'stock_asc' ); ?>>Stock croissant</option>
                        <option value="stock_desc" <?php selected( $sort_by, 'stock_desc' ); ?>>Stock décroissant</option>
                    </select>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="bihr-filters-actions">
                <?php submit_button( 'Filtrer', 'secondary', 'submit', false ); ?>
                <?php
                $has_active_filter = ! empty( $filter_search ) || ! empty( $filter_stock ) || ! empty( $filter_price_min ) || ! empty( $filter_price_max ) || ! empty( $filter_category ) || ! empty( $sort_by ) || ! empty( $filter_cat_l1 ) || ! empty( $filter_cat_l2 ) || ! empty( $filter_cat_l3 ) || ! empty( $filter_cat_l2_not );
                if ( $has_active_filter ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihr-products' ) ); ?>" class="button">
                        Réinitialiser
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Barre de progression et import multiple -->
    <div id="bihr-import-progress" style="display:none; margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
        <h3 style="margin-top: 0;">Import en cours...</h3>
        <div style="background: #f0f0f1; height: 30px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
            <div id="bihr-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
        </div>
        <p id="bihr-progress-text">0 / 0 produits importés</p>
        <div id="bihr-progress-details" style="max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 10px; border-radius: 4px; font-size: 12px;"></div>
    </div>

    <!-- Barre de progression pour le téléchargement des images -->
    <div id="bihr-image-progress" style="display:none; margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
        <h3 style="margin-top: 0;">Téléchargement des images...</h3>
        <div style="background: #f0f0f1; height: 30px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
            <div id="bihr-image-progress-bar" style="background: #b32d2e; height: 100%; width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
        </div>
        <p id="bihr-image-progress-text">0 images téléchargées</p>
        <div id="bihr-image-progress-details" style="max-height: 120px; overflow-y: auto; background: #f9f9f9; padding: 10px; border-radius: 4px; font-size: 12px;"></div>
    </div>

    <div style="margin-bottom: 15px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <div>
            <button id="bihr-select-all" class="button" style="margin-right: 5px;">Sélectionner tout (page)</button>
            <button id="bihr-deselect-all" class="button" style="margin-right: 5px;">Désélectionner tout</button>
            <button id="bihr-import-selected" class="button button-primary" disabled>
                <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                Importer les produits sélectionnés (<span id="bihr-selected-count">0</span>)
            </button>
            <button id="bihr-import-all-filtered" class="button button-secondary" style="margin-left: 10px;">
                <span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span>
                Importer tous les produits filtrés
            </button>
            <label style="margin-left: 15px; font-weight: 600; cursor: pointer;" title="Importe les données produit sans télécharger les images — ×3 plus rapide. Téléchargez les images ensuite.">
                <input type="checkbox" id="bihr-skip-images" style="margin-right: 4px;">
                Sans images <span style="color:#2271b1;">(×3 plus rapide)</span>
            </label>
        </div>

        <?php
        $pending_img_count = (int) count( get_posts( array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array( 'key' => '_bihr_pending_image_url', 'compare' => 'EXISTS' ),
            ),
        ) ) );
        if ( $pending_img_count > 0 ) : ?>
        <div id="bihr-pending-images-banner" style="margin: 10px 0; padding: 10px 15px; background: #fff3cd; border-left: 4px solid #f0ad4e; border-radius: 2px;">
            <strong><?php echo intval( $pending_img_count ); ?> produit(s)</strong> sans image en attente de téléchargement.
            <button id="bihr-download-pending-images" class="button button-secondary" style="margin-left: 10px;">
                <span class="dashicons dashicons-format-image" style="vertical-align: middle;"></span>
                Télécharger les images manquantes
            </button>
        </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-left:auto; display:flex; align-items:center; gap:8px;">
            <input type="hidden" name="action" value="bihrwi_start_mass_import" />
            <?php wp_nonce_field( 'bihrwi_start_mass_import_action', 'bihrwi_start_mass_import_nonce' ); ?>
            <!-- Repasser les filtres actuels au handler -->
            <input type="hidden" name="search" value="<?php echo esc_attr( $filter_search ); ?>" />
            <input type="hidden" name="stock_filter" value="<?php echo esc_attr( $filter_stock ); ?>" />
            <input type="hidden" name="price_min" value="<?php echo esc_attr( $filter_price_min ); ?>" />
            <input type="hidden" name="price_max" value="<?php echo esc_attr( $filter_price_max ); ?>" />
            <input type="hidden" name="category_filter" value="<?php echo esc_attr( $filter_category ); ?>" />
            <input type="hidden" name="cat_l1" value="<?php echo esc_attr( $filter_cat_l1 ); ?>" />
            <input type="hidden" name="cat_l2" value="<?php echo esc_attr( $filter_cat_l2 ); ?>" />
            <input type="hidden" name="cat_l3" value="<?php echo esc_attr( $filter_cat_l3 ); ?>" />
            <input type="hidden" name="cat_l2_not" value="<?php echo esc_attr( isset( $filter_cat_l2_not ) ? $filter_cat_l2_not : '' ); ?>" />

            <button type="submit" class="button button-secondary" style="border-color:#2271b1; color:#2271b1;">
                <span class="dashicons dashicons-clock" style="vertical-align: middle;"></span>
                Import massif en tâche de fond (tous les produits filtrés)
            </button>
        </form>
    </div>

    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th style="width:40px;">
                    <input type="checkbox" id="bihr-select-all-checkbox" title="Tout sélectionner" />
                </th>
                <th style="width:60px;">ID</th>
                <th style="width:120px;">Code produit</th>
                <th>Nom</th>
                <th>Catégorie (Bihr)</th>
                <th>Niveau 1</th>
                <th>Niveau 2</th>
                <th>Niveau 3</th>
                <th>Description</th>
                <th style="width:80px;">Image</th>
                <th style="width:80px;">Stock</th>
                <th style="width:120px;">Prix HT (dealer)</th>
                <th style="width:160px;">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ( ! empty( $products ) ) : ?>
            <?php foreach ( $products as $row ) : ?>
                <tr>
                    <td>
                        <input type="checkbox" class="bihr-product-checkbox" 
                               value="<?php echo intval( $row->id ); ?>" 
                               data-name="<?php echo esc_attr( $row->name ?: $row->product_code ); ?>" />
                    </td>
                    <td><?php echo intval( $row->id ); ?></td>
                    <td><?php echo esc_html( ! empty( $row->new_part_number ) ? $row->new_part_number : $row->product_code ); ?></td>
                    <td><?php echo esc_html( $row->name ); ?></td>
                    <td>
                        <?php
                        if ( ! empty( $row->category ) ) {
                            echo esc_html( $row->category );
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php echo ! empty( $row->cat_l1 ) ? esc_html( $row->cat_l1 ) : '&mdash;'; ?>
                    </td>
                    <td>
                        <?php echo ! empty( $row->cat_l2 ) ? esc_html( $row->cat_l2 ) : '&mdash;'; ?>
                    </td>
                    <td>
                        <?php echo ! empty( $row->cat_l3 ) ? esc_html( $row->cat_l3 ) : '&mdash;'; ?>
                    </td>
                    <td>
                        <?php
                        $desc = $row->description;
                        if ( $desc ) {
                            // On tronque un peu pour l'affichage
                            $desc = wp_trim_words( $desc, 30, '…' );
                            echo nl2br( esc_html( $desc ) );
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ( ! empty( $row->image_url ) ) {
                            $img_url = $row->image_url;
                            // Si ce n’est pas une URL absolue, on ajoute le préfixe https://api.mybihr.com
                            if ( ! preg_match( '#^https?://#i', $img_url ) ) {
                                $img_url = rtrim( BIHRWI_IMAGE_BASE_URL, '/' ) . '/' . ltrim( $img_url, '/' );
                            }
                            ?>
                            <img src="<?php echo esc_url( $img_url ); ?>" style="max-width:60px;height:auto;" />
                            <?php
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ( $row->stock_level !== null ) {
                            echo intval( $row->stock_level );
                            if ( ! empty( $row->stock_description ) ) {
                                echo '<br /><small>' . esc_html( $row->stock_description ) . '</small>';
                            }
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ( $row->dealer_price_ht !== null ) {
                            $price = (float) $row->dealer_price_ht;
                            echo esc_html( number_format( $price, 2, ',', ' ' ) ) . ' €';
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'bihrwi_import_product_action', 'bihrwi_import_product_nonce' ); ?>
                            <input type="hidden" name="action" value="bihrwi_import_product" />
                            <input type="hidden" name="bihrwi_product_id" value="<?php echo intval( $row->id ); ?>" />
                            <?php submit_button( 'Importer dans WooCommerce', 'secondary small', '', false ); ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr><td colspan="8">Aucun produit trouvé dans la table <code>wp_bihr_products</code>.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                // Préserver exactement les filtres actuellement dans l'URL.
                // Important: ne pas utiliser empty() (ex: "0" serait perdu).
                $allowed_keys = array( 'search', 'stock_filter', 'price_min', 'price_max', 'category_filter', 'sort_by', 'cat_l1', 'cat_l2', 'cat_l3' );
                $params = array( 'page' => 'bihr-products' );

                foreach ( $allowed_keys as $key ) {
                    if ( isset( $_GET[ $key ] ) && wp_unslash( $_GET[ $key ] ) !== '' ) {
                        $params[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
                    }
                }

                $base_admin_url = admin_url( 'admin.php' );

                if ( $current_page > 1 ) {
                    $prev_params = array_merge( $params, array( 'paged' => $current_page - 1 ) );
                    $prev_url = $base_admin_url . '?' . http_build_query( $prev_params, '', '&', PHP_QUERY_RFC3986 );
                    echo '<a class="button" href="' . esc_url( $prev_url ) . '">&laquo; Page précédente</a> ';
                }

                // Saisie directe du numéro de page
                echo '<span style="margin-left:10px; margin-right:10px;">';
                echo 'Page <input type="number" id="bihr-goto-page" min="1" max="' . intval( $total_pages ) . '" value="' . intval( $current_page ) . '" style="width:60px; text-align:center;" />';
                echo ' / ' . intval( $total_pages );
                echo ' <button type="button" id="bihr-goto-page-btn" class="button" style="margin-left:5px;">Aller</button>';
                echo '</span>';

                if ( $current_page < $total_pages ) {
                    $next_params = array_merge( $params, array( 'paged' => $current_page + 1 ) );
                    $next_url = $base_admin_url . '?' . http_build_query( $next_params, '', '&', PHP_QUERY_RFC3986 );
                    echo '<a class="button" href="' . esc_url( $next_url ) . '">Page suivante &raquo;</a>';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>

</div>
