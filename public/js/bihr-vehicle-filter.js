jQuery(document).ready(function($) {
    
    console.log('BIHR Vehicle Filter loaded');

    const ajaxUrl = bihrVehicleFilter.ajaxurl;
    const nonce = bihrVehicleFilter.nonce;

    // Éléments du formulaire
    const $form = $('#bihr-vehicle-filter-form');
    const $manufacturer = $('#bihr-manufacturer');
    const $model = $('#bihr-model');
    const $year = $('#bihr-year');
    const $results = $('#bihr-filter-results');
    const $resultsContent = $('.bihr-results-content');
    const $resultsCount = $('.bihr-results-count');
    const $resetBtn = $('#bihr-reset-filter');

    /**
     * Charge les fabricants au chargement de la page
     */
    function loadManufacturers() {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bihr_get_manufacturers',
                nonce: nonce
            },
            success: function(response) {
                if (response.success && response.data.manufacturers) {
                    $manufacturer.empty().append('<option value="">-- Sélectionnez un fabricant --</option>');
                    
                    response.data.manufacturers.forEach(function(manu) {
                        $manufacturer.append(
                            $('<option></option>')
                                .val(manu.manufacturer_code)
                                .text(manu.manufacturer_name)
                        );
                    });
                }
            },
            error: function() {
                console.error('Erreur lors du chargement des fabricants');
            }
        });
    }

    /**
     * Charge les modèles pour un fabricant
     */
    function loadModels(manufacturerCode) {
        $model.prop('disabled', true).empty().append('<option value="">⏳ Chargement...</option>');
        $year.prop('disabled', true).empty().append('<option value="">-- Sélectionnez d\'abord un modèle --</option>');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bihr_get_models',
                nonce: nonce,
                manufacturer: manufacturerCode
            },
            success: function(response) {
                if (response.success && response.data.models) {
                    $model.empty().append('<option value="">-- Sélectionnez un modèle --</option>');
                    
                    response.data.models.forEach(function(model) {
                        $model.append(
                            $('<option></option>')
                                .val(model.commercial_model_code)
                                .text(model.commercial_model_name)
                        );
                    });
                    
                    $model.prop('disabled', false);
                }
            },
            error: function() {
                console.error('Erreur lors du chargement des modèles');
                $model.empty().append('<option value="">❌ Erreur de chargement</option>');
            }
        });
    }

    /**
     * Charge les années pour un modèle
     */
    function loadYears(manufacturerCode, modelCode) {
        $year.prop('disabled', true).empty().append('<option value="">⏳ Chargement...</option>');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bihr_get_years',
                nonce: nonce,
                manufacturer: manufacturerCode,
                model: modelCode
            },
            success: function(response) {
                if (response.success && response.data.years) {
                    $year.empty().append('<option value="">-- Sélectionnez une année --</option>');
                    
                    response.data.years.forEach(function(yearData) {
                        const label = yearData.vehicle_year + 
                            (yearData.version_name ? ' - ' + yearData.version_name : '');
                        
                        $year.append(
                            $('<option></option>')
                                .val(yearData.vehicle_code)
                                .text(label)
                        );
                    });
                    
                    $year.prop('disabled', false);
                }
            },
            error: function() {
                console.error('Erreur lors du chargement des années');
                $year.empty().append('<option value="">❌ Erreur de chargement</option>');
            }
        });
    }

    /**
     * Filtre les produits par véhicule
     */
    function filterProducts(vehicleCode) {
        $resultsContent.html('<div class="bihr-loading">⏳ Recherche des pièces compatibles...</div>');
        $results.show();

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'bihr_filter_products',
                nonce: nonce,
                vehicle_code: vehicleCode
            },
            success: function(response) {
                if (response.success) {
                    const count = response.data.count;
                    const products = response.data.products;

                    $resultsCount.text(count + ' pièce(s) trouvée(s)');

                    if (count === 0) {
                        $resultsContent.html(
                            '<div class="bihr-no-results">' +
                            '<p>😔 ' + (response.data.message || 'Aucune pièce compatible trouvée pour ce véhicule.') + '</p>' +
                            '</div>'
                        );
                        return;
                    }

                    // Afficher les produits
                    let html = '<div class="bihr-products-grid">';
                    
                    products.forEach(function(product) {
                        html += '<div class="bihr-product-card">';
                        
                        if (product.image) {
                            html += '<a href="' + product.url + '" class="bihr-product-image">';
                            html += '<img src="' + product.image + '" alt="' + product.title + '">';
                            html += '</a>';
                        }
                        
                        html += '<div class="bihr-product-details">';
                        html += '<h4><a href="' + product.url + '">' + product.title + '</a></h4>';
                        
                        if (product.sku) {
                            html += '<p class="bihr-product-sku">Réf: ' + product.sku + '</p>';
                        }
                        
                        html += '<div class="bihr-product-price">' + product.price + '</div>';
                        html += '<a href="' + product.url + '" class="button bihr-view-product">Voir le produit</a>';
                        html += '</div>';
                        html += '</div>';
                    });
                    
                    html += '</div>';
                    
                    $resultsContent.html(html);
                } else {
                    $resultsContent.html('<div class="bihr-error">❌ ' + (response.data.message || 'Erreur') + '</div>');
                }
            },
            error: function() {
                $resultsContent.html('<div class="bihr-error">❌ Erreur de connexion</div>');
            }
        });
    }

    /**
     * Réinitialise le filtre
     */
    function resetFilter() {
        $manufacturer.val('');
        $model.prop('disabled', true).empty().append('<option value="">-- Sélectionnez d\'abord un fabricant --</option>');
        $year.prop('disabled', true).empty().append('<option value="">-- Sélectionnez d\'abord un modèle --</option>');
        $results.hide();
        $resultsContent.empty();
    }

    // ========================================
    // EVENT HANDLERS
    // ========================================

    // Changement de fabricant
    $manufacturer.on('change', function() {
        const manufacturerCode = $(this).val();
        
        if (manufacturerCode) {
            loadModels(manufacturerCode);
        } else {
            $model.prop('disabled', true).empty().append('<option value="">-- Sélectionnez d\'abord un fabricant --</option>');
            $year.prop('disabled', true).empty().append('<option value="">-- Sélectionnez d\'abord un modèle --</option>');
        }
        
        $results.hide();
    });

    // Changement de modèle
    $model.on('change', function() {
        const manufacturerCode = $manufacturer.val();
        const modelCode = $(this).val();
        
        if (manufacturerCode && modelCode) {
            loadYears(manufacturerCode, modelCode);
        } else {
            $year.prop('disabled', true).empty().append('<option value="">-- Sélectionnez d\'abord un modèle --</option>');
        }
        
        $results.hide();
    });

    // Soumission du formulaire
    $form.on('submit', function(e) {
        e.preventDefault();
        
        const vehicleCode = $year.val();
        
        if (!vehicleCode) {
            alert('Veuillez sélectionner un véhicule complet (fabricant, modèle et année).');
            return;
        }
        
        filterProducts(vehicleCode);
    });

    // Bouton de réinitialisation
    $resetBtn.on('click', function(e) {
        e.preventDefault();
        resetFilter();
    });

    // ========================================
    // INITIALISATION
    // ========================================

    loadManufacturers();
});
