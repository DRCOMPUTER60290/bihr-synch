/**
 * Gestion des filtres de catégories dépendants (Niveau 1 -> Niveau 2 -> Niveau 3)
 * et du recalcul des catégories depuis cat-ref-full-*.csv
 */
(function($) {
    'use strict';

    /**
     * Charge les options d'un niveau enfant via AJAX
     * et remplit la liste de cases à cocher correspondante.
     *
     * @param {number} level Niveau 2 ou 3
     * @param {string} cat_l1 Valeur de niveau 1
     * @param {string} cat_l2 Valeur de niveau 2 (pour charger niveau 3)
     * @param {string} selectedValue Valeur à pré‑sélectionner (optionnel)
     */
    function loadCatChildren(level, cat_l1, cat_l2, selectedValue) {
        var $targetBox;
        var checkboxClass;

        if (level === 2) {
            $targetBox = $('#cat_l2_box');
            checkboxClass = 'bihr-cat-l2-checkbox';
        } else if (level === 3) {
            $targetBox = $('#cat_l3_box');
            checkboxClass = 'bihr-cat-l3-checkbox';
        } else {
            return;
        }

        $targetBox.css('opacity', 0.6).html('<em style="color:#666;">Chargement...</em>');

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
                        var html = '';
                        $.each(response.data.options, function(i, opt) {
                            var checked = (selectedValue && opt.value === selectedValue) ? ' checked="checked"' : '';
                            html += '<label class="bihr-cat-checkbox-item" style="display:block; margin-bottom:2px;">';
                            html += '<input type="checkbox" class="' + checkboxClass + '" data-value="' + opt.value + '"' + checked + ' /> ';
                            html += opt.label + '</label>';
                        });
                        $targetBox.html(html).css('opacity', 1);
                    } else {
                        $targetBox.html('<em style="color:#666;">Aucune option disponible.</em>').css('opacity', 0.6);
                    }
                },
                error: function() {
                    $targetBox.html('<em style="color:#666;">Erreur de chargement.</em>').css('opacity', 0.6);
                }
            });
    }

    /**
     * Initialise les filtres dépendants (Niveau 1/2/3) en cases à cocher
     */
    function initCategoryFilters() {
        var $catL1Hidden = $('#cat_l1_value');
        var $catL2Hidden = $('#cat_l2_value');
        var $catL3Hidden = $('#cat_l3_value');

        // Récupérer les valeurs depuis l'URL (persistance)
        var urlParams   = new URLSearchParams(window.location.search);
        var selectedL1  = urlParams.get('cat_l1') || '';
        var selectedL2  = urlParams.get('cat_l2') || '';
        var selectedL3  = urlParams.get('cat_l3') || '';

        // Appliquer les valeurs sélectionnées aux inputs cachés (au cas où)
        if (selectedL1) {
            $catL1Hidden.val(selectedL1);
            $('.bihr-cat-l1-checkbox').each(function() {
                var val = $(this).data('value') + '';
                $(this).prop('checked', val === selectedL1);
            });
            // Charger Niveau 2, puis éventuellement Niveau 3
            loadCatChildren(2, selectedL1, '', selectedL2);
            if (selectedL2) {
                loadCatChildren(3, selectedL1, selectedL2, selectedL3);
            }
        }
        if (selectedL2) {
            $catL2Hidden.val(selectedL2);
        }
        if (selectedL3) {
            $catL3Hidden.val(selectedL3);
        }

        // Gestion des clics sur Niveau 1 : comportement type "radio" avec case à cocher
        $(document).on('change', '.bihr-cat-l1-checkbox', function() {
            var $this = $(this);
            var val   = $this.data('value') + '';

            // Ne permettre qu'une seule valeur sélectionnée
            $('.bihr-cat-l1-checkbox').not($this).prop('checked', false);

            if ($this.is(':checked')) {
                $catL1Hidden.val(val);
                // Reset N2 et N3
                $catL2Hidden.val('');
                $catL3Hidden.val('');
                $('#cat_l2_box').html('<em style="color:#666;">Chargement...</em>').css('opacity', 0.6);
                $('#cat_l3_box').html('<em style="color:#666;">Choisissez d\'abord un Niveau 2.</em>').css('opacity', 0.6);
                loadCatChildren(2, val, '', '');
            } else {
                // Plus aucune valeur sélectionnée
                $catL1Hidden.val('');
                $catL2Hidden.val('');
                $catL3Hidden.val('');
                $('#cat_l2_box').html('<em style="color:#666;">Choisissez d\'abord un Niveau 1.</em>').css('opacity', 0.6);
                $('#cat_l3_box').html('<em style="color:#666;">Choisissez d\'abord un Niveau 2.</em>').css('opacity', 0.6);
            }
        });

        // Gestion des clics sur Niveau 2
        $(document).on('change', '.bihr-cat-l2-checkbox', function() {
            var $this = $(this);
            var val   = $this.data('value') + '';
            var catL1 = $catL1Hidden.val() || '';

            // Ne permettre qu'une seule valeur sélectionnée
            $('.bihr-cat-l2-checkbox').not($this).prop('checked', false);

            if ($this.is(':checked')) {
                $catL2Hidden.val(val);
                $catL3Hidden.val('');
                $('#cat_l3_box').html('<em style="color:#666;">Chargement...</em>').css('opacity', 0.6);
                if (catL1) {
                    loadCatChildren(3, catL1, val, '');
                }
            } else {
                $catL2Hidden.val('');
                $catL3Hidden.val('');
                $('#cat_l3_box').html('<em style="color:#666;">Choisissez d\'abord un Niveau 2.</em>').css('opacity', 0.6);
            }
        });

        // Gestion des clics sur Niveau 3
        $(document).on('change', '.bihr-cat-l3-checkbox', function() {
            var $this = $(this);
            var val   = $this.data('value') + '';

            // Ne permettre qu'une seule valeur sélectionnée
            $('.bihr-cat-l3-checkbox').not($this).prop('checked', false);

            if ($this.is(':checked')) {
                $catL3Hidden.val(val);
            } else {
                $catL3Hidden.val('');
            }
        });

        // S'assurer que les valeurs cachées sont bien synchronisées avant l'envoi du formulaire "Filtrer"
        $('#bihr-products-filters').on('submit', function() {
            var selectedL1 = $('.bihr-cat-l1-checkbox:checked').first().data('value') || '';
            var selectedL2 = $('.bihr-cat-l2-checkbox:checked').first().data('value') || '';
            var selectedL3 = $('.bihr-cat-l3-checkbox:checked').first().data('value') || '';

            $('#cat_l1_value').val(selectedL1 || '');
            $('#cat_l2_value').val(selectedL2 || '');
            $('#cat_l3_value').val(selectedL3 || '');
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
