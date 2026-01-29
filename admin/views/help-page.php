<?php
/**
 * Page d'aide et tutoriel - Filtre véhicule
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap bihr-help-page">
    <h1>📚 Aide & Tutoriels - BIHR WooCommerce</h1>

    <div class="bihr-help-content">
        <!-- Section Filtre Véhicule -->
        <div class="bihr-help-section">
            <h2>🚗 Filtre de Compatibilité Véhicule</h2>
            
            <div class="bihr-help-box">
                <h3>🎯 À quoi sert ce filtre ?</h3>
                <p>Le filtre de compatibilité véhicule permet à vos clients de trouver facilement les pièces compatibles avec leur moto ou véhicule. Il affiche un formulaire avec des sélecteurs en cascade : <strong>Fabricant → Modèle → Année</strong>.</p>
            </div>

            <div class="bihr-help-box">
                <h3>📝 Shortcode de base</h3>
                <p>Pour afficher le filtre sur une page, utilisez le shortcode suivant :</p>
                <div class="bihr-code-block">
                    <code>[bihr_vehicle_filter]</code>
                    <button class="button button-small bihr-copy-btn" data-copy="[bihr_vehicle_filter]">📋 Copier</button>
                </div>
            </div>

            <div class="bihr-help-box">
                <h3>⚙️ Options disponibles</h3>
                <p>Le shortcode accepte deux paramètres optionnels :</p>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Paramètre</th>
                            <th>Description</th>
                            <th>Valeurs possibles</th>
                            <th>Défaut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>title</code></td>
                            <td>Titre affiché au-dessus du filtre</td>
                            <td>Texte personnalisé</td>
                            <td>"Trouvez vos pièces"</td>
                        </tr>
                        <tr>
                            <td><code>show_button</code></td>
                            <td>Afficher le bouton de recherche</td>
                            <td><code>yes</code> ou <code>no</code></td>
                            <td><code>yes</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bihr-help-box">
                <h3>💡 Exemples d'utilisation</h3>
                
                <div class="bihr-example">
                    <h4>Exemple 1 : Shortcode simple</h4>
                    <div class="bihr-code-block">
                        <code>[bihr_vehicle_filter]</code>
                        <button class="button button-small bihr-copy-btn" data-copy="[bihr_vehicle_filter]">📋 Copier</button>
                    </div>
                    <p class="bihr-note">Affiche le filtre avec le titre par défaut "Trouvez vos pièces" et le bouton de recherche.</p>
                </div>

                <div class="bihr-example">
                    <h4>Exemple 2 : Avec titre personnalisé</h4>
                    <div class="bihr-code-block">
                        <code>[bihr_vehicle_filter title="Quelle pièce pour votre moto ?"]</code>
                        <button class="button button-small bihr-copy-btn" data-copy='[bihr_vehicle_filter title="Quelle pièce pour votre moto ?"]'>📋 Copier</button>
                    </div>
                    <p class="bihr-note">Affiche le filtre avec un titre personnalisé.</p>
                </div>

                <div class="bihr-example">
                    <h4>Exemple 3 : Sans bouton (pour intégration personnalisée)</h4>
                    <div class="bihr-code-block">
                        <code>[bihr_vehicle_filter show_button="no"]</code>
                        <button class="button button-small bihr-copy-btn" data-copy='[bihr_vehicle_filter show_button="no"]'>📋 Copier</button>
                    </div>
                    <p class="bihr-note">Affiche uniquement les sélecteurs sans le bouton de recherche. Utile si vous voulez créer votre propre bouton ou déclencher la recherche via JavaScript.</p>
                </div>

                <div class="bihr-example">
                    <h4>Exemple 4 : Configuration complète</h4>
                    <div class="bihr-code-block">
                        <code>[bihr_vehicle_filter title="Recherche par véhicule" show_button="yes"]</code>
                        <button class="button button-small bihr-copy-btn" data-copy='[bihr_vehicle_filter title="Recherche par véhicule" show_button="yes"]'>📋 Copier</button>
                    </div>
                    <p class="bihr-note">Configuration complète avec titre personnalisé et bouton activé.</p>
                </div>
            </div>

            <div class="bihr-help-box">
                <h3>📖 Comment l'utiliser dans WordPress</h3>
                
                <h4>Méthode 1 : Dans l'éditeur de blocs (Gutenberg)</h4>
                <ol>
                    <li>Créez ou éditez une page/article</li>
                    <li>Cliquez sur <strong>+</strong> pour ajouter un bloc</li>
                    <li>Recherchez et sélectionnez le bloc <strong>"Shortcode"</strong></li>
                    <li>Collez le shortcode : <code>[bihr_vehicle_filter]</code></li>
                    <li>Publiez ou mettez à jour la page</li>
                </ol>

                <h4>Méthode 2 : Dans l'éditeur classique</h4>
                <ol>
                    <li>Créez ou éditez une page/article</li>
                    <li>Collez directement le shortcode dans le contenu : <code>[bihr_vehicle_filter]</code></li>
                    <li>Publiez ou mettez à jour la page</li>
                </ol>

                <h4>Méthode 3 : Dans un template PHP</h4>
                <p>Si vous éditez un template de thème (fichier PHP), utilisez :</p>
                <div class="bihr-code-block">
                    <code>&lt;?php echo do_shortcode('[bihr_vehicle_filter]'); ?&gt;</code>
                    <button class="button button-small bihr-copy-btn" data-copy="<?php echo esc_attr( '<?php echo do_shortcode(\'[bihr_vehicle_filter]\'); ?>' ); ?>">📋 Copier</button>
                </div>
            </div>

            <div class="bihr-help-box">
                <h3>🎨 Emplacements recommandés</h3>
                <ul>
                    <li><strong>Page boutique (Shop)</strong> : Permet de filtrer tous les produits</li>
                    <li><strong>Page dédiée "Trouvez vos pièces"</strong> : Créez une page spéciale avec le filtre</li>
                    <li><strong>Sidebar</strong> : Utilisez le widget "🏍️ BIHR - Filtre Véhicule" dans Apparence → Widgets</li>
                    <li><strong>Page d'accueil</strong> : Pour améliorer la découverte des produits</li>
                </ul>
            </div>

            <div class="bihr-help-box">
                <h3>🔧 Utilisation du Widget (alternative)</h3>
                <p>Au lieu d'utiliser le shortcode, vous pouvez également utiliser le widget WordPress :</p>
                <ol>
                    <li>Allez dans <strong>Apparence → Widgets</strong></li>
                    <li>Cherchez le widget <strong>"🏍️ BIHR - Filtre Véhicule"</strong></li>
                    <li>Glissez-déposez-le dans la sidebar de votre choix</li>
                    <li>Configurez un titre optionnel</li>
                    <li>Sauvegardez</li>
                </ol>
            </div>

            <div class="bihr-help-box bihr-info-box">
                <h3>ℹ️ Fonctionnement du filtre</h3>
                <ol>
                    <li><strong>Sélection du fabricant</strong> : Le client choisit une marque (ex: Honda, Yamaha, Kawasaki)</li>
                    <li><strong>Sélection du modèle</strong> : Les modèles disponibles pour cette marque s'affichent automatiquement</li>
                    <li><strong>Sélection de l'année/version</strong> : Les années disponibles pour ce modèle s'affichent</li>
                    <li><strong>Recherche</strong> : Clique sur "Voir les pièces compatibles"</li>
                    <li><strong>Résultats</strong> : Les produits compatibles avec le véhicule sélectionné sont filtrés et affichés</li>
                </ol>
                <p><strong>Note :</strong> Le véhicule sélectionné est sauvegardé en session, il reste donc mémorisé lors de la navigation.</p>
            </div>

            <div class="bihr-help-box bihr-warning-box">
                <h3>⚠️ Prérequis</h3>
                <p>Pour que le filtre fonctionne correctement, assurez-vous que :</p>
                <ul>
                    <li>✅ Les véhicules ont été importés (page <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihr-compatibility' ) ); ?>">🚗 Compatibilité</a>)</li>
                    <li>✅ Les fichiers de compatibilité ont été importés pour les marques concernées</li>
                    <li>✅ Les produits WooCommerce ont des SKU correspondant aux numéros de pièces dans les fichiers de compatibilité</li>
                </ul>
            </div>
        </div>

        <!-- Section Filtre Produit -->
        <div class="bihr-help-section">
            <h2>📦 Filtre de Produits par Catégories</h2>
            
            <div class="bihr-help-box">
                <h3>🎯 À quoi sert ce filtre ?</h3>
                <p>Le filtre de produits par catégories permet à vos clients de naviguer dans votre catalogue BIHR en utilisant la hiérarchie de catégories à 3 niveaux. Il affiche un formulaire avec des sélecteurs en cascade : <strong>Catégorie Niveau 1 → Niveau 2 → Niveau 3</strong>.</p>
                <p><strong>Différence avec le filtre véhicule :</strong> Ce filtre utilise les catégories produits (ex: "Moteur → Admission → Gicleur") alors que le filtre véhicule utilise la compatibilité avec les véhicules (Fabricant → Modèle → Année).</p>
            </div>

            <div class="bihr-help-box">
                <h3>📝 Shortcode de base</h3>
                <p>Pour afficher le filtre sur une page, utilisez le shortcode suivant :</p>
                <div class="bihr-code-block">
                    <code>[bihr_product_filter]</code>
                    <button class="button button-small bihr-copy-btn" data-copy="[bihr_product_filter]">📋 Copier</button>
                </div>
            </div>

            <div class="bihr-help-box">
                <h3>⚙️ Options disponibles</h3>
                <p>Le shortcode accepte deux paramètres optionnels :</p>
                
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Paramètre</th>
                            <th>Description</th>
                            <th>Valeurs possibles</th>
                            <th>Défaut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>title</code></td>
                            <td>Titre affiché au-dessus du filtre</td>
                            <td>Texte personnalisé</td>
                            <td>"Filtrez nos produits"</td>
                        </tr>
                        <tr>
                            <td><code>show_button</code></td>
                            <td>Afficher le bouton de recherche</td>
                            <td><code>yes</code> ou <code>no</code></td>
                            <td><code>yes</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bihr-help-box">
                <h3>💡 Exemples d'utilisation</h3>
                
                <div class="bihr-example">
                    <h4>Exemple 1 : Shortcode simple</h4>
                    <div class="bihr-code-block">
                        <code>[bihr_product_filter]</code>
                        <button class="button button-small bihr-copy-btn" data-copy="[bihr_product_filter]">📋 Copier</button>
                    </div>
                    <p class="bihr-note">Affiche le filtre avec le titre par défaut "Filtrez nos produits" et le bouton de recherche.</p>
                </div>

                <div class="bihr-example">
                    <h4>Exemple 2 : Avec titre personnalisé</h4>
                    <div class="bihr-code-block">
                        <code>[bihr_product_filter title="Naviguez par catégorie"]</code>
                        <button class="button button-small bihr-copy-btn" data-copy='[bihr_product_filter title="Naviguez par catégorie"]'>📋 Copier</button>
                    </div>
                    <p class="bihr-note">Affiche le filtre avec un titre personnalisé.</p>
                </div>

                <div class="bihr-example">
                    <h4>Exemple 3 : Sans bouton (pour intégration personnalisée)</h4>
                    <div class="bihr-code-block">
                        <code>[bihr_product_filter show_button="no"]</code>
                        <button class="button button-small bihr-copy-btn" data-copy='[bihr_product_filter show_button="no"]'>📋 Copier</button>
                    </div>
                    <p class="bihr-note">Affiche uniquement les sélecteurs sans le bouton de recherche. Utile si vous voulez créer votre propre bouton ou déclencher la recherche via JavaScript.</p>
                </div>

                <div class="bihr-example">
                    <h4>Exemple 4 : Configuration complète</h4>
                    <div class="bihr-code-block">
                        <code>[bihr_product_filter title="Trouvez votre pièce par catégorie" show_button="yes"]</code>
                        <button class="button button-small bihr-copy-btn" data-copy='[bihr_product_filter title="Trouvez votre pièce par catégorie" show_button="yes"]'>📋 Copier</button>
                    </div>
                    <p class="bihr-note">Configuration complète avec titre personnalisé et bouton activé.</p>
                </div>
            </div>

            <div class="bihr-help-box">
                <h3>📖 Comment l'utiliser dans WordPress</h3>
                
                <h4>Méthode 1 : Dans l'éditeur de blocs (Gutenberg)</h4>
                <ol>
                    <li>Créez ou éditez une page/article</li>
                    <li>Cliquez sur <strong>+</strong> pour ajouter un bloc</li>
                    <li>Recherchez et sélectionnez le bloc <strong>"Shortcode"</strong></li>
                    <li>Collez le shortcode : <code>[bihr_product_filter]</code></li>
                    <li>Publiez ou mettez à jour la page</li>
                </ol>

                <h4>Méthode 2 : Dans l'éditeur classique</h4>
                <ol>
                    <li>Créez ou éditez une page/article</li>
                    <li>Collez directement le shortcode dans le contenu : <code>[bihr_product_filter]</code></li>
                    <li>Publiez ou mettez à jour la page</li>
                </ol>

                <h4>Méthode 3 : Dans un template PHP</h4>
                <p>Si vous éditez un template de thème (fichier PHP), utilisez :</p>
                <div class="bihr-code-block">
                    <code>&lt;?php echo do_shortcode('[bihr_product_filter]'); ?&gt;</code>
                    <button class="button button-small bihr-copy-btn" data-copy="<?php echo esc_attr( '<?php echo do_shortcode(\'[bihr_product_filter]\'); ?>' ); ?>">📋 Copier</button>
                </div>
            </div>

            <div class="bihr-help-box">
                <h3>🎨 Emplacements recommandés</h3>
                <ul>
                    <li><strong>Page boutique (Shop)</strong> : Permet de filtrer tous les produits par catégorie</li>
                    <li><strong>Page dédiée "Catalogue BIHR"</strong> : Créez une page spéciale avec le filtre</li>
                    <li><strong>Page d'accueil</strong> : Pour améliorer la découverte des produits</li>
                    <li><strong>Page catégorie</strong> : Pour affiner la recherche dans une catégorie spécifique</li>
                </ul>
            </div>

            <div class="bihr-help-box bihr-info-box">
                <h3>ℹ️ Fonctionnement du filtre</h3>
                <ol>
                    <li><strong>Sélection du niveau 1</strong> : Le client choisit une catégorie principale (ex: "Moteur", "Equipement du pilote", "Outillage")</li>
                    <li><strong>Sélection du niveau 2</strong> : Les sous-catégories disponibles pour ce niveau 1 s'affichent automatiquement (ex: "Admission", "Echappement")</li>
                    <li><strong>Sélection du niveau 3</strong> : Les sous-sous-catégories disponibles pour ce couple (niveau 1 + niveau 2) s'affichent (ex: "Gicleur", "Filtre à air")</li>
                    <li><strong>Recherche</strong> : Clique sur "Voir les produits"</li>
                    <li><strong>Résultats</strong> : Les produits WooCommerce correspondant aux catégories sélectionnées sont affichés en grille avec images, prix et liens</li>
                </ol>
                <p><strong>Note :</strong> Les catégories sont chargées dynamiquement via AJAX. Si aucun niveau 1 n'est sélectionné, les niveaux 2 et 3 sont désactivés.</p>
            </div>

            <div class="bihr-help-box bihr-warning-box">
                <h3>⚠️ Prérequis</h3>
                <p>Pour que le filtre fonctionne correctement, assurez-vous que :</p>
                <ul>
                    <li>✅ Les catalogues BIHR ont été fusionnés (page <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihr-products' ) ); ?>">📦 Produits Bihr</a>)</li>
                    <li>✅ Les catégories ont été recalculées depuis le fichier <code>cat-ref-full-*.csv</code> (bouton "Recalculer catégories")</li>
                    <li>✅ Les produits ont été importés dans WooCommerce avec leurs codes BIHR</li>
                    <li>✅ Les produits WooCommerce ont des meta <code>_bihr_product_code</code> correspondant aux codes dans <code>wp_bihr_products</code></li>
                </ul>
            </div>
        </div>

        <!-- Section Support -->
        <div class="bihr-help-section">
            <h2>🆘 Besoin d'aide supplémentaire ?</h2>
            <div class="bihr-help-box">
                <p>Si vous rencontrez des problèmes ou avez des questions :</p>
                <ul>
                    <li>📖 Consultez la <a href="https://github.com/DRCOMPUTER60290/BIHR-SYNCH" target="_blank">documentation complète sur GitHub</a></li>
                    <li>📧 Contactez le support via le menu Freemius (si vous avez une licence premium)</li>
                    <li>🐛 Vérifiez les <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihr-logs' ) ); ?>">logs du plugin</a> en cas d'erreur</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.bihr-help-page {
    max-width: 1200px;
}

.bihr-help-content {
    margin-top: 20px;
}

.bihr-help-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.bihr-help-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #2271b1;
}

.bihr-help-box {
    background: #f9f9f9;
    border-left: 4px solid #2271b1;
    padding: 15px;
    margin: 15px 0;
}

.bihr-help-box h3 {
    margin-top: 0;
    color: #2271b1;
}

.bihr-info-box {
    border-left-color: #00a32a;
}

.bihr-info-box h3 {
    color: #00a32a;
}

.bihr-warning-box {
    border-left-color: #d63638;
}

.bihr-warning-box h3 {
    color: #d63638;
}

.bihr-code-block {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 12px;
    border-radius: 4px;
    margin: 10px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'Courier New', monospace;
}

.bihr-code-block code {
    background: transparent;
    color: #d4d4d4;
    padding: 0;
    font-size: 14px;
    flex: 1;
}

.bihr-copy-btn {
    margin-left: 10px;
    cursor: pointer;
}

.bihr-example {
    background: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
}

.bihr-example h4 {
    margin-top: 0;
    color: #2271b1;
}

.bihr-note {
    font-style: italic;
    color: #666;
    margin-top: 10px;
}

table.widefat {
    margin-top: 10px;
}

table.widefat th {
    background: #f0f0f1;
    font-weight: 600;
}

table.widefat code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
}

ol, ul {
    line-height: 1.8;
}
</style>

<script>
(function($) {
    // Fonctionnalité de copie
    $('.bihr-copy-btn').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const code = button.closest('.bihr-code-block').find('code').text();
        
        // Copier dans le presse-papiers
        const temp = $('<textarea>').val(code).appendTo('body').select();
        document.execCommand('copy');
        temp.remove();
        
        // Feedback visuel
        const originalText = button.html();
        button.html('✓ Copié!').css('background', '#00a32a').css('color', '#fff');
        
        setTimeout(function() {
            button.html(originalText).css('background', '').css('color', '');
        }, 2000);
    });
})(jQuery);
</script>
