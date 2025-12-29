<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Récupérer les marges enregistrées
$margin_settings = get_option( 'bihrwi_margin_settings', array(
    'default_margin_type' => 'percentage',
    'default_margin_value' => 0,
    'category_margins' => array(),
    'price_range_margins' => array(),
    'priority' => 'specific' // specific ou global
) );

$categories = array(
    'RIDER GEAR',
    'VEHICLE PARTS & ACCESSORIES',
    'LIQUIDS & LUBRICANTS',
    'TIRES & ACCESSORIES',
    'TOOLING & WS',
    'OTHER PRODUCTS & SERVICES'
);
?>

<div class="wrap">
    <h1>🏷️ Gestion des marges</h1>
    
    <p class="description">
        Configurez les marges à appliquer automatiquement sur les prix des produits BIHR lors de l'import dans WooCommerce.
        Les marges sont appliquées sur le prix HT fournisseur pour obtenir le prix de vente HT.
    </p>

    <?php if ( isset( $_GET['margin_saved'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>✅ Configuration des marges enregistrée avec succès !</p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field( 'bihrwi_save_margins', 'bihrwi_margins_nonce' ); ?>

        <!-- MARGE PAR DÉFAUT -->
        <div class="bihr-margin-section" style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>📊 Marge par défaut (globale)</h2>
            <p class="description">Cette marge s'applique à tous les produits qui n'ont pas de règle spécifique.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="default_margin_type">Type de marge</label>
                    </th>
                    <td>
                        <select name="default_margin_type" id="default_margin_type" style="width: 200px;">
                            <option value="percentage" <?php selected( $margin_settings['default_margin_type'], 'percentage' ); ?>>Pourcentage (%)</option>
                            <option value="fixed" <?php selected( $margin_settings['default_margin_type'], 'fixed' ); ?>>Montant fixe (€)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="default_margin_value">Valeur de la marge</label>
                    </th>
                    <td>
                        <input type="number" 
                               name="default_margin_value" 
                               id="default_margin_value" 
                               value="<?php echo esc_attr( $margin_settings['default_margin_value'] ); ?>" 
                               step="0.01" 
                               min="0"
                               style="width: 200px;" />
                        <span class="default-margin-suffix">%</span>
                        <p class="description">
                            Exemple : <strong>30%</strong> → Prix fournisseur 100€ = Prix vente 130€<br>
                            Exemple : <strong>10€</strong> → Prix fournisseur 100€ = Prix vente 110€
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- MARGES PAR CATÉGORIE -->
        <div class="bihr-margin-section" style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>🏷️ Marges par catégorie</h2>
            <p class="description">Définissez des marges différentes pour chaque catégorie de produits.</p>
            
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 40px;">Actif</th>
                        <th>Catégorie</th>
                        <th style="width: 150px;">Type</th>
                        <th style="width: 150px;">Marge</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $categories as $category ) : 
                        $cat_key = sanitize_key( $category );
                        $cat_margin = isset( $margin_settings['category_margins'][ $cat_key ] ) 
                            ? $margin_settings['category_margins'][ $cat_key ] 
                            : array( 'enabled' => false, 'type' => 'percentage', 'value' => 0 );
                    ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" 
                                       name="category_margins[<?php echo esc_attr( $cat_key ); ?>][enabled]" 
                                       value="1"
                                       <?php checked( $cat_margin['enabled'], true ); ?> />
                            </td>
                            <td><strong><?php echo esc_html( $category ); ?></strong></td>
                            <td>
                                <select name="category_margins[<?php echo esc_attr( $cat_key ); ?>][type]" style="width: 100%;">
                                    <option value="percentage" <?php selected( $cat_margin['type'], 'percentage' ); ?>>Pourcentage (%)</option>
                                    <option value="fixed" <?php selected( $cat_margin['type'], 'fixed' ); ?>>Fixe (€)</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" 
                                       name="category_margins[<?php echo esc_attr( $cat_key ); ?>][value]" 
                                       value="<?php echo esc_attr( $cat_margin['value'] ); ?>" 
                                       step="0.01" 
                                       min="0"
                                       style="width: 100%;" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- MARGES PAR TRANCHE DE PRIX -->
        <div class="bihr-margin-section" style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>💰 Marges par tranche de prix</h2>
            <p class="description">Appliquez des marges différentes selon le prix d'achat du produit (prix HT fournisseur).</p>
            
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 40px;">Actif</th>
                        <th style="width: 150px;">Prix min (€)</th>
                        <th style="width: 150px;">Prix max (€)</th>
                        <th style="width: 150px;">Type</th>
                        <th style="width: 150px;">Marge</th>
                        <th style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody id="price-range-tbody">
                    <?php 
                    if ( ! empty( $margin_settings['price_range_margins'] ) ) :
                        foreach ( $margin_settings['price_range_margins'] as $index => $range ) : ?>
                            <tr class="price-range-row">
                                <td style="text-align: center;">
                                    <input type="checkbox" 
                                           name="price_range_margins[<?php echo esc_attr( $index ); ?>][enabled]" 
                                           value="1"
                                           <?php checked( $range['enabled'], true ); ?> />
                                </td>
                                <td>
                                    <input type="number" 
                                           name="price_range_margins[<?php echo esc_attr( $index ); ?>][min]" 
                                           value="<?php echo esc_attr( $range['min'] ); ?>" 
                                           step="0.01" 
                                           min="0"
                                           placeholder="0.00"
                                           style="width: 100%;" />
                                </td>
                                <td>
                                    <input type="number" 
                                           name="price_range_margins[<?php echo esc_attr( $index ); ?>][max]" 
                                           value="<?php echo esc_attr( $range['max'] ); ?>" 
                                           step="0.01" 
                                           min="0"
                                           placeholder="999999.99"
                                           style="width: 100%;" />
                                </td>
                                <td>
                                    <select name="price_range_margins[<?php echo esc_attr( $index ); ?>][type]" style="width: 100%;">
                                        <option value="percentage" <?php selected( $range['type'], 'percentage' ); ?>>Pourcentage (%)</option>
                                        <option value="fixed" <?php selected( $range['type'], 'fixed' ); ?>>Fixe (€)</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" 
                                           name="price_range_margins[<?php echo esc_attr( $index ); ?>][value]" 
                                           value="<?php echo esc_attr( $range['value'] ); ?>" 
                                           step="0.01" 
                                           min="0"
                                           style="width: 100%;" />
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="button remove-price-range" title="Supprimer">❌</button>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr class="price-range-row">
                            <td style="text-align: center;">
                                <input type="checkbox" name="price_range_margins[0][enabled]" value="1" checked />
                            </td>
                            <td><input type="number" name="price_range_margins[0][min]" value="0" step="0.01" min="0" placeholder="0.00" style="width: 100%;" /></td>
                            <td><input type="number" name="price_range_margins[0][max]" value="50" step="0.01" min="0" placeholder="50.00" style="width: 100%;" /></td>
                            <td>
                                <select name="price_range_margins[0][type]" style="width: 100%;">
                                    <option value="percentage">Pourcentage (%)</option>
                                    <option value="fixed">Fixe (€)</option>
                                </select>
                            </td>
                            <td><input type="number" name="price_range_margins[0][value]" value="50" step="0.01" min="0" style="width: 100%;" /></td>
                            <td style="text-align: center;">
                                <button type="button" class="button remove-price-range" title="Supprimer">❌</button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 15px;">
                <button type="button" id="add-price-range" class="button">➕ Ajouter une tranche de prix</button>
            </p>
        </div>

        <!-- PRIORITÉ DES RÈGLES -->
        <div class="bihr-margin-section" style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2>⚖️ Ordre de priorité</h2>
            <p class="description">Définissez quelle règle s'applique en premier quand plusieurs correspondent au même produit.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Ordre d'application</th>
                    <td>
                        <label>
                            <input type="radio" name="priority" value="specific" <?php checked( $margin_settings['priority'], 'specific' ); ?> />
                            <strong>Règles spécifiques en priorité</strong> (Tranche de prix → Catégorie → Défaut)
                        </label>
                        <br><br>
                        <label>
                            <input type="radio" name="priority" value="global" <?php checked( $margin_settings['priority'], 'global' ); ?> />
                            <strong>Marge par défaut uniquement</strong> (Ignore les règles spécifiques)
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- APERÇU -->
        <div class="bihr-margin-section" style="background: #f0f8ff; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #2271b1;">
            <h2>👁️ Exemples de calcul</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Prix fournisseur HT</th>
                        <th>Catégorie</th>
                        <th>Règle appliquée</th>
                        <th>Calcul</th>
                        <th>Prix vente HT</th>
                    </tr>
                </thead>
                <tbody id="margin-preview">
                    <tr><td colspan="5" style="text-align: center; color: #999;">Enregistrez vos modifications pour voir les exemples</td></tr>
                </tbody>
            </table>
        </div>

        <?php submit_button( '💾 Enregistrer la configuration des marges', 'primary large' ); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Mise à jour du suffixe selon le type de marge par défaut
    $('#default_margin_type').on('change', function() {
        var suffix = $(this).val() === 'percentage' ? '%' : '€';
        $('.default-margin-suffix').text(suffix);
    });
    
    // Ajouter une tranche de prix
    var priceRangeIndex = <?php echo count( $margin_settings['price_range_margins'] ); ?>;
    
    $('#add-price-range').on('click', function() {
        var newRow = `
            <tr class="price-range-row">
                <td style="text-align: center;">
                    <input type="checkbox" name="price_range_margins[${priceRangeIndex}][enabled]" value="1" checked />
                </td>
                <td><input type="number" name="price_range_margins[${priceRangeIndex}][min]" value="0" step="0.01" min="0" placeholder="0.00" style="width: 100%;" /></td>
                <td><input type="number" name="price_range_margins[${priceRangeIndex}][max]" value="100" step="0.01" min="0" placeholder="100.00" style="width: 100%;" /></td>
                <td>
                    <select name="price_range_margins[${priceRangeIndex}][type]" style="width: 100%;">
                        <option value="percentage">Pourcentage (%)</option>
                        <option value="fixed">Fixe (€)</option>
                    </select>
                </td>
                <td><input type="number" name="price_range_margins[${priceRangeIndex}][value]" value="30" step="0.01" min="0" style="width: 100%;" /></td>
                <td style="text-align: center;">
                    <button type="button" class="button remove-price-range" title="Supprimer">❌</button>
                </td>
            </tr>
        `;
        
        $('#price-range-tbody').append(newRow);
        priceRangeIndex++;
    });
    
    // Supprimer une tranche de prix
    $(document).on('click', '.remove-price-range', function() {
        if ($('.price-range-row').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('Vous devez conserver au moins une tranche de prix.');
        }
    });
    
});
</script>

<style>
.bihr-margin-section h2 {
    margin-top: 0;
    border-bottom: 2px solid #2271b1;
    padding-bottom: 10px;
}

.price-range-row:hover {
    background-color: #f9f9f9;
}

input[type="number"] {
    padding: 5px 8px;
}
</style>
