# BIHR-SYNCH - Plugin WooCommerce

Plugin WordPress pour la synchronisation automatique des produits BIHR avec WooCommerce.

## 📋 Informations

**Plugin:** BIHR WooCommerce Importer  
**Version:** 1.4.0  
**Auteur:** DrComputer60290 - Albert Benjamin  
**Entreprise:** DrComputer60290  
**Site web:** [drcomputer60290.fr](https://drcomputer60290.fr)  

### 📞 Contact

**Représentant:** M. Albert Benjamin  
**Adresse:** 81 rue René Cassin, 60290 Laigneville, France  
**Email:** webmaster@drcomputer60290.fr  
**Téléphone:** 07 86 99 08 35  

---

## 📋 Table des matières

- [Description](#description)
- [Fonctionnalités](#fonctionnalités)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Performance](#performance)
- [Structure des fichiers](#structure-des-fichiers)
- [API et catalogues](#api-et-catalogues)
- [Enrichissement IA](#enrichissement-ia)
- [FAQ](#faq)
- [Support](#support)

## 🎯 Description

**BIHR-SYNCH** est un plugin WordPress conçu pour synchroniser automatiquement les catalogues de produits BIHR avec votre boutique WooCommerce. Il gère le téléchargement, la fusion, l'enrichissement et l'import des produits de manière automatisée.

### Caractéristiques principales

- 🔄 **Synchronisation automatique** des catalogues BIHR
- 🤖 **Enrichissement IA** des descriptions via OpenAI GPT-4
- 📊 **Filtrage avancé** (catégorie, prix, stock, recherche)
- 📦 **Import multi-produits** avec barres de progression en temps réel
- 🔢 **Prise en compte de `NewPartNumber`** (affichage + recherche + SKU WooCommerce)
- 🖼️ **Gestion automatique des images**
- 📈 **Gestion des stocks** en temps réel avec synchronisation programmable
- 🔐 **Authentification OAuth** sécurisée
- 📦 **Synchronisation automatique des commandes** vers l'API BIHR
- 🏍️ **Filtre véhicule** pour les clients (compatibilité produits)
- 🔁 **Outil admin “Synchro SKU”** depuis la compatibilité véhicules
- ⚡ **Ultra-optimisé** : 10× plus rapide qu'initialement

## ✨ Fonctionnalités

### 1. Authentification

#### OAuth BIHR API
- Connexion sécurisée à l'API BIHR
- Stockage chiffré des tokens d'accès
- Rafraîchissement automatique des tokens

#### Clé OpenAI
- Intégration OpenAI GPT-4 pour l'enrichissement
- Validation de la clé en temps réel
- Test de connectivité API

**Page:** `Bihr Import > Authentification`

### 2. Téléchargement des catalogues

Le plugin télécharge automatiquement 6 types de catalogues :

| Catalogue | Contenu | Utilité |
|-----------|---------|---------|
| **References** | Codes produits, noms, descriptions de base | Base de données principale |
| **ExtendedReferences** | Descriptions longues, catégories | Enrichissement des informations |
| **Prices** | Prix revendeur HT | Tarification |
| **Images** | URLs des images produits | Visuels |
| **Inventory** | Niveaux de stock | Disponibilité |
| **Attributes** | Attributs techniques | Spécifications détaillées |

**Fonctionnalités:**
- ✅ Téléchargement ZIP automatique
- ✅ Extraction et fusion des catalogues
- ✅ Gestion des fichiers multiples (ExtendedReferences A-G)
- ✅ Barre de progression en temps réel
- ✅ Logs détaillés de chaque opération

**Page:** `Bihr Import > Authentification` (section Téléchargement)

### 3. Gestion des catégories

#### Mapping automatique
Le plugin mappe automatiquement les codes de catégorie BIHR vers des noms lisibles :

```
A → RIDER GEAR
B → VEHICLE PARTS & ACCESSORIES
C → LIQUIDS & LUBRICANTS
D → TIRES & ACCESSORIES
E → TOOLING & WS
G → OTHER PRODUCTS & SERVICES
```

#### Création automatique
- Création automatique des catégories WooCommerce
- Pas de duplication (détection des catégories existantes)
- Assignment automatique lors de l'import

### 4. Système de filtrage avancé

**Page:** `Bihr Import > Produits Bihr`

#### Filtres disponibles

##### 🔍 Recherche textuelle
- Recherche dans : code produit, **NewPartNumber**, nom, description
- Insensible à la casse
- Correspondance partielle

##### 📦 Filtre de stock
- **Tous** : Affiche tous les produits
- **En stock** : Produits avec stock > 0
- **Rupture** : Produits avec stock = 0

##### 💰 Filtre de prix
- **Prix minimum** : Seuil bas (€ HT)
- **Prix maximum** : Seuil haut (€ HT)
- Filtrage par plage personnalisée

##### 🏷️ Filtre de catégorie
- Dropdown des catégories disponibles
- Extraction dynamique depuis la base
- Option "Toutes les catégories"

##### 🔄 Tri des résultats
- **Par défaut** : ID ascendant
- **Prix** : Croissant / Décroissant
- **Nom** : A-Z / Z-A
- **Stock** : Croissant / Décroissant

### 5. Prévisualisation et import des produits

#### Tableau de produits

Affichage complet avec colonnes :
- ☑️ **Sélection** : Case à cocher
- 🔢 **ID** : ID interne
- 📦 **Code** : `NewPartNumber` (si présent) sinon `ProductCode`
- 📝 **Nom** : Nom du produit (priorité : `longdescription1`)
- 💶 **Prix HT** : Prix revendeur
- 📊 **Stock** : Niveau de stock
- 🏷️ **Catégorie** : Catégorie assignée
- 🖼️ **Image** : Miniature (64x64px)
- ⚙️ **Actions** : Bouton d'import individuel

#### Import multi-produits

**Fonctionnalités:**
- ✅ Sélection multiple avec cases à cocher
- ✅ "Tout sélectionner" / "Tout désélectionner"
- ✅ Compteur dynamique de produits sélectionnés
- ✅ Barre de progression en temps réel
- ✅ Journal détaillé par produit :
  - 🔄 En cours (icône animée)
  - ✅ Succès (avec WC ID)
  - ❌ Erreur (avec message)
- ✅ Import séquentiel (500ms entre chaque)
- ✅ Décochage automatique des produits importés

**Avantages:**
- Évite la surcharge serveur
- Traçabilité complète
- Gestion d'erreurs granulaire
- Possibilité de réimporter les échecs

### 6. Enrichissement IA (OpenAI GPT-4)

#### Activation
L'enrichissement IA s'active automatiquement si une clé OpenAI valide est configurée.

#### Modèles supportés

| Modèle | Usage | Capacités |
|--------|-------|-----------|
| **GPT-4o** | Produits avec image | Vision + texte |
| **GPT-4o-mini** | Produits sans image | Texte uniquement |

#### Processus d'enrichissement

1. **Analyse** du nom et de l'image du produit
2. **Génération** de deux descriptions :
   - **Description courte** : Accroche marketing (2-3 phrases)
   - **Description longue** : Contenu détaillé avec bénéfices
3. **Intégration** automatique dans WooCommerce :
   - `short_description` → Excerpt WooCommerce
   - `long_description` → Description principale
4. **Fallback** : Utilise les descriptions CSV si l'IA échoue

#### Format de réponse IA

```
[SHORT]
Texte de la description courte ici...
[/SHORT]

[LONG]
Texte de la description longue ici...
[/LONG]
```

#### Avantages
- 🎯 Descriptions optimisées SEO
- 💼 Ton professionnel et engageant
- 🖼️ Analyse visuelle des produits
- ⚡ Génération en moins de 10 secondes

### 7. Gestion des images

#### Téléchargement automatique
- URL de base : `https://api.mybihr.com`
- Détection du type MIME
- Support : JPG, PNG, GIF, WebP

#### Optimisations
- ✅ Évite les doublons (meta `_bihr_image_source`)
- ✅ Association automatique au produit WooCommerce
- ✅ Définition comme image principale
- ✅ Génération des miniatures WordPress

### 8. Synchronisation des prix

#### Prix revendeur HT
- Import depuis le catalogue **Prices**
- Stockage en `dealer_price_ht`
- Application comme prix régulier WooCommerce

#### Possibilités d'extension
- Ajouter une marge personnalisée
- Gérer les prix TTC
- Créer des prix promotionnels

### 9. Gestion des stocks

#### Synchronisation
- Import depuis le catalogue **Inventory**
- Colonne `StockLevel` → `stock_level`
- Mise à jour automatique du statut WooCommerce :
  - `instock` si stock > 0
  - `outofstock` si stock = 0

#### Gestion WooCommerce
- Activation de "Gérer le stock"
- Quantité synchronisée
- Statut de disponibilité automatique

### 10. Logs et débogage

**Page:** `Bihr Import > Logs`

#### Fonctionnalités
- 📝 Logs horodatés de toutes les opérations
- 🔍 Traçabilité complète des imports
- 🐛 Détection et affichage des erreurs
- 🗑️ Bouton "Vider les logs"

#### Événements enregistrés
- Authentification OAuth
- Téléchargement des catalogues
- Fusion des données
- Import WooCommerce
- Enrichissement IA
- Erreurs et exceptions

### 11. Priorité des noms de produits

Le plugin utilise une hiérarchie intelligente pour déterminer le nom du produit :

```
1. longdescription1    (Priorité 1 - References)
2. furtherdescription  (Priorité 2 - References)
3. shortdescription    (Priorité 3 - References)
4. name                (Priorité 4 - Fallback)
```

Cette logique garantit que les noms les plus descriptifs sont utilisés.

### 12. Actions disponibles

#### Page Authentification
- 🔑 Configurer les clés API (BIHR + OpenAI)
- 📥 Télécharger tous les catalogues
- 🔄 Fusionner les catalogues
- ✅ Tester la connexion OpenAI

#### Page Produits
- 🔍 Filtrer et rechercher
- ☑️ Sélectionner des produits
- 📦 Importer (unitaire ou multiple)
- 📊 Voir l'état du stock et les prix

#### Page Synchro SKU
- 🔁 Synchroniser les SKU WooCommerce depuis la compatibilité véhicules
- ✅ Utilise `part_number` (compatibilité) comme SKU
- 🔍 Match automatique dans l'ordre : `NewPartNumber` → `_sku` actuel → `product_code`

#### Page Logs
- 📖 Consulter l'historique
- 🗑️ Vider les logs

#### Page Commandes
- ⚙️ Configurer la synchronisation automatique
- 🔄 Activer/désactiver l'envoi vers BIHR
- 📦 Paramétrer la validation et livraison
- 📊 Voir les dernières commandes synchronisées

### 13. Synchronisation automatique des commandes

**Page:** `Bihr Import > Commandes`

#### Fonctionnement automatique

Lorsqu'un client passe une commande sur votre boutique WooCommerce :

1. 🛒 **Détection** : Le plugin détecte la création de la commande
2. 🔍 **Vérification** : Vérifie que la commande contient des produits BIHR
3. 📤 **Envoi** : Transmet automatiquement la commande à l'API BIHR
4. 📝 **Confirmation** : Ajoute une note avec l'ID de commande BIHR
5. 📊 **Logs** : Enregistre tous les détails de la synchronisation

#### Configuration disponible

| Option | Description | Défaut |
|--------|-------------|--------|
| **Synchronisation auto** | Active/désactive l'envoi automatique | ✅ Activé |
| **Validation automatique** | Les commandes sont transformées en commande automatiquement | ✅ Activé |

#### Format de commande BIHR

Structure simplifiée selon la nouvelle API v2.1 :

```json
{
  "Order": {
    "CustomerReference": "WC Order #123 - John Doe",
    "Lines": [
      {
        "ProductId": "TPCI07495",
        "Quantity": 2,
        "CustomerReference": "Nom du produit"
      }
    ],
    "IsAutomaticCheckoutActivated": true
  },
  "DropShippingAddress": {
    "FirstName": "John",
    "LastName": "Doe",
    "Line1": "123 rue Example",
    "Line2": "Appartement 4B",
    "ZipCode": "75001",
    "Town": "Paris",
    "Country": "FR",
    "Phone": "+33123456789"
  }
}
```

#### Métadonnées de commande

Le plugin stocke les informations suivantes sur chaque commande WooCommerce :

| Meta Key | Description |
|----------|-------------|
| `_bihr_order_synced` | Commande synchronisée avec succès |
| `_bihr_sync_ticket_id` | Ticket ID WooCommerce (identifiant interne) |
| `_bihr_api_ticket_id` | Ticket ID retourné par l'API BIHR |
| `_bihr_order_url` | URL de la commande/panier sur mybihr.com |
| `_bihr_sync_date` | Date et heure de synchronisation |
| `_bihr_order_sync_failed` | Échec de synchronisation |
| `_bihr_sync_error` | Message d'erreur détaillé |

#### Workflow asynchrone (nouveau)

La création de commande suit maintenant un workflow asynchrone similaire aux catalogues :

1. **Envoi** : POST `/api/v2.1/Order/Creation`
   - Retourne : `ResultCode` et `TicketId`
   - ResultCode peut être :
     - "Cart creation requested" → Panier créé (validation manuelle sur mybihr.com)
     - "Order creation requested" → Commande créée automatiquement

2. **Vérification** : GET `/api/v2.1/Order/GenerationStatus?TicketId={id}`
   - Retourne : `OrderUrl` et `RequestStatus`
   - RequestStatus peut être :
     - "Running" → Création en cours
     - "Cart" → Panier créé avec succès
     - "Order" → Commande créée avec succès
     - Message d'erreur si problème métier

3. **Stockage** : Le plugin enregistre automatiquement :
   - L'URL de la commande (`_bihr_order_url`)
   - Le TicketId BIHR (`_bihr_api_ticket_id`)
   - Le statut final dans les notes de commande

#### Format de réponse BIHR

L'API BIHR retourne la réponse suivante lors de la création d'une commande :

```json
{
  "ResultCode": "Cart creation requested",
  "TicketId": "a8287cc768dd40de8b225cc98bc30f82"
}
```

Le plugin capture automatiquement :
- **ResultCode** : Type de création ("Cart creation requested" ou "Order creation requested")
- **TicketId** : Identifiant unique pour vérifier le statut (stocké dans `_bihr_api_ticket_id`)
- **OrderUrl** : URL de la commande sur mybihr.com (récupéré via GenerationStatus, stocké dans `_bihr_order_url`)

Ces informations sont visibles dans :
- 📝 Les métadonnées de commande WooCommerce
- 📋 Les notes de commande
- 📊 Les logs du plugin (page Bihr Import > Logs)

#### Avantages

- ✅ **Automatisation complète** : Pas d'intervention manuelle
- ✅ **Traçabilité** : Notes ajoutées à chaque commande
- ✅ **Sécurité** : Vérification des produits BIHR uniquement
- ✅ **Formatage intelligent** : Numéros de téléphone internationaux
- ✅ **Adresses flexibles** : Livraison ou facturation
- ✅ **Logs détaillés** : Historique complet des synchronisations

#### Gestion des erreurs

En cas d'échec :
- ❌ La commande est marquée avec `_bihr_order_sync_failed`
- 📝 Le message d'erreur est stocké
- 📋 Une note est ajoutée à la commande
- 📊 L'erreur est loguée pour analyse

### 14. Synchronisation SKU depuis compatibilité véhicules

**Page:** `Bihr Import > Synchro SKU`

Cette fonctionnalité met à jour le SKU WooCommerce (`_sku`) pour qu'il corresponde au `part_number` de la table de compatibilité véhicules.

**Pourquoi ?** Le filtre véhicule frontend recherche les produits par SKU (ex: `WHERE _sku = part_number`).

**Correspondance automatique (anti-cas “CAR10206” / “1047882”) :**

1. `_bihr_new_part_number` (NewPartNumber)
2. `_sku` actuel
3. `_bihr_product_code` (code BIHR)

Si une correspondance est trouvée, le script met le SKU à jour avec `part_number`.

## 🚀 Installation

### Prérequis

- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+
- MySQL 5.7+

### Étapes d'installation

1. **Télécharger** le plugin depuis le dépôt GitHub
2. **Extraire** dans `/wp-content/plugins/bihr-woocommerce-importer/`
3. **Activer** le plugin depuis l'admin WordPress
4. **Configurer** les clés API (voir section Configuration)

### Structure des dossiers créés

```
wp-content/uploads/
└── bihr-import/          # Catalogues CSV téléchargés
    ├── cat-ref-*.csv
    ├── cat-extref-*.csv
    ├── cat-prices-*.csv
    └── ...
```

### Base de données

Le plugin crée automatiquement la table `wp_bihr_products` :

```sql
CREATE TABLE wp_bihr_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(100) UNIQUE,
    new_part_number VARCHAR(100),
    name VARCHAR(255),
    description TEXT,
    image_url VARCHAR(500),
    dealer_price_ht DECIMAL(10,2),
    stock_level INT,
    stock_description VARCHAR(255),
    category VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## ⚙️ Configuration

### 1. Configuration OAuth BIHR

1. Rendez-vous dans `Bihr Import > Authentification`
2. Renseignez vos identifiants BIHR :
   - **Client ID**
   - **Client Secret**
   - **Username**
   - **Password**
3. Cliquez sur **"Enregistrer les identifiants"**
4. Le plugin générera automatiquement un token d'accès

### 2. Configuration OpenAI (Optionnel)

1. Obtenir une clé API sur [platform.openai.com](https://platform.openai.com)
2. Dans la même page Authentification :
   - Saisissez votre **Clé API OpenAI**
3. Cliquez sur **"Enregistrer les identifiants"**
4. Le plugin teste automatiquement la validité de la clé

**Messages possibles:**
- ✅ "Clé OpenAI valide et opérationnelle"
- ❌ "Clé OpenAI invalide"
- ⚠️ "Quota OpenAI dépassé"

### 3. Configuration des constantes (optionnel)

Fichier `bihr-woocommerce-importer.php` :

```php
// URL de base pour les images
define( 'BIHRWI_IMAGE_BASE_URL', 'https://api.mybihr.com' );

// Version du plugin
define( 'BIHRWI_VERSION', '1.0.0' );
```

## 📖 Utilisation

### Workflow complet

#### 1️⃣ Première utilisation

```
Authentification → Télécharger catalogues → Fusionner → Filtrer → Importer
```

1. **Configurer** les clés API (BIHR + OpenAI si souhaité)
2. **Télécharger** tous les catalogues (bouton bleu)
3. **Fusionner** les catalogues (bouton vert)
4. Aller dans **"Produits"**
5. **Filtrer** les produits souhaités
6. **Sélectionner** les produits à importer
7. Cliquer sur **"Importer la sélection"**
8. **Suivre** la progression en temps réel

#### 2️⃣ Utilisation régulière

```
Télécharger → Fusionner → Importer nouveautés
```

1. **Télécharger** les catalogues mis à jour
2. **Fusionner** (met à jour les produits existants)
3. **Filtrer** par stock ou prix
4. **Importer** les nouveaux produits ou mises à jour

### Exemples de cas d'usage

#### Importer tous les casques (catégorie RIDER GEAR)

1. Page **Produits**
2. Filtre **Catégorie** : "RIDER GEAR"
3. Clic **"Tout sélectionner"**
4. Clic **"Importer la sélection"**

#### Importer uniquement les produits en stock entre 50€ et 200€

1. Page **Produits**
2. Filtre **Stock** : "En stock"
3. **Prix min** : 50
4. **Prix max** : 200
5. Clic **"Appliquer les filtres"**
6. Sélectionner les produits souhaités
7. Clic **"Importer la sélection"**

#### Rechercher un produit spécifique

1. Page **Produits**
2. Barre de **Recherche** : "HELMET XYZ"
3. Clic **"Rechercher"**
4. Import du produit trouvé

## 📁 Structure des fichiers

```
bihr-woocommerce-importer/
│
├── bihr-woocommerce-importer.php    # Fichier principal du plugin
├── README.md                         # Ce fichier
├── update-schema.sql                 # Script de mise à jour DB
│
├── admin/                            # Interface d'administration
│   ├── class-bihr-admin.php         # Contrôleur principal
│   ├── css/
│   │   └── bihr-admin.css           # Styles (progress bars, filtres)
│   ├── js/
│   │   └── bihr-progress.js         # JavaScript (AJAX, progression)
│   └── views/
│       ├── auth-page.php            # Page authentification
│       ├── compatibility-page.php    # Import compatibilité véhicules
│       ├── imported-products-page.php# Produits importés WooCommerce (suivi)
│       ├── logs-page.php            # Page logs
│       ├── margin-page.php           # Paramètres de marges
│       ├── orders-settings-page.php # Page paramètres commandes
│       ├── products-page.php         # Page produits (filtres + import)
│       └── sku-sync-compatibility-page.php # Page admin : Synchro SKU
│
└── includes/                         # Classes métier
    ├── class-bihr-ai-enrichment.php # Enrichissement OpenAI
    ├── class-bihr-api-client.php    # Client API BIHR (OAuth)
    ├── class-bihr-logger.php        # Système de logs
    ├── class-bihr-order-sync.php    # Synchronisation des commandes
  ├── class-bihr-product-sync.php  # Parsing CSV, fusion, import WooCommerce
  ├── class-bihr-vehicle-compatibility.php # Compatibilité véhicules
  └── class-bihr-vehicle-filter.php # Filtre véhicule frontend
```

### Rôle de chaque classe

| Classe | Responsabilité |
|--------|----------------|
| `BihrWI_Admin` | Gestion des pages admin, formulaires, AJAX |
| `BihrWI_AI_Enrichment` | Intégration OpenAI GPT-4 |
| `BihrWI_API_Client` | Authentification OAuth, téléchargement catalogues |
| `BihrWI_Logger` | Enregistrement des logs |
| `BihrWI_Order_Sync` | Synchronisation automatique des commandes |
| `BihrWI_Product_Sync` | Parsing CSV, fusion, import WooCommerce |

## 🔌 API et catalogues

### API BIHR

#### Endpoints utilisés

- **OAuth Token** : `https://api.mybihr.com/token`
- **Catalogues** : `https://api.mybihr.com/api/catalog/{type}`
- **Images** : `https://api.mybihr.com/{image_path}`
- **Création commande** : `https://api.mybihr.com/api/v2.1/Order/Creation`

#### Types de catalogues

```
References           → ref
ExtendedReferences  → extref
Prices              → prices
Images              → images
Inventory           → inventory
Attributes          → attributes
```

### Format des catalogues CSV

#### Séparateurs
- `;` (point-virgule) ou `,` (virgule)
- Détection automatique

#### Encodage
- UTF-8 avec BOM
- Headers normalisés en minuscules

#### Colonnes principales

**References:**
```csv
ProductCode;NewPartNumber;ShortDescription;FurtherDescription;LongDescription1
```

**ExtendedReferences:**
```csv
ProductCode;Description;LongDescription;TechnicalDescription;LongDescription1
```

**Prices:**
```csv
ProductCode;DealerPrice
```

**Images:**
```csv
ProductCode;Url;IsDefault
```

**Inventory:**
```csv
ProductId;StockLevel;StockLevelDescription
```

## 🤖 Enrichissement IA

### Configuration OpenAI

#### Modèles disponibles

```php
// Avec image (vision)
'gpt-4o'

// Sans image (texte uniquement)
'gpt-4o-mini'
```

#### Prompt système

Le plugin envoie un prompt optimisé pour générer des descriptions marketing :

```
Vous êtes un expert en rédaction de fiches produits pour une boutique de motos et équipements.

Génère deux descriptions pour ce produit :
1. Une description courte (2-3 phrases max)
2. Une description longue (1 paragraphe)

Format de réponse :
[SHORT]
Description courte ici
[/SHORT]

[LONG]
Description longue ici
[/LONG]
```

#### Timeout et gestion d'erreurs

- **Timeout** : 60 secondes
- **Fallback** : Descriptions CSV si échec
- **Logs** : Toutes les erreurs sont enregistrées

### Désactivation de l'IA

Pour désactiver l'enrichissement IA :
1. Supprimer la clé OpenAI de la page Authentification
2. Les imports utiliseront uniquement les descriptions CSV

## ❓ FAQ

### Comment mettre à jour les catalogues ?

Retéléchargez et fusionnez les catalogues depuis la page Authentification. La fusion met à jour les produits existants sans créer de doublons.

### Les produits sont-ils dupliqués lors d'un ré-import ?

Non. Le plugin vérifie le `product_code` et met à jour les produits existants au lieu de créer des doublons.

### Puis-je importer sans l'enrichissement IA ?

Oui. Sans clé OpenAI, le plugin utilisera les descriptions des catalogues CSV.

### Comment gérer les catégories personnalisées ?

Modifiez la fonction `get_category_mapping()` dans `class-bihr-product-sync.php` pour ajouter vos propres mappings.

### Que faire en cas d'erreur d'import ?

1. Consultez la page **Logs** pour identifier l'erreur
2. Vérifiez les permissions WooCommerce
3. Testez la connectivité API BIHR
4. Vérifiez que tous les catalogues sont téléchargés

### Les images sont-elles optimisées ?

Les images sont téléchargées en taille originale. Utilisez un plugin d'optimisation d'images WordPress pour les compresser automatiquement.

### Puis-je personnaliser les prix ?

Oui. Modifiez la fonction `import_to_woocommerce()` pour ajouter une marge ou calculer les prix TTC.

### Comment ajouter de nouveaux filtres ?

1. Ajoutez le champ dans `products-page.php`
2. Modifiez `get_products()` dans `class-bihr-product-sync.php`
3. Ajoutez la logique SQL dans la clause WHERE

### Le plugin est-il compatible avec WPML ?

Le plugin n'est pas testé avec WPML, mais devrait fonctionner. Les descriptions IA sont en français par défaut.

### Peut-on automatiser la synchronisation ?

Oui. Utilisez WP-Cron ou un cron système pour appeler les actions :
```php
do_action('bihrwi_download_catalogs');
do_action('bihrwi_merge_catalogs');
```

### Comment désactiver la synchronisation automatique des commandes ?

Rendez-vous dans `Bihr Import > Commandes` et décochez "Synchronisation automatique". Les commandes ne seront plus envoyées à BIHR automatiquement.

### Que se passe-t-il si une commande contient des produits non-BIHR ?

Seuls les produits avec un code BIHR (meta `_bihr_product_code`) sont envoyés. Si aucun produit BIHR n'est trouvé, la commande n'est pas synchronisée.

### Comment retrouver l'ID de commande BIHR ?

L'ID est stocké dans les notes de commande WooCommerce et dans le meta `_bihr_order_id`. Il est également visible dans la page "Commandes" du plugin.

## 🛠️ Support

### Logs et débogage

Activez le mode debug WordPress :

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Les logs du plugin sont accessibles dans `Bihr Import > Logs`.

### Rapporter un bug

Ouvrez une issue sur GitHub avec :
- Version WordPress et WooCommerce
- Version PHP
- Message d'erreur complet
- Logs du plugin

### Contribuer

Les contributions sont les bienvenues ! 

1. Fork le projet
2. Créez une branche (`git checkout -b feature/amelioration`)
3. Committez (`git commit -m 'Ajout fonctionnalité'`)
4. Pushez (`git push origin feature/amelioration`)
5. Ouvrez une Pull Request

## 📝 Licence

Ce plugin est un projet privé développé pour l'intégration BIHR-WooCommerce.

## 👨‍💻 Auteur & Contact

**Développé par:** DrComputer60290  
**Représentant:** M. Albert Benjamin  
**Entreprise:** DrComputer60290  

### 📞 Coordonnées

**Adresse:**  
81 rue René Cassin  
60290 Laigneville  
France

**Contact:**  
📧 Email: [webmaster@drcomputer60290.fr](mailto:webmaster@drcomputer60290.fr)  
📱 Téléphone: 07 86 99 08 35  
🌐 Site web: [drcomputer60290.fr](https://drcomputer60290.fr)

## 🔄 Changelog

### Version 1.4.0 (2025-12-14)

**Nouveautés majeures:**
- ✨ **Synchronisation automatique des stocks** avec planification WP-Cron
  - 4 fréquences disponibles (horaire, 2×/jour, quotidien, hebdomadaire)
  - Synchronisation manuelle avec progression en temps réel
  - Statistiques détaillées de chaque synchronisation
- ⚡ **Ultra-optimisation des imports CSV** (batch size 2000 lignes)
  - TECNIUM (488K lignes) : 6-8 minutes (vs 80 min initialement)
  - 10× plus rapide que la version initiale
  - Insertions SQL en masse pour performances maximales
- 🎨 **Sous-barres de progression** par marque lors de l'import groupé
  - Visualisation de chaque marque en temps réel
  - Barre globale + barres individuelles
  - Rechargement automatique après import
- 🏍️ **Filtre véhicule frontend** pour clients
  - Shortcode [bihr_vehicle_filter]
  - Widget WordPress
  - Cascade : Fabricant → Modèle → Année
  - Affichage compatibilité sur pages produits
- 🔄 **Rechargement automatique** après imports
  - Import véhicules : auto-reload après 2 sec
  - Import groupé marques : auto-reload après 3 sec
  - Meilleure UX utilisateur

**Améliorations:**
- 📊 Interface remaniée de l'import groupé
- 🎯 Logs plus détaillés avec progression par marque
- 📈 Statistiques enrichies de synchronisation
- 🔐 Sécurité renforcée (nonces, sanitization)

**Performance:**
- Vitesse d'import : 1000 lignes/sec (vs 98 initialement)
- Réduction requêtes SQL : 99.95%
- Réduction requêtes AJAX : 95%

### Version 1.0.0 (2024-12-09)

**Ajouts:**
- ✅ Authentification OAuth BIHR
- ✅ Téléchargement automatique des catalogues
- ✅ Fusion intelligente des 6 catalogues
- ✅ Filtres avancés (catégorie, prix, stock, recherche)
- ✅ Tri multi-critères
- ✅ Import multi-produits avec progression
- ✅ Enrichissement IA via OpenAI GPT-4
- ✅ Gestion automatique des images
- ✅ Mapping des catégories
- ✅ Système de logs complet
- ✅ Interface responsive et intuitive
- ✅ **Synchronisation automatique des commandes vers l'API BIHR**
- ✅ **Page de configuration des paramètres de commandes**
- ✅ **Formatage intelligent des adresses et téléphones**

**Optimisations:**
- Priorité `longdescription1` pour les noms
- Évitement des doublons d'images
- Import séquentiel avec délai anti-surcharge
- Détection automatique du séparateur CSV
- Normalisation des headers CSV
- Vérification des produits BIHR avant synchronisation

---

**🚀 Pour commencer, rendez-vous dans `Bihr Import > Authentification` !**

**📚 Documentation complète disponible dans les fichiers :**
- `AUTO_STOCK_SYNC_GUIDE.md` : Guide synchronisation automatique
- `ULTRA_OPTIMIZATION_2000.md` : Optimisations de performance
- `VEHICLE_FILTER_GUIDE.md` : Filtre véhicule frontend
- `DEPLOYMENT_GUIDE.md` : Guide de déploiement
- `TESTING_GUIDE.md` : Guide de tests
