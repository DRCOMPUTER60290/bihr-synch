/**
 * Gestion des filtres de catégories dépendants (Niveau 1 -> Niveau 2 -> Niveau 3)
 * et du recalcul des catégories depuis cat-ref-full-*.csv
 */
(function($) {
    'use strict';

    /**
     * Charge les options d'un niveau enfant via AJAX
     */
    function loadCatChildren(level, cat_l1, cat_l2, targetSelect) {
        var $target = $(targetSelect);
        $target.prop('disabled', true).html('<option value="">Chargement...</option>');

        $.ajax({
            url: bihrCategoryFiltersData.ajaxurl,
            type: 'POST',
            data: {
                action: 'bihr_get_cat_children',
                nonce: bihrCategoryFiltersData.nonce,
                level: level,
                cat_l1: cat_l1 || '',
                cat_l2: cat_l2 || ''
            },
            success: function(response) {
                if (response.success && response.data.options) {
                    var options = '<option value="">Toutes</option>';
                    $.each(response.data.options, function(i, opt) {
                        options += '<option value="' + opt.value + '">' + opt.label + '</option>';
                    });
                    $target.html(options).prop('disabled', false);
                } else {
                    $target.html('<option value="">Aucune option</option>').prop('disabled', true);
                }
            },
            error: function() {
                $target.html('<option value="">Erreur de chargement</option>').prop('disabled', true);
            }
        });
    }

    /**
     * Initialise les filtres dépendants
     */
    function initCategoryFilters() {
        var $catL1 = $('#cat_l1');
        var $catL2 = $('#cat_l2');
        var $catL3 = $('#cat_l3');

        // Récupérer les valeurs depuis l'URL (persistance)
        var urlParams = new URLSearchParams(window.location.search);
        var selectedL1 = urlParams.get('cat_l1') || '';
        var selectedL2 = urlParams.get('cat_l2') || '';
        var selectedL3 = urlParams.get('cat_l3') || '';

        // Si des valeurs sont présentes dans l'URL, les pré-remplir
        if (selectedL1) {
            $catL1.val(selectedL1);
            // Charger Niveau 2
            $.ajax({
                url: bihrCategoryFiltersData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bihr_get_cat_children',
                    nonce: bihrCategoryFiltersData.nonce,
                    level: 2,
                    cat_l1: selectedL1,
                    cat_l2: ''
                },
                success: function(response) {
                    if (response.success && response.data.options) {
                        var options = '<option value="">Toutes</option>';
                        $.each(response.data.options, function(i, opt) {
                            options += '<option value="' + opt.value + '"' + (opt.value === selectedL2 ? ' selected' : '') + '>' + opt.label + '</option>';
                        });
                        $catL2.html(options).prop('disabled', false);
                        
                        // Si N2 est sélectionné, charger N3
                        if (selectedL2) {
                            $.ajax({
                                url: bihrCategoryFiltersData.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'bihr_get_cat_children',
                                    nonce: bihrCategoryFiltersData.nonce,
                                    level: 3,
                                    cat_l1: selectedL1,
                                    cat_l2: selectedL2
                                },
                                success: function(response3) {
                                    if (response3.success && response3.data.options) {
                                        var options3 = '<option value="">Toutes</option>';
                                        $.each(response3.data.options, function(i, opt) {
                                            options3 += '<option value="' + opt.value + '"' + (opt.value === selectedL3 ? ' selected' : '') + '>' + opt.label + '</option>';
                                        });
                                        $catL3.html(options3).prop('disabled', false);
                                    }
                                }
                            });
                        }
                    }
                }
            });
        }

        // Changement Niveau 1
        $catL1.on('change', function() {
            var catL1Value = $(this).val();
            
            // Reset N2 et N3
            $catL2.html('<option value="">Toutes</option>').prop('disabled', true);
            $catL3.html('<option value="">Toutes</option>').prop('disabled', true);

            if (catL1Value) {
                loadCatChildren(2, catL1Value, '', '#cat_l2');
            }
        });

        // Changement Niveau 2
        $catL2.on('change', function() {
            var catL1Value = $catL1.val();
            var catL2Value = $(this).val();
            
            // Reset N3
            $catL3.html('<option value="">Toutes</option>').prop('disabled', true);

            if (catL1Value && catL2Value) {
                loadCatChildren(3, catL1Value, catL2Value, '#cat_l3');
            }
        });
    }

    /**
     * Gestion du recalcul des catégories depuis cat-ref-full
     */
    function initRebuildCatLevels() {
        $('#bihr-rebuild-cat-levels-btn').on('click', function() {
            var $btn = $(this);
            var $progress = $('#bihr-rebuild-cat-progress');
            var $progressBar = $('#bihr-rebuild-cat-progress-bar');
            var $progressText = $('#bihr-rebuild-cat-progress-text');

            if (!confirm('Recalculer les catégories depuis cat-ref-full-*.csv ?\n\nCette opération peut prendre plusieurs minutes.')) {
                return;
            }

            $btn.prop('disabled', true).text('⏳ Recalcul en cours...');
            $progress.show();
            $progressBar.css('width', '0%');
            $progressText.text('Initialisation...');

            var offset = 0;
            var limit = 2000;
            var totalProcessed = 0;
            var totalUpdated = 0;
            var totalErrors = 0;
            var fileName = '';

            function processBatch() {
                $.ajax({
                    url: bihrCategoryFiltersData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bihr_rebuild_cat_levels',
                        nonce: bihrCategoryFiltersData.nonce,
                        offset: offset,
                        limit: limit
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            fileName = data.file || '';
                            totalProcessed += data.processed || 0;
                            totalUpdated += data.updated || 0;
                            totalErrors += data.errors || 0;
                            offset = data.offset || offset;

                            // Mise à jour de la barre de progression (estimation)
                            var progressPercent = Math.min(95, (offset / 100000) * 100); // Estimation basée sur 100k lignes max
                            $progressBar.css('width', progressPercent + '%');
                            $progressText.text(
                                'Traitement... ' + 
                                totalProcessed + ' lignes lues, ' + 
                                totalUpdated + ' produits mis à jour, ' + 
                                totalErrors + ' erreurs'
                            );

                            if (data.has_more) {
                                // Continuer avec le batch suivant
                                setTimeout(processBatch, 100);
                            } else {
                                // Terminé
                                $progressBar.css('width', '100%');
                                $progressText.text(
                                    '✓ Terminé ! ' + 
                                    totalProcessed + ' lignes lues, ' + 
                                    totalUpdated + ' produits mis à jour, ' + 
                                    totalErrors + ' erreurs' +
                                    (fileName ? ' (Fichier: ' + fileName + ')' : '')
                                );
                                $btn.prop('disabled', false).text('🔄 Recalculer catégories (cat-ref-full)');
                                
                                // Recharger la page après 2 secondes pour voir les nouveaux filtres
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2000);
                            }
                        } else {
                            // Erreur
                            $progressText.text('✗ Erreur: ' + (response.data.message || 'Erreur inconnue'));
                            $btn.prop('disabled', false).text('🔄 Recalculer catégories (cat-ref-full)');
                        }
                    },
                    error: function() {
                        $progressText.text('✗ Erreur de communication avec le serveur');
                        $btn.prop('disabled', false).text('🔄 Recalculer catégories (cat-ref-full)');
                    }
                });
            }

            // Démarrer le traitement
            processBatch();
        });
    }

    // Initialisation au chargement de la page
    $(document).ready(function() {
        initCategoryFilters();
        initRebuildCatLevels();
    });

})(jQuery);
