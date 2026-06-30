jQuery(document).ready(function($) {
    
    console.log('BIHR JS - Script chargé avec succès');
    console.log('BIHR JS - jQuery version:', $.fn.jquery);
    
    // ============================================
    // GESTION DE LA SÉLECTION MULTIPLE DE PRODUITS
    // ============================================
    
    var selectedProducts = [];
    var stopImport = false;
    
    // Fonction pour mettre à jour le compteur
    function updateSelectedCount() {
        var count = $('.bihr-product-checkbox:checked').length;
        var totalCheckboxes = $('.bihr-product-checkbox').length;
        
        console.log('BIHR Debug - Total checkboxes:', totalCheckboxes);
        console.log('BIHR Debug - Checked:', count);
        console.log('BIHR Debug - Button element:', $('#bihr-import-selected').length);
        console.log('BIHR Debug - Count element:', $('#bihr-selected-count').length);
        
        $('#bihr-selected-count').text(count);
        $('#bihr-import-selected').prop('disabled', count === 0);
        
        selectedProducts = [];
        $('.bihr-product-checkbox:checked').each(function() {
            selectedProducts.push({
                id: $(this).val(),
                name: $(this).data('name')
            });
        });
    }

    // Navigation directe vers une page
    $('#bihr-goto-page-btn').on('click', function() {
        var pageNum = parseInt($('#bihr-goto-page').val(), 10);
        var maxPages = parseInt($('#bihr-goto-page').attr('max'), 10);
        
        if (isNaN(pageNum) || pageNum < 1) {
            pageNum = 1;
        }
        if (pageNum > maxPages) {
            pageNum = maxPages;
        }
        
        // Récupérer les paramètres de l'URL actuelle
        var urlParams = new URLSearchParams(window.location.search);
        urlParams.set('paged', pageNum);
        
        window.location.href = window.location.pathname + '?' + urlParams.toString();
    });

    // Permettre d'appuyer sur Entrée dans le champ de saisie
    $('#bihr-goto-page').on('keypress', function(e) {
        if (e.which === 13) {
            $('#bihr-goto-page-btn').click();
        }
    });
    
    // Sélectionner tout
    $('#bihr-select-all, #bihr-select-all-checkbox').on('click', function(e) {
        if ($(this).is('#bihr-select-all-checkbox')) {
            var checked = $(this).prop('checked');
            $('.bihr-product-checkbox').prop('checked', checked);
        } else {
            e.preventDefault();
            $('.bihr-product-checkbox').prop('checked', true);
            $('#bihr-select-all-checkbox').prop('checked', true);
        }
        updateSelectedCount();
    });
    
    // Désélectionner tout
    $('#bihr-deselect-all').on('click', function(e) {
        e.preventDefault();
        $('.bihr-product-checkbox').prop('checked', false);
        $('#bihr-select-all-checkbox').prop('checked', false);
        updateSelectedCount();
    });
    
    // Changement de sélection
    $(document).on('change', '.bihr-product-checkbox', function() {
        updateSelectedCount();
        // Mettre à jour la checkbox "tout sélectionner"
        var totalCheckboxes = $('.bihr-product-checkbox').length;
        var checkedCheckboxes = $('.bihr-product-checkbox:checked').length;
        $('#bihr-select-all-checkbox').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Initialiser le compteur au chargement de la page
    updateSelectedCount();

    // ============================================
    // BOUTONS D'ARRÊT
    // ============================================

    $('#bihr-stop-import, #bihr-stop-images').on('click', function(e) {
        e.preventDefault();
        stopImport = true;

        var $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Arrêt en cours...');

        // Nettoyer la queue WP-Cron au cas où
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'bihrwi_stop_mass_import', nonce: bihrProgressData.nonce }
        });
    });

    // ============================================
    // IMPORT DES PRODUITS SÉLECTIONNÉS
    // ============================================
    $('#bihr-import-selected').on('click', function(e) {
        e.preventDefault();
        
        if (selectedProducts.length === 0) {
            alert('Veuillez sélectionner au moins un produit.');
            return;
        }
        
        if (!confirm('Voulez-vous importer ' + selectedProducts.length + ' produit(s) dans WooCommerce ?')) {
            return;
        }
        
        // Afficher la barre de progression
        var $progressContainer = $('#bihr-import-progress');
        var $progressBar = $('#bihr-progress-bar');
        var $progressText = $('#bihr-progress-text');
        var $progressDetails = $('#bihr-progress-details');
        
        stopImport = false;
        $progressContainer.show();
        $('#bihr-stop-import').show();
        $progressBar.css('width', '0%').text('0%');
        $progressText.text('0 / ' + selectedProducts.length + ' produits importés');
        $progressDetails.html('');

        // Désactiver les boutons
        $('#bihr-import-selected, #bihr-select-all, #bihr-deselect-all').prop('disabled', true);
        $('.bihr-product-checkbox').prop('disabled', true);
        
        // Importer les produits par batch pour accélérer l'import
        var currentIndex = 0;
        var successCount = 0;
        var errorCount = 0;
        var batchSize = 500; // Taille de batch cible

        function importNextProductBatch() {
            if (currentIndex >= selectedProducts.length) {
                // Terminé
                $progressBar.css('width', '100%').text('100%');
                var finalMsg = 'Import terminé : ' + successCount + ' succès';
                if (errorCount > 0) {
                    finalMsg += ', ' + errorCount + ' erreur(s)';
                }
                $progressText.html('<strong style="color: green;">' + finalMsg + '</strong>');
                $('#bihr-stop-import').hide();

                function reenableButtons() {
                    $('#bihr-import-selected, #bihr-select-all, #bihr-deselect-all').prop('disabled', false);
                    $('.bihr-product-checkbox').prop('disabled', false);
                    $('.bihr-product-checkbox:checked').each(function() {
                        var productId = $(this).val();
                        var wasSuccess = $('#bihr-success-' + productId).length > 0;
                        if (wasSuccess) {
                            $(this).prop('checked', false);
                        }
                    });
                    updateSelectedCount();
                }

                if (!$('#bihr-skip-images').is(':checked')) {
                    var $imageContainer = $('#bihr-image-progress');
                    var $imageBar = $('#bihr-image-progress-bar');
                    var $imageText = $('#bihr-image-progress-text');
                    var $imageDetails = $('#bihr-image-progress-details');

                    chainImageDownloadAfterImport($imageContainer, $imageBar, $imageText, $imageDetails, function() {
                        setTimeout(reenableButtons, 2000);
                    });
                } else {
                    setTimeout(reenableButtons, 2000);
                }

                return;
            }

            if (stopImport) {
                $progressBar.css('width', '0%').text('0%');
                $progressText.html('<strong style="color: #d63638;">⏹ Import arrêté par l\'utilisateur</strong>');
                $progressDetails.append('<div style="color:#d63638; font-weight:bold;">Import arrêté après ' + currentIndex + ' / ' + selectedProducts.length + ' produits.</div>');
                $('#bihr-stop-import, #bihr-stop-images').hide();
                $('#bihr-import-selected, #bihr-select-all, #bihr-deselect-all').prop('disabled', false);
                $('.bihr-product-checkbox').prop('disabled', false);
                updateSelectedCount();
                return;
            }

            // Construire le batch courant
            var batch = selectedProducts.slice(currentIndex, currentIndex + batchSize);
            var batchIds = [];

            // Ajouter une ligne de progression pour chaque produit du batch
            for (var i = 0; i < batch.length; i++) {
                var product = batch[i];
                batchIds.push(product.id);

                $progressDetails.append('<div id="bihr-import-' + product.id + '" style="padding: 5px; border-bottom: 1px solid #ddd;">' +
                    '<span class="dashicons dashicons-update" style="color: #2271b1; animation: rotation 1s infinite linear;"></span> ' +
                    '<strong>' + product.name + '</strong> (ID: ' + product.id + ') - Import en cours...' +
                    '</div>');
            }

            // Scroll vers le bas
            $progressDetails.scrollTop($progressDetails[0].scrollHeight);

            var skipImages = $('#bihr-skip-images').is(':checked') ? '1' : '0';

            // Appel AJAX pour importer le batch de produits
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bihrwi_import_products_batch',
                    product_ids: batchIds,
                    skip_images: skipImages,
                    nonce: bihrProgressData.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.results) {
                        for (var j = 0; j < response.data.results.length; j++) {
                            var res = response.data.results[j];
                            var lineId = '#bihr-import-' + res.product_id;
                            if (res.success) {
                                $(lineId).html(
                                    '<span id="bihr-success-' + res.product_id + '" class="dashicons dashicons-yes-alt" style="color: green;"></span> ' +
                                    '<strong>Produit ID ' + res.product_id + '</strong> - Importé (WC ID: ' + res.wc_id + ')'
                                );
                                successCount++;
                            } else {
                                $(lineId).html(
                                    '<span class="dashicons dashicons-dismiss" style="color: red;"></span> ' +
                                    '<strong>Produit ID ' + res.product_id + '</strong> - Erreur : ' + res.message
                                );
                                errorCount++;
                            }
                        }
                    } else {
                        for (var k = 0; k < batch.length; k++) {
                            var p = batch[k];
                            $('#bihr-import-' + p.id).html(
                                '<span class="dashicons dashicons-dismiss" style="color: red;"></span> ' +
                                '<strong>' + p.name + '</strong> - Erreur : réponse invalide du serveur'
                            );
                            errorCount++;
                        }
                    }
                },
                error: function() {
                    for (var k = 0; k < batch.length; k++) {
                        var p = batch[k];
                        $('#bihr-import-' + p.id).html(
                            '<span class="dashicons dashicons-dismiss" style="color: red;"></span> ' +
                            '<strong>' + p.name + '</strong> - Erreur de connexion'
                        );
                        errorCount++;
                    }
                },
                complete: function() {
                    currentIndex += batch.length;
                    var percent = Math.round((currentIndex / selectedProducts.length) * 100);
                    $progressBar.css('width', percent + '%').text(percent + '%');
                    $progressText.text(currentIndex + ' / ' + selectedProducts.length + ' produits importés');
                    importNextProductBatch();
                }
            });
        }

        // Démarrer l'import par batch
        importNextProductBatch();
    });

    // Import de tous les produits filtrés
    $('#bihr-import-all-filtered').on('click', function(e) {
        e.preventDefault();
        
        // Récupérer les filtres depuis l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var filters = {
            search: urlParams.get('search') || '',
            stock_filter: urlParams.get('stock_filter') || '',
            price_min: urlParams.get('price_min') || '',
            price_max: urlParams.get('price_max') || '',
            category_filter: urlParams.get('category_filter') || '',
            cat_l1: urlParams.get('cat_l1') || '',
            cat_l2: urlParams.get('cat_l2') || '',
            cat_l3: urlParams.get('cat_l3') || '',
            cat_l2_not: urlParams.get('cat_l2_not') || ''
        };
        
        // Désactiver le bouton
        var $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Récupération des produits...');
        
        // Récupérer tous les IDs filtrés
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bihr_get_all_filtered_ids',
                nonce: bihrProgressData.nonce,
                search: filters.search,
                stock_filter: filters.stock_filter,
                price_min: filters.price_min,
                price_max: filters.price_max,
                category_filter: filters.category_filter,
                cat_l1: filters.cat_l1,
                cat_l2: filters.cat_l2,
                cat_l3: filters.cat_l3,
                cat_l2_not: filters.cat_l2_not
            },
            success: function(response) {
                if (response.success && response.data.ids && response.data.ids.length > 0) {
                    var allIds = response.data.ids;
                    var count = allIds.length;
                    
                    if (!confirm('Voulez-vous importer ' + count + ' produit(s) correspondant aux filtres actuels dans WooCommerce ?\n\nCette opération peut prendre plusieurs minutes.')) {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span> Importer tous les produits filtrés');
                        return;
                    }
                    
                    // Construire la liste de produits (sans les noms, on utilisera juste les IDs)
                    var allProducts = [];
                    for (var i = 0; i < allIds.length; i++) {
                        allProducts.push({
                            id: allIds[i],
                            name: 'Produit ID ' + allIds[i]
                        });
                    }
                    
                    // Afficher la barre de progression
                    var $progressContainer = $('#bihr-import-progress');
                    var $progressBar = $('#bihr-progress-bar');
                    var $progressText = $('#bihr-progress-text');
                    var $progressDetails = $('#bihr-progress-details');
                    
                    stopImport = false;
                    $progressContainer.show();
                    $('#bihr-stop-import').show();
                    $progressBar.css('width', '0%').text('0%');
                    $progressText.text('0 / ' + count + ' produits importés');
                    $progressDetails.html('');

                    // Désactiver les boutons
                    $('#bihr-import-selected, #bihr-import-all-filtered, #bihr-select-all, #bihr-deselect-all').prop('disabled', true);
                    $('.bihr-product-checkbox').prop('disabled', true);
                    
                    // Importer les produits par batch pour accélérer l'import
                    var currentIndex = 0;
                    var successCount = 0;
                    var errorCount = 0;
                    var batchSize = 500; // Taille de batch cible

                    function importNextFilteredProductBatch() {
                        if (currentIndex >= allProducts.length) {
                            // Terminé
                            $progressBar.css('width', '100%').text('100%');
                            var finalMsg = 'Import terminé : ' + successCount + ' succès';
                            if (errorCount > 0) {
                                finalMsg += ', ' + errorCount + ' erreur(s)';
                            }
                            $progressText.html('<strong style="color: green;">' + finalMsg + '</strong>');
                            $('#bihr-stop-import').hide();

                            // Lancer le téléchargement des images si nécessaire
                            if (!$('#bihr-skip-images').is(':checked')) {
                                var $imageContainer = $('#bihr-image-progress');
                                var $imageBar = $('#bihr-image-progress-bar');
                                var $imageText = $('#bihr-image-progress-text');
                                var $imageDetails = $('#bihr-image-progress-details');

                                chainImageDownloadAfterImport($imageContainer, $imageBar, $imageText, $imageDetails, function() {
                                    setTimeout(function() {
                                        $('#bihr-import-selected, #bihr-import-all-filtered, #bihr-select-all, #bihr-deselect-all').prop('disabled', false);
                                        $('.bihr-product-checkbox').prop('disabled', false);
                                        $btn.html('<span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span> Importer tous les produits filtrés');
                                    }, 2000);
                                });
                            } else {
                                setTimeout(function() {
                                    $('#bihr-import-selected, #bihr-import-all-filtered, #bihr-select-all, #bihr-deselect-all').prop('disabled', false);
                                    $('.bihr-product-checkbox').prop('disabled', false);
                                    $btn.html('<span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span> Importer tous les produits filtrés');
                                }, 2000);
                            }

                            return;
                        }

                        if (stopImport) {
                            $progressBar.css('width', '0%').text('0%');
                            $progressText.html('<strong style="color: #d63638;">⏹ Import arrêté par l\'utilisateur</strong>');
                            $progressDetails.append('<div style="color:#d63638; font-weight:bold;">Import arrêté après ' + currentIndex + ' / ' + allProducts.length + ' produits.</div>');
                            $('#bihr-stop-import, #bihr-stop-images').hide();
                            $('#bihr-import-selected, #bihr-import-all-filtered, #bihr-select-all, #bihr-deselect-all').prop('disabled', false);
                            $('.bihr-product-checkbox').prop('disabled', false);
                            $btn.html('<span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span> Importer tous les produits filtrés');
                            return;
                        }

                        // Construire le batch courant
                        var batch = allProducts.slice(currentIndex, currentIndex + batchSize);
                        var batchIds = [];

                        // Ajouter une ligne de progression pour chaque produit du batch
                        for (var i = 0; i < batch.length; i++) {
                            var product = batch[i];
                            batchIds.push(product.id);

                            $progressDetails.append('<div id="bihr-import-' + product.id + '" style="padding: 5px; border-bottom: 1px solid #ddd;">' +
                                '<span class="dashicons dashicons-update" style="color: #2271b1; animation: rotation 1s infinite linear;"></span> ' +
                                '<strong>' + product.name + '</strong> - Import en cours...' +
                                '</div>');
                        }

                        // Scroll vers le bas
                        $progressDetails.scrollTop($progressDetails[0].scrollHeight);

                        var skipImagesFiltered = $('#bihr-skip-images').is(':checked') ? '1' : '0';

                        // Appel AJAX pour importer le batch de produits
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'bihrwi_import_products_batch',
                                product_ids: batchIds,
                                skip_images: skipImagesFiltered,
                                nonce: bihrProgressData.nonce
                            },
                            success: function(response) {
                                if (response.success && response.data && response.data.results) {
                                    for (var j = 0; j < response.data.results.length; j++) {
                                        var res = response.data.results[j];
                                        var lineId = '#bihr-import-' + res.product_id;
                                        if (res.success) {
                                            $(lineId).html(
                                                '<span id="bihr-success-' + res.product_id + '" class="dashicons dashicons-yes-alt" style="color: green;"></span> ' +
                                                '<strong>Produit ID ' + res.product_id + '</strong> - Importé (WC ID: ' + res.wc_id + ')'
                                            );
                                            successCount++;
                                        } else {
                                            $(lineId).html(
                                                '<span class="dashicons dashicons-dismiss" style="color: red;"></span> ' +
                                                '<strong>Produit ID ' + res.product_id + '</strong> - Erreur : ' + res.message
                                            );
                                            errorCount++;
                                        }
                                    }
                                } else {
                                    for (var k = 0; k < batch.length; k++) {
                                        var p = batch[k];
                                        $('#bihr-import-' + p.id).html(
                                            '<span class="dashicons dashicons-dismiss" style="color: red;"></span> ' +
                                            '<strong>' + p.name + '</strong> - Erreur : réponse invalide du serveur'
                                        );
                                        errorCount++;
                                    }
                                }
                            },
                            error: function() {
                                for (var k = 0; k < batch.length; k++) {
                                    var p = batch[k];
                                    $('#bihr-import-' + p.id).html(
                                        '<span class="dashicons dashicons-dismiss" style="color: red;"></span> ' +
                                        '<strong>' + p.name + '</strong> - Erreur de connexion'
                                    );
                                    errorCount++;
                                }
                            },
                            complete: function() {
                                currentIndex += batch.length;
                                var percent = Math.round((currentIndex / allProducts.length) * 100);
                                $progressBar.css('width', percent + '%').text(percent + '%');
                                $progressText.text(currentIndex + ' / ' + allProducts.length + ' produits importés');
                                importNextFilteredProductBatch();
                            }
                        });
                    }

                    // Démarrer l'import par batch
                    importNextFilteredProductBatch();
                    
                } else {
                    alert('Aucun produit trouvé correspondant aux filtres actuels.');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span> Importer tous les produits filtrés');
                }
            },
            error: function() {
                alert('Erreur lors de la récupération des produits filtrés.');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span> Importer tous les produits filtrés');
            }
        });
    });
    
    // ============================================
    // TÉLÉCHARGEMENT DES IMAGES EN ATTENTE
    // ============================================

    function downloadPendingImagesLoop($btn, $status) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'bihrwi_download_pending_images', nonce: bihrProgressData.nonce },
            success: function(response) {
                if (response.success) {
                    var remaining = response.data.remaining;
                    $status.text('Images restantes : ' + remaining);
                    if (remaining > 0) {
                        setTimeout(function() { downloadPendingImagesLoop($btn, $status); }, 1000);
                    } else {
                        $status.html('<strong style="color:green;">✓ Toutes les images ont été téléchargées !</strong>');
                        $btn.prop('disabled', false).text('Télécharger les images manquantes');
                        $('#bihr-pending-images-banner').slideUp();
                    }
                }
            },
            error: function() {
                $status.html('<span style="color:red;">Erreur de connexion. Réessayez.</span>');
                $btn.prop('disabled', false).text('Télécharger les images manquantes');
            }
        });
    }

    $(document).on('click', '#bihr-download-pending-images', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Téléchargement en cours...');
        var $status = $('<span style="margin-left:10px;"></span>');
        $btn.after($status);
        downloadPendingImagesLoop($btn, $status);
    });

    // ============================================
    // TÉLÉCHARGEMENT DES IMAGES AVEC BARRE DE PROGRESSION (auto après import)
    // ============================================

    function chainImageDownloadAfterImport($imageContainer, $imageBar, $imageText, $imageDetails, afterComplete) {
        stopImport = false;
        $imageContainer.show();
        $('#bihr-stop-images').show();
        $imageBar.css('width', '0%').text('0%');
        $imageText.text('Préparation du téléchargement des images...');
        $imageDetails.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'bihrwi_count_pending_images', nonce: bihrProgressData.nonce },
            success: function(response) {
                if (response.success && response.data.count > 0) {
                    var initialCount = response.data.count;
                    var downloaded = 0;
                    $imageText.text('0 / ' + initialCount + ' images téléchargées');

                    function imageDownloadLoop() {
                        if (stopImport) {
                            $imageBar.css('width', '0%').text('0%');
                            $imageText.html('<strong style="color: #d63638;">⏹ Téléchargement arrêté par l\'utilisateur</strong>');
                            $imageDetails.append('<div style="color:#d63638; font-weight:bold;">Arrêté après ' + downloaded + ' / ' + initialCount + ' images.</div>');
                            $('#bihr-stop-images').hide();
                            if (afterComplete) afterComplete();
                            return;
                        }

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: { action: 'bihrwi_download_pending_images', nonce: bihrProgressData.nonce },
                            success: function(resp) {
                                if (stopImport) return;
                                if (resp.success) {
                                    var remaining = resp.data.remaining;
                                    downloaded = initialCount - remaining;
                                    var pct = Math.round((downloaded / initialCount) * 100);
                                    $imageBar.css('width', pct + '%').text(pct + '%');
                                    $imageText.text(downloaded + ' / ' + initialCount + ' images téléchargées');

                                    if (remaining > 0) {
                                        setTimeout(imageDownloadLoop, 500);
                                    } else {
                                        $imageBar.css('width', '100%').text('100%');
                                        $imageText.html('<strong style="color: green;">✓ Toutes les images ont été téléchargées</strong>');
                                        $('#bihr-stop-images').hide();
                                        if (afterComplete) afterComplete();
                                    }
                                } else {
                                    $imageDetails.append('<div style="color:red;">Erreur lors du téléchargement.</div>');
                                    if (afterComplete) afterComplete();
                                }
                            },
                            error: function() {
                                $imageDetails.append('<div style="color:red;">Erreur de connexion, nouvelle tentative...</div>');
                                setTimeout(imageDownloadLoop, 2000);
                            }
                        });
                    }

                    imageDownloadLoop();
                } else {
                    if (response.success && response.data.count === 0) {
                        $imageText.html('<strong>Aucune image en attente</strong>');
                        $('#bihr-stop-images').hide();
                    } else {
                        $imageText.html('<span style="color:red;">Erreur lors du comptage des images</span>');
                    }
                    if (afterComplete) afterComplete();
                }
            },
            error: function() {
                $imageText.html('<span style="color:red;">Erreur lors du comptage des images</span>');
                if (afterComplete) afterComplete();
            }
        });
    }

    // ============================================
    // TEST DE LA CLÉ OPENAI
    // ============================================
    
    $('#bihr-test-openai-key').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $input = $('#bihrwi_openai_key');
        var $result = $('#bihr-openai-test-result');
        var apiKey = $input.val();
        
        if (!apiKey || apiKey.trim() === '') {
            $result.html('<span style="color: #d63638;">⚠️ Veuillez saisir une clé API</span>');
            return;
        }
        
        $btn.prop('disabled', true).text('⏳ Test en cours...');
        $result.html('<span style="color: #666;">⏳ Test de la clé en cours...</span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bihr_test_openai_key',
                nonce: bihrProgressData.nonce,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span style="color: #00a32a; font-weight: 600;">' + response.data.message + '</span>');
                } else {
                    $result.html('<span style="color: #d63638; font-weight: 600;">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $result.html('<span style="color: #d63638;">❌ Erreur de connexion au serveur</span>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('🧪 Tester la clé');
            }
        });
    });
    
    // ============================================
    // GESTION DU TÉLÉCHARGEMENT DES CATALOGUES
    // ============================================
    
    // Gestion du téléchargement automatique des catalogues (polling côté client)
    $('#bihr-download-all-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('input[type="submit"]');
        var $progressContainer = $('#bihr-download-progress');
        var $progressBar = $('#bihr-download-progress-bar');
        var $progressText = $('#bihr-download-progress-text');

        $button.prop('disabled', true);
        $progressContainer.show();
        $progressBar.css('width', '5%');
        $progressText.text('Démarrage de la génération des catalogues...');

        // Étape 1 : lancer la génération (retourne immédiatement un session_id)
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bihrwi_start_catalog_download',
                nonce: bihrProgressData.nonce
            },
            success: function(response) {
                if (!response.success) {
                    $progressBar.css('width', '100%').addClass('error');
                    $progressText.text('✗ Erreur : ' + response.data.message);
                    $button.prop('disabled', false);
                    return;
                }
                var sessionId = response.data.session_id;
                var total     = response.data.total;
                $progressBar.css('width', '10%');
                $progressText.text('Génération en cours pour ' + total + ' catalogue(s)...');
                bihrPollCatalogStatus(sessionId, total, $progressBar, $progressText, $button);
            },
            error: function() {
                $progressBar.css('width', '100%').addClass('error');
                $progressText.text('✗ Erreur de connexion');
                $button.prop('disabled', false);
            }
        });
    });

    // Étape 2 : polling du statut toutes les 5 secondes
    function bihrPollCatalogStatus(sessionId, total, $progressBar, $progressText, $button) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bihrwi_check_catalog_download_status',
                session_id: sessionId,
                nonce: bihrProgressData.nonce
            },
            success: function(response) {
                if (!response.success) {
                    $progressBar.css('width', '100%').addClass('error');
                    $progressText.text('✗ Erreur : ' + response.data.message);
                    $button.prop('disabled', false);
                    return;
                }
                var data = response.data;
                if (data.status === 'complete') {
                    $progressBar.css('width', '100%').addClass('complete');
                    var msg = '✓ Terminé ! ' + data.catalogs_count + ' catalogue(s), ' + data.files_count + ' fichier(s) extraits.';
                    $progressText.text(msg);
                    setTimeout(function() { window.location.reload(); }, 2000);
                } else {
                    var pct = 10 + Math.round((data.done_count / data.total) * 70);
                    $progressBar.css('width', pct + '%');
                    $progressText.text('Génération : ' + data.done_count + '/' + data.total + ' catalogue(s) prêt(s)...');
                    setTimeout(function() {
                        bihrPollCatalogStatus(sessionId, total, $progressBar, $progressText, $button);
                    }, 5000);
                }
            },
            error: function() {
                // Réessai silencieux sur erreur réseau
                setTimeout(function() {
                    bihrPollCatalogStatus(sessionId, total, $progressBar, $progressText, $button);
                }, 5000);
            }
        });
    }
    
    // Gestion de la fusion des catalogues
    $('#bihr-merge-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('input[type="submit"]');
        var $progressContainer = $('#bihr-merge-progress');
        var $progressBar = $('#bihr-merge-progress-bar');
        var $progressText = $('#bihr-merge-progress-text');
        
        // Désactive le bouton
        $button.prop('disabled', true);
        
        // Affiche la barre de progression
        $progressContainer.show();
        $progressBar.css('width', '0%');
        $progressText.text('Démarrage de la fusion...');
        
        // Démarre la fusion
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bihrwi_merge_catalogs_ajax',
                nonce: bihrProgressData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $progressBar.css('width', '100%');
                    $progressText.text('✓ Fusion terminée ! ' + response.data.count + ' produits fusionnés.');
                    $progressBar.addClass('complete');
                    
                    // Recharge la page après 2 secondes
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    $progressBar.css('width', '100%');
                    $progressBar.addClass('error');
                    $progressText.text('✗ Erreur : ' + response.data.message);
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                $progressBar.css('width', '100%');
                $progressBar.addClass('error');
                $progressText.text('✗ Erreur de connexion');
                $button.prop('disabled', false);
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                
                // Simulation de progression
                var progressInterval = setInterval(function() {
                    var currentWidth = parseInt($progressBar.css('width'));
                    var containerWidth = $progressBar.parent().width();
                    var currentPercent = (currentWidth / containerWidth) * 100;
                    
                    if (currentPercent < 90) {
                        var newPercent = currentPercent + (Math.random() * 10);
                        $progressBar.css('width', newPercent + '%');
                        
                        // Messages de progression
                        if (newPercent < 30) {
                            $progressText.text('Lecture des fichiers CSV...');
                        } else if (newPercent < 60) {
                            $progressText.text('Fusion des catalogues...');
                        } else if (newPercent < 90) {
                            $progressText.text('Enregistrement dans la base de données...');
                        }
                    }
                }, 500);
                
                xhr.addEventListener('loadend', function() {
                    clearInterval(progressInterval);
                });
                
                return xhr;
            }
        });
    });
    
    // ============================================
    // RAFRAÎCHISSEMENT DU STOCK EN TEMPS RÉEL
    // ============================================
    
    $(document).on('click', '.refresh-stock', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $cell = $button.closest('.stock-cell');
        var $stockValue = $cell.find('.stock-value');
        var productCode = $cell.data('product-code');
        var productId = $cell.data('product-id') || 0;
        
        if (!productCode) {
            alert('Code produit introuvable.');
            return;
        }
        
        // Désactiver le bouton et afficher un spinner
        $button.prop('disabled', true);
        $button.find('.dashicons').addClass('spin');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'bihr_refresh_stock',
                product_code: productCode,
                product_id: productId,
                nonce: bihrProgressData.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stockLevel = response.data.stock_level;
                    var stockHtml = '<strong style="color: green;">' + stockLevel + '</strong>';
                    
                    if (stockLevel === 0) {
                        stockHtml = '<strong style="color: red;">0</strong>';
                    } else if (stockLevel < 5) {
                        stockHtml = '<strong style="color: orange;">' + stockLevel + '</strong>';
                    }
                    
                    $stockValue.html(stockHtml);
                    
                    // Animation de succès
                    $cell.css('background-color', '#d4edda');
                    setTimeout(function() {
                        $cell.css('background-color', '');
                    }, 2000);
                    
                    // Message si le stock WooCommerce a été mis à jour
                    if (response.data.updated && productId > 0) {
                        var $statusLabel = $cell.find('small');
                        if ($statusLabel.length) {
                            if (stockLevel > 0) {
                                $statusLabel.removeClass('stock-status-outofstock stock-status-onbackorder')
                                           .addClass('stock-status-instock')
                                           .text('En stock');
                            } else {
                                $statusLabel.removeClass('stock-status-instock stock-status-onbackorder')
                                           .addClass('stock-status-outofstock')
                                           .text('Rupture');
                            }
                        }
                    }
                } else {
                    alert('Erreur lors de la récupération du stock : ' + response.data.message);
                }
            },
            error: function() {
                alert('Erreur de connexion lors de la récupération du stock.');
            },
            complete: function() {
                // Réactiver le bouton
                $button.prop('disabled', false);
                $button.find('.dashicons').removeClass('spin');
            }
        });
    });
    
});
