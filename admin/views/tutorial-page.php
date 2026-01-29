<?php
/**
 * Page Tutoriel - Documentation complète de toutes les pages du plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap bihr-tutorial-page">
    <h1>📖 Tutoriel Complet - BIHR WooCommerce Importer</h1>
    
    <div class="bihr-tutorial-intro">
        <p style="font-size: 16px; color: #666; margin-bottom: 30px;">
            Ce guide vous explique en détail le fonctionnement de chaque page et fonctionnalité du plugin BIHR WooCommerce Importer.
        </p>
    </div>

    <div class="bihr-tutorial-content">
        
        <!-- Table des matières -->
        <div class="bihr-tutorial-toc">
            <h2>📑 Table des matières</h2>
            <ul class="bihr-toc-list">
                <li><a href="#dashboard">🏠 Dashboard (Accueil)</a></li>
                <li><a href="#auth">🔐 Authentification</a></li>
                <li><a href="#products">📦 Produits BIHR</a></li>
                <li><a href="#imported">✅ Produits Importés</a></li>
                <li><a href="#compatibility">🚗 Compatibilité</a></li>
                <li><a href="#orders">🛒 Commandes</a></li>
                <li><a href="#margins">💰 Marges</a></li>
                <li><a href="#logs">📊 Logs</a></li>
                <li><a href="#sku-sync">🔄 Synchro SKU</a></li>
                <li><a href="#wpcron">⚙️ WP-Cron</a></li>
                <li><a href="#help">📚 Aide</a></li>
            </ul>
        </div>

        <!-- Dashboard -->
        <div id="dashboard" class="bihr-tutorial-section">
            <h2>🏠 Dashboard (Accueil)</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Le Dashboard est la page d'accueil du plugin. Il affiche un aperçu de l'état de votre installation et des statistiques principales.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📊 Éléments de la page</h3>
                
                <h4>1. Mode Débutant / Expert</h4>
                <p>En haut à droite, un toggle permet de basculer entre :</p>
                <ul>
                    <li><strong>Mode Débutant</strong> : Interface simplifiée avec explications détaillées</li>
                    <li><strong>Mode Expert</strong> : Interface complète avec toutes les options avancées</li>
                </ul>

                <h4>2. Bannière de connexion</h4>
                <ul>
                    <li><strong>Si non connecté</strong> : Affiche un message de bienvenue avec un bouton pour se connecter</li>
                    <li><strong>Si connecté</strong> : Affiche un message de confirmation de connexion</li>
                </ul>

                <h4>3. Cartes de statut</h4>
                <p>Quatre cartes affichent l'état de :</p>
                <ul>
                    <li><strong>Authentification</strong> : État de la connexion à l'API BIHR</li>
                    <li><strong>Produits</strong> : Nombre de produits dans WooCommerce</li>
                    <li><strong>Commandes</strong> : Nombre de commandes synchronisées</li>
                    <li><strong>Stocks</strong> : État de la synchronisation automatique des stocks</li>
                </ul>
                <p>Chaque carte affiche :</p>
                <ul>
                    <li>Une icône de statut (✅ OK, ⚠️ Attention, ❌ Erreur)</li>
                    <li>Un titre et une description</li>
                    <li>Des actions rapides (boutons de redirection)</li>
                </ul>

                <h4>4. Actions rapides</h4>
                <p>Boutons pour accéder rapidement aux fonctionnalités principales :</p>
                <ul>
                    <li><strong>🔐 Authentification</strong> : Configurer les identifiants BIHR</li>
                    <li><strong>📦 Produits BIHR</strong> : Gérer l'import des produits</li>
                    <li><strong>🛒 Commandes</strong> : Configurer la synchronisation des commandes</li>
                    <li><strong>💰 Marges</strong> : Configurer les marges de prix</li>
                    <li><strong>📚 Aide & Support</strong> : Accéder à la documentation</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box bihr-info-box">
                <h3>💡 Astuce</h3>
                <p>Le Dashboard est votre point de départ. Vérifiez toujours l'état de l'authentification avant de commencer à importer des produits.</p>
            </div>
        </div>

        <!-- Authentification -->
        <div id="auth" class="bihr-tutorial-section">
            <h2>🔐 Authentification</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page permet de configurer les identifiants pour se connecter à l'API BIHR et à l'API OpenAI (optionnel).</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📝 Champs du formulaire</h3>
                
                <h4>1. Username Bihr</h4>
                <p>Votre identifiant BIHR pour accéder à l'API. Ce champ est <strong>obligatoire</strong> pour utiliser le plugin.</p>

                <h4>2. Password Bihr</h4>
                <p>Votre mot de passe BIHR. Ce champ est <strong>obligatoire</strong> pour utiliser le plugin.</p>
                <p><strong>Note :</strong> Le mot de passe est stocké de manière sécurisée dans la base de données WordPress.</p>

                <h4>3. Clé API OpenAI (optionnel)</h4>
                <p>Votre clé API OpenAI pour activer l'enrichissement automatique des produits par IA.</p>
                <ul>
                    <li><strong>Bouton "🧪 Tester la clé"</strong> : Teste la validité de la clé sans sauvegarder</li>
                    <li><strong>Lien "Configurer ma clé API OpenAI"</strong> : Ouvre la page OpenAI pour créer/gérer vos clés</li>
                </ul>
                <p><strong>Fonctionnalités activées si clé renseignée :</strong></p>
                <ul>
                    <li>Génération automatique d'un <strong>nom amélioré</strong> pour chaque produit</li>
                    <li>Génération automatique d'une <strong>description courte</strong> (2-3 phrases)</li>
                    <li>Génération automatique d'une <strong>description longue</strong> (4-6 paragraphes)</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>🔄 Processus de sauvegarde</h3>
                <ol>
                    <li>Cliquez sur <strong>"Sauvegarder & Tester l'authentification"</strong></li>
                    <li>Le plugin teste automatiquement la connexion BIHR</li>
                    <li>Si une clé OpenAI est renseignée, elle est également testée</li>
                    <li>Des messages de succès/erreur s'affichent selon les résultats</li>
                </ol>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📋 Token actuel</h3>
                <p>En bas de la page, un champ affiche le token BIHR actuel (valable environ 30 minutes).</p>
                <p>Ce token est utilisé automatiquement pour toutes les requêtes API. Il est renouvelé automatiquement si nécessaire.</p>
            </div>

            <div class="bihr-tutorial-box bihr-warning-box">
                <h3>⚠️ Important</h3>
                <ul>
                    <li>Les identifiants BIHR sont <strong>obligatoires</strong> pour utiliser le plugin</li>
                    <li>La clé OpenAI est <strong>optionnelle</strong> mais recommandée pour améliorer les descriptions produits</li>
                    <li>En cas d'erreur d'authentification, vérifiez vos identifiants sur le site BIHR</li>
                </ul>
            </div>
        </div>

        <!-- Produits BIHR -->
        <div id="products" class="bihr-tutorial-section">
            <h2>📦 Produits BIHR</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page est le cœur du plugin. Elle permet de gérer l'import des catalogues BIHR, de visualiser les produits disponibles et de les importer dans WooCommerce.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📥 Section 1 : Fusion des catalogues CSV</h3>
                
                <h4>Option A : Téléchargement automatique</h4>
                <p>Cette option télécharge automatiquement tous les catalogues depuis l'API BIHR :</p>
                <ul>
                    <li><strong>References</strong> : Informations de base des produits (codes, noms, descriptions)</li>
                    <li><strong>ExtendedReferences</strong> : Descriptions détaillées et catégories</li>
                    <li><strong>Attributes</strong> : Attributs techniques des produits</li>
                    <li><strong>Images</strong> : URLs des images produits</li>
                    <li><strong>Stocks</strong> : Niveaux de stock actuels</li>
                </ul>
                <p><strong>⚠️ Attention :</strong> Cette opération peut prendre plusieurs minutes.</p>
                <p>Une barre de progression affiche l'avancement du téléchargement.</p>

                <h4>Option B : Import manuel</h4>
                <p>Si vous avez déjà les fichiers CSV, vous pouvez :</p>
                <ol>
                    <li>Placer les fichiers dans <code>wp-content/uploads/bihr-import/</code></li>
                    <li>Cliquer sur <strong>"Fusionner les catalogues"</strong></li>
                </ol>
                <p><strong>Fichiers nécessaires :</strong></p>
                <ul>
                    <li><code>cat-ref-full-*.csv</code> (References)</li>
                    <li><code>cat-extref-full-*.csv</code> (ExtendedReferences, peut être divisé en plusieurs fichiers _A, _B, etc.)</li>
                    <li><code>cat-prices-*.csv</code> (Prix, généré spécifiquement pour votre compte)</li>
                    <li><code>cat-images-*.csv</code> (Images)</li>
                    <li><code>cat-inventory-*.csv</code> (Stocks)</li>
                    <li><code>cat-attributes-*.csv</code> (Attributs)</li>
                </ul>

                <h4>Recalculer les catégories depuis cat-ref-full</h4>
                <p>Ce bouton permet de recalculer les colonnes <code>cat_l1</code>, <code>cat_l2</code>, <code>cat_l3</code> depuis le fichier <code>cat-ref-full-*.csv</code> le plus récent.</p>
                <p><strong>Utilité :</strong> Si vous avez mis à jour le fichier cat-ref-full, ce bouton met à jour les catégories dans la base de données.</p>
                <p><strong>⚠️ Important :</strong> Cette opération met à jour uniquement les catégories CategoryPath, pas la colonne "category" (RIDER GEAR, etc.).</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>💰 Section 2 : Catalog Prices (gestion asynchrone)</h3>
                
                <p>Le catalogue <strong>Prices</strong> est spécifique à votre compte et peut prendre 30 à 60 minutes pour être généré lors de la première demande de la journée.</p>
                
                <h4>Génération manuelle</h4>
                <p>Cliquez sur <strong>"Démarrer la génération"</strong> pour lancer la création du fichier Prices.</p>
                <p>Le statut est surveillé automatiquement via WP-Cron. Vous pouvez aussi vérifier manuellement avec le bouton <strong>"Vérifier le statut maintenant"</strong>.</p>

                <h4>Planning automatique</h4>
                <p>Configurez un planning pour générer automatiquement le catalogue Prices :</p>
                <ul>
                    <li><strong>Jour de la semaine</strong> : Choisissez le jour (ex: Lundi)</li>
                    <li><strong>Fréquence</strong> : Hebdomadaire ou Mensuel</li>
                    <li><strong>Heure</strong> : Heure de génération (ex: 02:00)</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>🔍 Section 3 : Filtres de recherche</h3>
                
                <p>Cette section permet de filtrer les produits BIHR avant de les importer dans WooCommerce.</p>

                <h4>Filtres disponibles :</h4>
                <ul>
                    <li><strong>Recherche</strong> : Recherche dans le code produit, nom, description</li>
                    <li><strong>Stock</strong> : En stock / Rupture de stock</li>
                    <li><strong>Prix min/max</strong> : Filtrer par fourchette de prix HT (dealer)</li>
                    <li><strong>Catégorie</strong> : Filtrer par catégorie BIHR (RIDER GEAR, VEHICLE PARTS, etc.)</li>
                    <li><strong>Niveau 1 (CategoryPath)</strong> : Première catégorie du CategoryPath (ex: "Moteur")</li>
                    <li><strong>Niveau 2</strong> : Sous-catégorie (dépend du Niveau 1, ex: "Admission")</li>
                    <li><strong>Niveau 3</strong> : Sous-sous-catégorie (dépend du Niveau 1+2, ex: "Gicleur")</li>
                    <li><strong>Trier par</strong> : Nom, Prix, Stock (croissant/décroissant)</li>
                </ul>

                <h4>Filtres dépendants (Niveau 1 → 2 → 3)</h4>
                <p>Les filtres de catégories sont dépendants :</p>
                <ol>
                    <li>Sélectionnez un <strong>Niveau 1</strong> → Le <strong>Niveau 2</strong> se remplit automatiquement via AJAX</li>
                    <li>Sélectionnez un <strong>Niveau 2</strong> → Le <strong>Niveau 3</strong> se remplit automatiquement</li>
                </ol>
                <p>Les filtres sont persistants : si vous rechargez la page, vos sélections sont conservées.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📋 Section 4 : Tableau des produits</h3>
                
                <p>Le tableau affiche tous les produits BIHR correspondant aux filtres sélectionnés.</p>

                <h4>Colonnes affichées :</h4>
                <ul>
                    <li><strong>Checkbox</strong> : Sélectionner des produits pour import multiple</li>
                    <li><strong>ID</strong> : Identifiant unique dans wp_bihr_products</li>
                    <li><strong>Code produit</strong> : Code BIHR ou NewPartNumber</li>
                    <li><strong>Nom</strong> : Nom du produit</li>
                    <li><strong>Catégorie (Bihr)</strong> : Catégorie principale (RIDER GEAR, etc.)</li>
                    <li><strong>Niveau 1/2/3</strong> : Catégories du CategoryPath</li>
                    <li><strong>Description</strong> : Description du produit (tronquée)</li>
                    <li><strong>Image</strong> : Aperçu de l'image produit</li>
                    <li><strong>Stock</strong> : Niveau de stock actuel</li>
                    <li><strong>Prix HT (dealer)</strong> : Prix fournisseur HT</li>
                    <li><strong>Action</strong> : Bouton "Importer dans WooCommerce"</li>
                </ul>

                <h4>Actions disponibles :</h4>
                <ul>
                    <li><strong>Sélectionner tout (page)</strong> : Sélectionne tous les produits de la page actuelle</li>
                    <li><strong>Désélectionner tout</strong> : Désélectionne tous les produits</li>
                    <li><strong>Importer les produits sélectionnés</strong> : Importe tous les produits cochés (avec barre de progression)</li>
                    <li><strong>Importer tous les produits filtrés</strong> : Importe TOUS les produits correspondant aux filtres (pas seulement ceux de la page)</li>
                    <li><strong>Importer dans WooCommerce</strong> (par produit) : Importe un produit individuel</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📄 Pagination</h3>
                
                <p>En bas du tableau, la pagination permet de naviguer entre les pages :</p>
                <ul>
                    <li><strong>Page précédente / suivante</strong> : Navigation classique</li>
                    <li><strong>Champ de saisie</strong> : Saisissez directement le numéro de page souhaité</li>
                    <li><strong>Bouton "Aller"</strong> : Valide la saisie (ou appuyez sur Entrée)</li>
                </ul>
                <p><strong>Note :</strong> Les filtres sont préservés lors du changement de page.</p>
            </div>

            <div class="bihr-tutorial-box bihr-info-box">
                <h3>💡 Workflow recommandé</h3>
                <ol>
                    <li>Téléchargez ou importez les catalogues CSV</li>
                    <li>Générez le catalogue Prices (si nécessaire)</li>
                    <li>Utilisez les filtres pour trouver les produits souhaités</li>
                    <li>Sélectionnez les produits à importer</li>
                    <li>Cliquez sur "Importer les produits sélectionnés" ou "Importer tous les produits filtrés"</li>
                    <li>Suivez la progression dans la barre de progression</li>
                </ol>
            </div>
        </div>

        <!-- Produits Importés -->
        <div id="imported" class="bihr-tutorial-section">
            <h2>✅ Produits Importés</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page liste tous les produits WooCommerce qui ont été importés depuis BIHR.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📋 Fonctionnalités</h3>
                <ul>
                    <li><strong>Liste des produits</strong> : Affiche tous les produits WooCommerce avec leur code BIHR</li>
                    <li><strong>Recherche</strong> : Recherche par nom, SKU ou code BIHR</li>
                    <li><strong>Liens directs</strong> : Accès rapide à la page d'édition de chaque produit</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>🔍 Utilisation</h3>
                <p>Cette page est utile pour :</p>
                <ul>
                    <li>Vérifier quels produits ont été importés</li>
                    <li>Retrouver rapidement un produit importé</li>
                    <li>Éditer un produit importé dans WooCommerce</li>
                </ul>
            </div>
        </div>

        <!-- Compatibilité -->
        <div id="compatibility" class="bihr-tutorial-section">
            <h2>🚗 Compatibilité</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page permet d'importer les données de compatibilité véhicule (liste des véhicules et liens de compatibilité).</p>
                <p><strong>⚠️ Fonctionnalité Premium :</strong> Cette page est réservée à la version Pro du plugin.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📥 Import des véhicules</h3>
                <p>Importe la liste des véhicules compatibles depuis un fichier ZIP ou CSV.</p>
                <p>Le fichier doit contenir : Fabricant, Modèle, Année, Version, Code véhicule.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>🔗 Import de la compatibilité</h3>
                <p>Importe les liens de compatibilité entre les numéros de pièces et les véhicules.</p>
                <p>Le fichier doit contenir : Code véhicule, Numéro de pièce, Compatibilité.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📊 Statistiques</h3>
                <p>Affiche le nombre de véhicules et de liens de compatibilité importés.</p>
            </div>
        </div>

        <!-- Commandes -->
        <div id="orders" class="bihr-tutorial-section">
            <h2>🛒 Commandes</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page configure la synchronisation automatique des commandes WooCommerce vers l'API BIHR.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>⚙️ Options de configuration</h3>
                
                <h4>1. Synchronisation automatique</h4>
                <p>Si activée, toutes les nouvelles commandes WooCommerce sont automatiquement transmises à l'API BIHR.</p>
                <p><strong>Déclencheurs :</strong></p>
                <ul>
                    <li>Création d'une commande (statut "processing" ou "completed")</li>
                    <li>Changement de statut d'une commande existante</li>
                </ul>

                <h4>2. Validation automatique</h4>
                <p>Si activée, les commandes sont automatiquement validées côté BIHR sans intervention manuelle.</p>
                <p><strong>⚠️ Attention :</strong> Cette option valide immédiatement la commande. Désactivez-la si vous voulez valider manuellement.</p>

                <h4>3. Statuts de commande à synchroniser</h4>
                <p>Choisissez quels statuts WooCommerce déclenchent la synchronisation :</p>
                <ul>
                    <li><strong>En attente</strong> : Commandes non payées</li>
                    <li><strong>En cours de traitement</strong> : Commandes payées en cours</li>
                    <li><strong>En attente de paiement</strong> : Commandes en attente de validation de paiement</li>
                    <li><strong>Terminée</strong> : Commandes complétées</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📋 Informations de commande</h3>
                <p>Sur chaque page de commande WooCommerce, un bloc affiche :</p>
                <ul>
                    <li><strong>Ticket ID BIHR</strong> : Identifiant de la commande dans BIHR</li>
                    <li><strong>Statut de génération</strong> : État de la génération de la commande</li>
                    <li><strong>Bouton "Récupérer les données"</strong> : Télécharge les détails de la commande depuis BIHR</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box bihr-warning-box">
                <h3>⚠️ Important</h3>
                <ul>
                    <li>La synchronisation automatique ne fonctionne que si l'authentification BIHR est configurée</li>
                    <li>Les commandes doivent contenir au moins un produit pour être synchronisées</li>
                    <li>Si la boutique n'est pas encore en ligne, désactivez la synchronisation automatique</li>
                </ul>
            </div>
        </div>

        <!-- Marges -->
        <div id="margins" class="bihr-tutorial-section">
            <h2>💰 Marges</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page permet de configurer les marges appliquées automatiquement sur les prix fournisseur lors de l'import dans WooCommerce.</p>
                <p><strong>⚠️ Fonctionnalité Premium :</strong> Cette page est réservée à la version Pro du plugin.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📊 Types de marges</h3>
                
                <h4>1. Marge par défaut (globale)</h4>
                <p>Cette marge s'applique à tous les produits qui n'ont pas de règle spécifique.</p>
                <p><strong>Types disponibles :</strong></p>
                <ul>
                    <li><strong>Pourcentage</strong> : Marge en % (ex: 20% = prix × 1.20)</li>
                    <li><strong>Fixe</strong> : Marge en euros (ex: 5€ = prix + 5)</li>
                </ul>

                <h4>2. Marges par catégorie</h4>
                <p>Définissez des marges spécifiques pour chaque catégorie BIHR :</p>
                <ul>
                    <li>RIDER GEAR</li>
                    <li>VEHICLE PARTS & ACCESSORIES</li>
                    <li>LIQUIDS & LUBRICANTS</li>
                    <li>TIRES & ACCESSORIES</li>
                    <li>TOOLING & WS</li>
                    <li>OTHER PRODUCTS & SERVICES</li>
                </ul>
                <p>Chaque catégorie peut avoir sa propre marge (pourcentage ou fixe).</p>

                <h4>3. Marges par tranche de prix</h4>
                <p>Définissez des marges selon la fourchette de prix du produit :</p>
                <ul>
                    <li>Exemple : 0-50€ → 25%, 50-100€ → 20%, 100€+ → 15%</li>
                </ul>
                <p>Vous pouvez créer plusieurs tranches avec des marges différentes.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>⚙️ Priorité d'application</h3>
                <p>Vous pouvez choisir la priorité d'application des marges :</p>
                <ul>
                    <li><strong>Spécifique</strong> : Tranche de prix → Catégorie → Défaut (dans cet ordre)</li>
                    <li><strong>Globale uniquement</strong> : Seule la marge par défaut est appliquée</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box bihr-info-box">
                <h3>💡 Exemple de calcul</h3>
                <p>Prix fournisseur : 100€ HT</p>
                <p>Marge configurée : 20% (pourcentage)</p>
                <p><strong>Prix de vente = 100€ × 1.20 = 120€ HT</strong></p>
            </div>
        </div>

        <!-- Logs -->
        <div id="logs" class="bihr-tutorial-section">
            <h2>📊 Logs</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page affiche tous les logs du plugin pour le débogage et le suivi des opérations.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📋 Contenu des logs</h3>
                <p>Les logs enregistrent :</p>
                <ul>
                    <li>Toutes les opérations d'import (succès, erreurs)</li>
                    <li>Les appels API BIHR</li>
                    <li>Les erreurs de synchronisation</li>
                    <li>Les opérations de fusion de catalogues</li>
                    <li>Les tests d'authentification</li>
                    <li>Les opérations d'enrichissement IA</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>🔧 Actions disponibles</h3>
                <ul>
                    <li><strong>Effacer les logs</strong> : Vide le fichier de logs</li>
                    <li><strong>Télécharger les logs</strong> : Télécharge le fichier de logs complet</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box bihr-info-box">
                <h3>💡 Astuce</h3>
                <p>En cas de problème, consultez toujours les logs en premier. Ils contiennent des informations détaillées sur les erreurs.</p>
            </div>
        </div>

        <!-- Synchro SKU -->
        <div id="sku-sync" class="bihr-tutorial-section">
            <h2>🔄 Synchro SKU</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page permet de synchroniser les SKU des produits WooCommerce avec les numéros de pièces de compatibilité.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📋 Fonctionnalités</h3>
                <ul>
                    <li><strong>Synchronisation automatique</strong> : Met à jour les SKU des produits importés</li>
                    <li><strong>Correspondance</strong> : Fait correspondre les codes BIHR avec les SKU WooCommerce</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>🔍 Utilisation</h3>
                <p>Cette fonctionnalité est utile pour :</p>
                <ul>
                    <li>Assurer la cohérence entre les SKU WooCommerce et les codes BIHR</li>
                    <li>Permettre au filtre de compatibilité véhicule de fonctionner correctement</li>
                </ul>
            </div>
        </div>

        <!-- WP-Cron -->
        <div id="wpcron" class="bihr-tutorial-section">
            <h2>⚙️ WP-Cron</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page affiche le diagnostic du système WP-Cron de WordPress, utilisé pour les tâches automatiques du plugin.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📊 Informations affichées</h3>
                <ul>
                    <li><strong>État de WP-Cron</strong> : Activé ou désactivé</li>
                    <li><strong>Tâches planifiées</strong> : Liste des tâches automatiques du plugin</li>
                    <li><strong>Prochaine exécution</strong> : Date et heure de la prochaine tâche</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>🔧 Tâches automatiques</h3>
                <p>Le plugin utilise WP-Cron pour :</p>
                <ul>
                    <li>Génération automatique du catalogue Prices (selon planning)</li>
                    <li>Synchronisation automatique des stocks (si activée)</li>
                    <li>Vérification périodique du statut des commandes</li>
                </ul>
            </div>

            <div class="bihr-tutorial-box bihr-warning-box">
                <h3>⚠️ Important</h3>
                <p>Si WP-Cron est désactivé (DISABLE_WP_CRON), vous devez configurer un cron serveur pour que les tâches automatiques fonctionnent.</p>
            </div>
        </div>

        <!-- Aide -->
        <div id="help" class="bihr-tutorial-section">
            <h2>📚 Aide</h2>
            
            <div class="bihr-tutorial-box">
                <h3>🎯 Vue d'ensemble</h3>
                <p>Cette page contient la documentation complète des shortcodes et filtres frontend du plugin.</p>
            </div>

            <div class="bihr-tutorial-box">
                <h3>📖 Sections disponibles</h3>
                <ul>
                    <li><strong>Filtre de Compatibilité Véhicule</strong> : Documentation du shortcode <code>[bihr_vehicle_filter]</code></li>
                    <li><strong>Filtre de Produits par Catégories</strong> : Documentation du shortcode <code>[bihr_product_filter]</code></li>
                </ul>
            </div>

            <div class="bihr-tutorial-box">
                <h3>💡 Contenu</h3>
                <p>Pour chaque filtre, la page explique :</p>
                <ul>
                    <li>À quoi sert le filtre</li>
                    <li>Comment utiliser le shortcode</li>
                    <li>Les options disponibles</li>
                    <li>Des exemples d'utilisation</li>
                    <li>Comment l'intégrer dans WordPress</li>
                    <li>Les prérequis nécessaires</li>
                </ul>
            </div>
        </div>

        <!-- Section Support -->
        <div class="bihr-tutorial-section">
            <h2>🆘 Besoin d'aide supplémentaire ?</h2>
            <div class="bihr-tutorial-box">
                <p>Si vous rencontrez des problèmes ou avez des questions :</p>
                <ul>
                    <li>📖 Consultez la <a href="https://github.com/DRCOMPUTER60290/bihr-synch" target="_blank">documentation complète sur GitHub</a></li>
                    <li>📧 Contactez le support via le menu Freemius (si vous avez une licence premium)</li>
                    <li>🐛 Vérifiez les <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihr-logs' ) ); ?>">logs du plugin</a> en cas d'erreur</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.bihr-tutorial-page {
    max-width: 1400px;
}

.bihr-tutorial-intro {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.bihr-tutorial-content {
    margin-top: 30px;
}

.bihr-tutorial-toc {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.bihr-tutorial-toc h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #2271b1;
}

.bihr-toc-list {
    list-style: none;
    padding: 0;
    margin: 15px 0 0 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.bihr-toc-list li {
    margin: 0;
}

.bihr-toc-list a {
    display: block;
    padding: 10px 15px;
    background: #f9f9f9;
    border-left: 3px solid #2271b1;
    text-decoration: none;
    color: #2271b1;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.bihr-toc-list a:hover {
    background: #f0f6fc;
    border-left-color: #005a87;
    transform: translateX(5px);
}

.bihr-tutorial-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    scroll-margin-top: 20px;
}

.bihr-tutorial-section h2 {
    margin-top: 0;
    padding-bottom: 15px;
    border-bottom: 3px solid #2271b1;
    color: #23282d;
    font-size: 28px;
}

.bihr-tutorial-box {
    background: #f9f9f9;
    border-left: 4px solid #2271b1;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.bihr-tutorial-box h3 {
    margin-top: 0;
    color: #2271b1;
    font-size: 20px;
}

.bihr-tutorial-box h4 {
    color: #23282d;
    font-size: 16px;
    margin-top: 20px;
    margin-bottom: 10px;
}

.bihr-tutorial-box ul,
.bihr-tutorial-box ol {
    line-height: 1.8;
    margin-left: 20px;
}

.bihr-tutorial-box code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.bihr-info-box {
    border-left-color: #00a32a;
    background: #f0f6fc;
}

.bihr-info-box h3 {
    color: #00a32a;
}

.bihr-warning-box {
    border-left-color: #d63638;
    background: #fcf0f1;
}

.bihr-warning-box h3 {
    color: #d63638;
}

.bihr-tutorial-box p {
    line-height: 1.6;
    color: #50575e;
}

.bihr-tutorial-box strong {
    color: #23282d;
}

@media (max-width: 768px) {
    .bihr-toc-list {
        grid-template-columns: 1fr;
    }
    
    .bihr-tutorial-section {
        padding: 20px;
    }
}
</style>
