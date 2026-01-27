(function ($) {
    'use strict';

    /**
     * Remplit un <select> avec une liste de termes.
     *
     * @param {jQuery} $select
     * @param {Array} terms
     * @param {number} selectedId
     */
    function populateSelect($select, terms, selectedId) {
        $select.empty();

        // Option vide = "Toutes"
        $select.append(
            $('<option>', {
                value: '',
                text: 'Toutes'
            })
        );

        if (!terms || !terms.length) {
            $select.prop('disabled', true);
            return;
        }

        $.each(terms, function (_, term) {
            $select.append(
                $('<option>', {
                    value: term.id,
                    text: term.name,
                    selected: selectedId && parseInt(selectedId, 10) === parseInt(term.id, 10)
                })
            );
        });

        $select.prop('disabled', false);
    }

    /**
     * Charge les enfants d'une catégorie via AJAX.
     *
     * @param {number} parentId
     * @param {jQuery} $target
     * @param {number} selectedId
     * @returns {jQuery.Promise}
     */
    function loadChildCategories(parentId, $target, selectedId) {
        var dfd = $.Deferred();

        if (!parentId) {
            populateSelect($target, [], 0);
            dfd.resolve();
            return dfd.promise();
        }

        $.ajax({
            url: BihrCategoryFilters.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'bihr_get_child_categories',
                nonce: BihrCategoryFilters.nonce,
                parent_id: parentId
            }
        })
            .done(function (response) {
                if (response && response.success && response.data && response.data.terms) {
                    populateSelect($target, response.data.terms, selectedId);
                } else {
                    populateSelect($target, [], 0);
                }
                dfd.resolve();
            })
            .fail(function () {
                populateSelect($target, [], 0);
                dfd.resolve();
            });

        return dfd.promise();
    }

    $(function () {
        if (typeof BihrCategoryFilters === 'undefined') {
            return;
        }

        var $cat = $('#bihr_cat');
        var $subcat = $('#bihr_subcat');
        var $subcat2 = $('#bihr_subcat2');

        if (!$cat.length || !$subcat.length || !$subcat2.length) {
            return;
        }

        var selected = BihrCategoryFilters.selected || {};
        var rootCategories = BihrCategoryFilters.rootCategories || [];

        // Initialisation du select principal avec les catégories racine.
        populateSelect($cat, rootCategories, selected.cat || 0);

        // État initial des enfants.
        $subcat.prop('disabled', true);
        $subcat2.prop('disabled', true);

        // Si une catégorie est déjà sélectionnée, charger ses enfants (et éventuellement les petits-enfants).
        if (selected.cat) {
            loadChildCategories(selected.cat, $subcat, selected.subcat || 0).then(function () {
                if (selected.subcat) {
                    return loadChildCategories(selected.subcat, $subcat2, selected.subcat2 || 0);
                }
                return null;
            });
        }

        // Changement de catégorie principale.
        $cat.on('change', function () {
            var catId = parseInt($(this).val(), 10) || 0;

            // Réinitialiser les niveaux inférieurs.
            populateSelect($subcat, [], 0);
            populateSelect($subcat2, [], 0);

            if (!catId) {
                return;
            }

            loadChildCategories(catId, $subcat, 0);
        });

        // Changement de sous-catégorie.
        $subcat.on('change', function () {
            var subId = parseInt($(this).val(), 10) || 0;

            // Réinitialiser la sous-sous-catégorie.
            populateSelect($subcat2, [], 0);

            if (!subId) {
                return;
            }

            loadChildCategories(subId, $subcat2, 0);
        });
    });
})(jQuery);

