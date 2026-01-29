/**
 * BIHR Product Filter - Frontend JavaScript
 */
(function($) {
    'use strict';

    var ajaxUrl = bihrProductFilterData.ajaxurl;
    var nonce = bihrProductFilterData.nonce;

    var $form = $('#bihr-product-filter-form');
    var $catL1 = $('#bihr-cat-l1');
    var $catL2 = $('#bihr-cat-l2');
    var $catL3 = $('#bihr-cat-l3');
    var $results = $('#bihr-product-filter-results');
    var $resultsContent = $results.find('.bihr-results-content');
    var $resultsCount = $results.find('.bihr-results-count');

    /**
     * Charge les catégories niveau 1
     */
    function loadLevel1() {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bihr_get_product_cat_level1',
                nonce: nonce
            },
            success: function(response) {
                if (response.success && response.data.categories) {
                    var options = '<option value="">-- Toutes les catégories --</option>';
                    $.each(response.data.categories, function(i, cat) {
                        options += '<option value="' + escapeHtml(cat) + '">' + escapeHtml(cat) + '</option>';
                    });
                    $catL1.html(options);
                }
            },
            error: function() {
                $catL1.html('<option value="">Erreur de chargement</option>');
            }
        });
    }

    /**
     * Charge les catégories niveau 2
     */
    function loadLevel2(catL1Value) {
        if (!catL1Value) {
            $catL2.html('<option value="">-- Sélectionnez d\'abord un niveau 1 --</option>').prop('disabled', true);
            $catL3.html('<option value="">-- Sélectionnez d\'abord un niveau 2 --</option>').prop('disabled', true);
            return;
        }

        $catL2.prop('disabled', true).html('<option value="">Chargement...</option>');
        $catL3.html('<option value="">-- Sélectionnez d\'abord un niveau 2 --</option>').prop('disabled', true);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bihr_get_product_cat_level2',
                nonce: nonce,
                cat_l1: catL1Value
            },
            success: function(response) {
                if (response.success && response.data.categories && response.data.categories.length > 0) {
                    var options = '<option value="">-- Toutes --</option>';
                    $.each(response.data.categories, function(i, cat) {
                        options += '<option value="' + escapeHtml(cat) + '">' + escapeHtml(cat) + '</option>';
                    });
                    $catL2.html(options).prop('disabled', false);
                } else {
                    $catL2.html('<option value="">Aucune sous-catégorie</option>').prop('disabled', true);
                }
            },
            error: function() {
                $catL2.html('<option value="">Erreur de chargement</option>').prop('disabled', true);
            }
        });
    }

    /**
     * Charge les catégories niveau 3
     */
    function loadLevel3(catL1Value, catL2Value) {
        if (!catL1Value || !catL2Value) {
            $catL3.html('<option value="">-- Sélectionnez d\'abord un niveau 2 --</option>').prop('disabled', true);
            return;
        }

        $catL3.prop('disabled', true).html('<option value="">Chargement...</option>');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bihr_get_product_cat_level3',
                nonce: nonce,
                cat_l1: catL1Value,
                cat_l2: catL2Value
            },
            success: function(response) {
                if (response.success && response.data.categories && response.data.categories.length > 0) {
                    var options = '<option value="">-- Toutes --</option>';
                    $.each(response.data.categories, function(i, cat) {
                        options += '<option value="' + escapeHtml(cat) + '">' + escapeHtml(cat) + '</option>';
                    });
                    $catL3.html(options).prop('disabled', false);
                } else {
                    $catL3.html('<option value="">Aucune sous-sous-catégorie</option>').prop('disabled', true);
                }
            },
            error: function() {
                $catL3.html('<option value="">Erreur de chargement</option>').prop('disabled', true);
            }
        });
    }

    /**
     * Filtre les produits
     */
    function filterProducts() {
        var catL1 = $catL1.val();
        var catL2 = $catL2.val();
        var catL3 = $catL3.val();

        if (!catL1 && !catL2 && !catL3) {
            alert('Veuillez sélectionner au moins une catégorie.');
            return;
        }

        $resultsContent.html('<div class="bihr-loading">⏳ Recherche des produits...</div>');
        $results.show();

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bihr_filter_products_by_category',
                nonce: nonce,
                cat_l1: catL1,
                cat_l2: catL2,
                cat_l3: catL3
            },
            success: function(response) {
                if (response.success) {
                    const count = response.data.count;
                    const products = response.data.products;

                    $resultsCount.text(count + ' produit(s) trouvé(s)');

                    if (count === 0) {
                        $resultsContent.html(
                            '<div class="bihr-no-results">' +
                            '<p>😔 ' + (response.data.message || 'Aucun produit trouvé pour ces catégories.') + '</p>' +
                            '</div>'
                        );
                        return;
                    }

                    // Afficher les produits
                    let html = '<div class="bihr-products-grid">';
                    
                    $.each(products, function(i, product) {
                        html += '<div class="bihr-product-item">';
                        html += '<img src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.title) + '" class="bihr-product-image" />';
                        html += '<div class="bihr-product-title">';
                        html += '<a href="' + escapeHtml(product.url) + '">' + escapeHtml(product.title) + '</a>';
                        html += '</div>';
                        if (product.sku) {
                            html += '<div class="bihr-product-sku">Ref: ' + escapeHtml(product.sku) + '</div>';
                        }
                        html += '<div class="bihr-product-price">' + product.price + '</div>';
                        html += '</div>';
                    });
                    
                    html += '</div>';
                    $resultsContent.html(html);
                } else {
                    $resultsContent.html(
                        '<div class="bihr-no-results">' +
                        '<p>❌ Erreur: ' + (response.data.message || 'Erreur lors de la recherche') + '</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                $resultsContent.html(
                    '<div class="bihr-no-results">' +
                    '<p>❌ Erreur de connexion au serveur</p>' +
                    '</div>'
                );
            }
        });
    }

    /**
     * Échappe le HTML pour éviter les XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }

    /**
     * Réinitialise le filtre
     */
    function resetFilter() {
        $catL1.val('');
        $catL2.html('<option value="">-- Sélectionnez d\'abord un niveau 1 --</option>').prop('disabled', true);
        $catL3.html('<option value="">-- Sélectionnez d\'abord un niveau 2 --</option>').prop('disabled', true);
        $results.hide();
    }

    // Initialisation
    $(document).ready(function() {
        // Charger les catégories niveau 1 au démarrage
        loadLevel1();

        // Changement niveau 1
        $catL1.on('change', function() {
            var val = $(this).val();
            loadLevel2(val);
            $catL3.html('<option value="">-- Sélectionnez d\'abord un niveau 2 --</option>').prop('disabled', true);
        });

        // Changement niveau 2
        $catL2.on('change', function() {
            var valL1 = $catL1.val();
            var valL2 = $(this).val();
            loadLevel3(valL1, valL2);
        });

        // Soumission du formulaire
        $form.on('submit', function(e) {
            e.preventDefault();
            filterProducts();
        });

        // Bouton réinitialiser
        $('#bihr-reset-product-filter').on('click', function() {
            resetFilter();
        });
    });

})(jQuery);
