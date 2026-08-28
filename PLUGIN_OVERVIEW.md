# BIHR Synch — Résumé complet du plugin

**Nom :** BIHR Synch (bihr-woocommerce-importer)  
**Version :** 2.0.0  
**Auteur :** DrComputer60290 – Albert Benjamin  
**Licence :** GPLv2 or later  
**Modèle de distribution :** Premium via Freemius (essai 7 jours sans CB)

---

## Objectif

BIHR Synch est un plugin WordPress/WooCommerce qui **synchronise automatiquement le catalogue BIHR** (équipementier moto) avec une boutique en ligne. Il couvre tout le cycle de vie produit : téléchargement des catalogues, enrichissement des fiches produits, import WooCommerce, gestion du stock et transmission des commandes vers l'API BIHR.

---

## Architecture technique

```
bihr-woocommerce-importer.php   ← Point d'entrée, initialisation Freemius
├── admin/
│   ├── class-bihr-tools.php    ← Classe principale : AJAX, import, API
│   ├── css/bihr-admin.css      ← Styles de l'interface admin
│   ├── js/bihr-progress.js     ← Barres de progression temps réel
│   └── views/                  ← Pages admin (dashboard, produits, logs…)
├── blocks/
│   ├── product-filter/         ← Bloc Gutenberg : filtre produit
│   └── vehicle-filter/         ← Bloc Gutenberg : filtre par véhicule
├── assets/admin/               ← JS filtres catégorie/AJAX
├── freemius/                   ← SDK licensing Freemius
└── config.php (ignoré git)     ← Tokens OAuth et clés API (local uniquement)
```

---

## Fonctionnalités principales

### 1. Authentification

| Mécanisme | Détail |
|-----------|--------|
| OAuth BIHR | Tokens chiffrés, rafraîchissement automatique |
| Clé OpenAI | Validation temps réel, test de connectivité |

**Page admin :** `Bihr Import > Authentification`

---

### 2. Téléchargement des catalogues BIHR

Le plugin récupère **6 types de catalogues** via l'API mybihr.com :

| Catalogue | Contenu |
|-----------|---------|
| References | Codes produits, noms, descriptions de base |
| ExtendedReferences (A–G) | Descriptions longues, catégories |
| Prices | Prix revendeur HT |
| Images | URLs des visuels produits |
| Inventory | Niveaux de stock |
| Attributes | Attributs techniques |

- Téléchargement ZIP automatique, extraction et fusion
- Barre de progression temps réel
- Logs détaillés de chaque opération

---

### 3. Catégories automatiques

Mapping automatique codes BIHR → noms WooCommerce :

```
A → RIDER GEAR
B → VEHICLE PARTS & ACCESSORIES
C → LIQUIDS & LUBRICANTS
D → TIRES & ACCESSORIES
E → TOOLING & WS
G → OTHER PRODUCTS & SERVICES
```

Création automatique dans WooCommerce sans doublon.

---

### 4. Filtrage avancé des produits

**Page admin :** `Bihr Import > Produits Bihr`

| Filtre | Description |
|--------|-------------|
| Recherche texte | Code, NewPartNumber, nom, description (insensible à la casse) |
| Stock | Tous / En stock / Rupture |
| Prix | Plage min–max (€ HT) |
| Catégorie | Dropdown dynamique |
| Tri | Par prix, nom, stock (asc/desc) |

---

### 5. Import WooCommerce

#### Tableau produits (colonnes)
Sélection · ID · Code (`NewPartNumber` prioritaire) · Nom · Prix HT · Stock · Catégorie · Image · Actions

#### Import multi-produits
- Cases à cocher + "Tout sélectionner / désélectionner"
- Compteur dynamique de sélection
- Barre de progression + journal par produit (en cours / succès avec WC ID / erreur)
- Import séquentiel (500 ms entre chaque) pour éviter la surcharge serveur
- Décochage automatique des produits importés avec succès

#### Hiérarchie des noms de produits
```
1. longdescription1   (priorité maximale)
2. furtherdescription
3. shortdescription
4. name               (fallback)
```

---

### 6. Enrichissement IA (OpenAI GPT-4)

S'active automatiquement si une clé OpenAI valide est présente.

| Modèle | Usage |
|--------|-------|
| GPT-4o | Produit avec image (vision + texte) |
| GPT-4o-mini | Produit sans image (texte uniquement) |

**Processus :**
1. Analyse du nom et de l'image
2. Génération d'une description courte (accroche 2–3 phrases) et longue (détaillée)
3. Injection dans `short_description` et `description` WooCommerce
4. Fallback automatique sur les données CSV si l'IA échoue

**Format de réponse attendu :**
```
[SHORT]Texte court...[/SHORT]
[LONG]Texte long...[/LONG]
```

---

### 7. Gestion des images

- URL de base : `https://api.mybihr.com`
- Formats supportés : JPG, PNG, GIF, WebP
- Détection du type MIME
- Dédoublonnage via meta `_bihr_image_source`
- Association automatique comme image principale WooCommerce

---

### 8. Gestion des stocks

- Source : catalogue **Inventory** (`StockLevel`)
- Mise à jour WooCommerce : `instock` si stock > 0, `outofstock` si stock = 0
- Synchronisation programmable (WP-Cron)

---

### 9. Synchronisation automatique des commandes

**Page admin :** `Bihr Import > Commandes`

Quand un client passe commande sur WooCommerce :

1. Détection de la commande
2. Vérification que les produits sont BIHR
3. Envoi via `POST /api/v2.1/Order/Creation`
4. Polling du statut via `GET /api/v2.1/Order/GenerationStatus?TicketId={id}`
5. Note WooCommerce avec l'ID commande BIHR

#### Statuts de retour API

| ResultCode | Signification |
|------------|---------------|
| Cart creation requested | Panier créé (validation manuelle sur mybihr.com) |
| Order creation requested | Commande validée automatiquement |

#### Métadonnées stockées sur la commande WooCommerce

| Meta Key | Description |
|----------|-------------|
| `_bihr_order_synced` | Synchronisation réussie |
| `_bihr_sync_ticket_id` | Ticket ID interne WooCommerce |
| `_bihr_api_ticket_id` | Ticket ID retourné par l'API BIHR |
| `_bihr_order_url` | URL du panier/commande mybihr.com |
| `_bihr_sync_date` | Date et heure de synchronisation |
| `_bihr_order_sync_failed` | Échec de synchronisation |
| `_bihr_sync_error` | Message d'erreur détaillé |

---

### 10. Outil Synchro SKU

**Page admin :** `Bihr Import > Synchro SKU`

Synchronise les SKU WooCommerce depuis la compatibilité véhicules.  
Ordre de correspondance : `NewPartNumber` → `_sku` actuel → `product_code`

---

### 11. Blocs Gutenberg

| Bloc | Fonctionnalité |
|------|---------------|
| `product-filter` | Filtre produits côté client |
| `vehicle-filter` | Filtre par véhicule (compatibilité produits) |

---

### 12. Logs et débogage

**Page admin :** `Bihr Import > Logs`

- Logs horodatés de toutes les opérations
- Événements : OAuth, téléchargement, fusion, import, enrichissement IA, erreurs
- Bouton "Vider les logs"
- Outil de diagnostic WP-Cron intégré

---

## Pages de l'interface admin

| Page | Slug | Rôle |
|------|------|------|
| Tableau de bord | `bihr-dashboard` | Vue d'ensemble |
| Authentification | `bihr-auth` | Clés API BIHR et OpenAI |
| Produits Bihr | `bihr-products` | Filtrage et import |
| Catégories | `bihr-categories` | Gestion du mapping |
| Commandes | `bihr-orders-settings` | Config sync commandes |
| Synchro SKU | `bihr-sku-sync` | Outil SKU/compatibilité |
| Marge | `bihr-margin` | Gestion des marges |
| Produits importés | `bihr-imported` | Suivi des imports |
| Logs | `bihr-logs` | Historique |
| Aide | `bihr-help` | Documentation |
| Tutoriel | `bihr-tutorial` | Guide d'utilisation |

---

## Optimisations de performance

- Import séquentiel avec délai (évite la surcharge serveur)
- Dédoublonnage images et produits
- Workflow asynchrone pour commandes et catalogues
- Ultra-optimisé : **10× plus rapide** que la version initiale
- Support WP-Cron pour les synchronisations programmées

---

## Informations de contact / Support

| | |
|-|---|
| **Auteur** | Albert Benjamin (DrComputer60290) |
| **Adresse** | 81 rue René Cassin, 60290 Laigneville, France |
| **Email** | webmaster@drcomputer60290.fr |
| **Téléphone** | 07 86 99 08 35 |
| **Site** | [drcomputer60290.fr](https://drcomputer60290.fr) |
| **Co-éditeur IA** | Claude (Anthropic) — assistance conception & développement |
