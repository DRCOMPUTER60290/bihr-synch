<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories_nonce = wp_create_nonce( 'bihrwi_categories_nonce' );
$analyze_nonce    = wp_create_nonce( 'bihrwi_analyze_categories_action' );
$apply_nonce      = wp_create_nonce( 'bihrwi_apply_french_categories_action' );
$ajax_url         = admin_url( 'admin-ajax.php' );
?>

<div class="wrap">
    <h1>BIHR – Catégories traduites</h1>

    <p>
        Table de correspondance entre les catégories BIHR (anglais technique) et les catégories françaises
        générées automatiquement pour WooCommerce.
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihrwi_products' ) ); ?>">← Retour aux produits BIHR</a>
    </p>

    <!-- Boutons d'action -->
    <div style="margin-bottom:16px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <button type="button" id="bihr-cat-analyze-btn" class="button button-primary">
            🏷️ Analyser et traduire les catégories
        </button>
        <button type="button" id="bihr-cat-apply-btn" class="button button-secondary">
            ✅ Appliquer aux produits
        </button>
        <button type="button" id="bihr-cat-export-btn" class="button button-secondary">
            📥 Exporter CSV
        </button>
        <button type="button" id="bihr-cat-clear-btn" class="button" style="color:#b91c1c;"
            onclick="return confirm('Supprimer tout le cache de traductions ?');">
            🗑️ Supprimer le cache
        </button>
    </div>

    <!-- Barre de progression (cachée par défaut) -->
    <div id="bihr-cat-progress-wrap" class="bihr-section" style="display:none; margin-bottom:16px;">
        <div id="bihr-cat-progress-phase" style="display:flex;align-items:center;gap:12px;margin-bottom:8px;font-weight:600;font-size:13px;">
            <span id="bihr-cat-progress-icon" style="font-size:18px;">⚙️</span>
            <span id="bihr-cat-progress-label">En cours...</span>
            <span id="bihr-cat-progress-counter" style="margin-left:auto;color:#666;font-weight:400;"></span>
        </div>
        <div class="bihr-progress-bar-wrapper">
            <div id="bihr-cat-progress-bar" class="bihr-progress-bar"></div>
        </div>
        <div id="bihr-cat-progress-text" class="bihr-progress-text" style="margin-bottom:10px;">Initialisation...</div>
        <div id="bihr-cat-log" style="
            height:180px; overflow-y:auto;
            background:#0d1117; color:#c9d1d9;
            border-radius:6px; border:1px solid #30363d;
            font-family:'Consolas','Monaco',monospace; font-size:12px;
            padding:8px 12px; line-height:1.7;
        ">
            <div style="color:#58a6ff;border-bottom:1px solid #21262d;padding-bottom:4px;margin-bottom:4px;">En attente...</div>
        </div>
        <div id="bihr-cat-summary" style="display:none; margin-top:10px; padding:10px 14px;
            background:#f0f6fc; border-left:4px solid #0969da; border-radius:4px; font-size:13px;">
        </div>
    </div>

    <!-- Recherche -->
    <div style="margin-bottom:12px; display:flex; gap:10px; align-items:center;">
        <input type="search" id="bihr-cat-search" class="regular-text"
            placeholder="Rechercher une catégorie..." style="max-width:320px;" />
        <button type="button" id="bihr-cat-search-btn" class="button">🔍 Rechercher</button>
        <span id="bihr-cat-count" style="color:#666; font-size:13px;"></span>
    </div>

    <!-- Tableau -->
    <table class="wp-list-table widefat fixed striped" id="bihr-cat-table">
        <thead>
            <tr>
                <th style="width:50%;">Catégorie BIHR (anglais)</th>
                <th style="width:50%;">Catégorie française (WooCommerce)</th>
            </tr>
        </thead>
        <tbody id="bihr-cat-tbody">
            <tr><td colspan="2" style="text-align:center;color:#666;">Chargement...</td></tr>
        </tbody>
        <tfoot>
            <tr>
                <th>Catégorie BIHR (anglais)</th>
                <th>Catégorie française (WooCommerce)</th>
            </tr>
        </tfoot>
    </table>

    <!-- Pagination -->
    <div id="bihr-cat-pagination" style="margin-top:10px; display:flex; gap:6px; align-items:center; flex-wrap:wrap;"></div>

</div>

<script type="text/javascript">
jQuery(document).ready(function($) {

    var categoriesNonce = '<?php echo esc_js( $categories_nonce ); ?>';
    var analyzeNonce    = '<?php echo esc_js( $analyze_nonce ); ?>';
    var applyNonce      = '<?php echo esc_js( $apply_nonce ); ?>';
    var ajaxUrl         = '<?php echo esc_js( $ajax_url ); ?>';

    var currentPage   = 1;
    var currentSearch = '';

    function escHtml(text) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(text || '')));
        return d.innerHTML;
    }

    // ─── Chargement du tableau ──────────────────────────────────────────────

    function loadTranslations(page, search) {
        currentPage   = page || 1;
        currentSearch = search !== undefined ? search : currentSearch;

        $.get(ajaxUrl, {
            action:    'bihrwi_get_category_translations',
            _wpnonce:  categoriesNonce,
            page_num:  currentPage,
            search:    currentSearch
        }, function(resp) {
            if (!resp.success) { return; }
            var d      = resp.data;
            var tbody  = $('#bihr-cat-tbody');
            tbody.empty();
            $('#bihr-cat-count').text('(' + d.total + ' entrée' + (d.total > 1 ? 's' : '') + ')');

            if (!d.items || Object.keys(d.items).length === 0) {
                tbody.html('<tr><td colspan="2" style="text-align:center;color:#666;">Aucune traduction trouvée. Lancez d\'abord "Analyser et traduire les catégories".</td></tr>');
            } else {
                $.each(d.items, function(en, fr) {
                    tbody.append(
                        '<tr>'
                        + '<td><code style="font-size:12px;">' + escHtml(en) + '</code></td>'
                        + '<td>' + escHtml(fr) + '</td>'
                        + '</tr>'
                    );
                });
            }

            // Pagination
            var pag = $('#bihr-cat-pagination');
            pag.empty();
            if (d.total_pages > 1) {
                if (currentPage > 1) {
                    pag.append($('<button>').addClass('button').text('← Précédent').on('click', function() {
                        loadTranslations(currentPage - 1);
                    }));
                }
                pag.append($('<span>').css({padding:'4px 8px', color:'#666'})
                    .text('Page ' + currentPage + ' / ' + d.total_pages));
                if (currentPage < d.total_pages) {
                    pag.append($('<button>').addClass('button').text('Suivant →').on('click', function() {
                        loadTranslations(currentPage + 1);
                    }));
                }
            }
        });
    }

    loadTranslations(1, '');

    // Recherche
    $('#bihr-cat-search-btn').on('click', function() {
        loadTranslations(1, $('#bihr-cat-search').val());
    });
    $('#bihr-cat-search').on('keypress', function(e) {
        if (e.which === 13) { loadTranslations(1, $(this).val()); }
    });

    // ─── Export CSV ─────────────────────────────────────────────────────────

    $('#bihr-cat-export-btn').on('click', function() {
        var url = ajaxUrl + '?action=bihrwi_export_category_mapping&_wpnonce=' + encodeURIComponent(categoriesNonce);
        window.location.href = url;
    });

    // ─── Suppression cache ───────────────────────────────────────────────────

    $('#bihr-cat-clear-btn').on('click', function() {
        $.post(ajaxUrl, {
            action: 'bihrwi_clear_category_mapping',
            _wpnonce: categoriesNonce
        }, function(resp) {
            if (resp.success) {
                alert(resp.data.message);
                loadTranslations(1, '');
            }
        });
    });

    // ─── Streaming helper ───────────────────────────────────────────────────

    function runStreaming(action, nonce, onLine, onDone) {
        var progressWrap = $('#bihr-cat-progress-wrap');
        var progressBar  = $('#bihr-cat-progress-bar');
        var progressText = $('#bihr-cat-progress-text');
        var progressIcon = $('#bihr-cat-progress-icon');
        var progressLbl  = $('#bihr-cat-progress-label');
        var progressCnt  = $('#bihr-cat-progress-counter');
        var logDiv       = $('#bihr-cat-log');
        var summary      = $('#bihr-cat-summary');

        progressWrap.show();
        logDiv.html('<div style="color:#58a6ff;border-bottom:1px solid #21262d;padding-bottom:4px;margin-bottom:4px;">Démarrage...</div>');
        summary.hide();
        progressBar.css({width:'2%', background:'#2271b1'}).text('');

        var lastLen = 0;

        function processLines(fullText) {
            var newText = fullText.slice(lastLen);
            lastLen = fullText.length;
            if (!newText) return;

            newText.split('\n').forEach(function(line) {
                try {
                    if (!line.trim()) return;
                    var d = JSON.parse(line);
                    onLine(d, progressBar, progressText, progressIcon, progressLbl, progressCnt, logDiv, summary);
                } catch(e) {}
            });
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { action: action, _wpnonce: nonce },
            xhrFields: {
                onprogress: function() { processLines(this.responseText); }
            },
            complete: function(xhr) {
                processLines(xhr.responseText);
                if (onDone) onDone();
            }
        });
    }

    // ─── Analyser et traduire ───────────────────────────────────────────────

    $('#bihr-cat-analyze-btn').on('click', function() {
        var btn = $(this).prop('disabled', true).text('⏳ Analyse en cours...');

        runStreaming('bihrwi_analyze_categories', analyzeNonce,
            function(d, bar, text, icon, lbl, cnt, log, summary) {
                if (d.type === 'file_start') {
                    icon.text('📂'); lbl.text('Lecture CSV'); cnt.text(d.current + '/' + d.total);
                    bar.css({width: (d.current/d.total*30)+'%', background:'#2271b1'});
                    text.text('Lecture : ' + d.message);
                    log.append('<div><span class="bihr-file-spinner" style="display:inline-block;width:12px;height:12px;border:2px solid #30363d;border-top-color:#58a6ff;border-radius:50%;animation:bihrSpin .8s linear infinite;"></span> ' + escHtml(d.message) + '</div>');
                } else if (d.type === 'file_done') {
                    log.find('.bihr-file-spinner').last().replaceWith('<span style="color:#3fb950;">✔</span>');
                } else if (d.type === 'status') {
                    icon.text('🔄'); lbl.text(d.message);
                    bar.css({width: (d.current/d.total*100)+'%', background:'#0969da'});
                    log.append('<div style="color:#79c0ff;">ℹ ' + escHtml(d.message) + '</div>');
                } else if (d.type === 'translate_progress') {
                    icon.text('🤖'); lbl.text('Traduction IA'); cnt.text(d.current+'/'+d.total);
                    bar.css({width: (30+d.current/d.total*40)+'%', background:'#8250df'});
                    text.text(d.message);
                    log.append('<div style="color:#d2a8ff;">🤖 ' + escHtml(d.message) + '</div>');
                } else if (d.type === 'wc_progress') {
                    icon.text('🗂️'); lbl.text('Catégories WC'); cnt.text(d.current+'/'+d.total);
                    bar.css({width: (70+d.current/d.total*30)+'%', background:'#1a7f37'});
                } else if (d.type === 'warning') {
                    log.append('<div style="color:#e3b341;">⚠ ' + escHtml(d.message) + '</div>');
                } else if (d.type === 'complete') {
                    bar.css({width:'100%', background:'#00a32a'}).text('100%');
                    icon.text('✅'); lbl.text('Terminé !'); cnt.text('');
                    text.text(d.message);
                    if (d.extra) {
                        var e = d.extra;
                        summary.html(
                            '<strong>Catégories détectées :</strong> ' + (e.categories_detected||0) + ' &nbsp;|&nbsp; '
                            + '<strong>Chaînes uniques :</strong> ' + (e.unique_strings||0) + ' &nbsp;|&nbsp; '
                            + '<strong>Nouvelles traductions :</strong> ' + (e.new_translations||0) + ' &nbsp;|&nbsp; '
                            + '<strong>Cache :</strong> ' + (e.cached_translations||0) + ' &nbsp;|&nbsp; '
                            + '<strong>Catégories WC :</strong> ' + (e.wc_categories||0) + ' &nbsp;|&nbsp; '
                            + '<strong>Durée :</strong> ' + (e.elapsed||'?') + 's'
                        ).show();
                    }
                } else if (d.type === 'error') {
                    bar.css({width:'100%', background:'#da3633'});
                    icon.text('❌'); lbl.text('Erreur');
                    text.text(d.message);
                    log.append('<div style="color:#f85149;">❌ ' + escHtml(d.message) + '</div>');
                }
                log.scrollTop(log[0].scrollHeight);
            },
            function() {
                btn.prop('disabled', false).text('🏷️ Analyser et traduire les catégories');
                loadTranslations(currentPage, currentSearch);
            }
        );
    });

    // ─── Appliquer aux produits ─────────────────────────────────────────────

    $('#bihr-cat-apply-btn').on('click', function() {
        var btn = $(this).prop('disabled', true).text('⏳ Application en cours...');

        runStreaming('bihrwi_apply_french_categories', applyNonce,
            function(d, bar, text, icon, lbl, cnt, log, summary) {
                if (d.type === 'status') {
                    icon.text('⚙️'); lbl.text(d.message); text.text(d.message);
                } else if (d.type === 'progress') {
                    var pct = d.total > 0 ? Math.round(d.current/d.total*100) : 0;
                    bar.css({width: pct+'%', background:'#0969da'}).text(pct+'%');
                    cnt.text(d.current.toLocaleString()+'/'+d.total.toLocaleString());
                    text.text(d.message);
                } else if (d.type === 'complete') {
                    bar.css({width:'100%', background:'#00a32a'}).text('100%');
                    icon.text('✅'); lbl.text('Terminé !'); cnt.text('');
                    text.text(d.message);
                    if (d.extra) {
                        var e = d.extra;
                        summary.html(
                            '<strong>Produits traités :</strong> ' + (e.total||0) + ' &nbsp;|&nbsp; '
                            + '<strong>Mis à jour :</strong> ' + (e.updated||0) + ' &nbsp;|&nbsp; '
                            + '<strong>Durée :</strong> ' + (e.elapsed||'?') + 's'
                        ).show();
                    }
                } else if (d.type === 'error') {
                    bar.css({width:'100%', background:'#da3633'});
                    icon.text('❌'); lbl.text('Erreur'); text.text(d.message);
                }
                log.scrollTop(log[0].scrollHeight);
            },
            function() {
                btn.prop('disabled', false).text('✅ Appliquer aux produits');
            }
        );
    });

});
</script>

<style>
@keyframes bihrSpin { to { transform: rotate(360deg); } }
#bihr-cat-table code { background: transparent; color: #555; }
</style>
