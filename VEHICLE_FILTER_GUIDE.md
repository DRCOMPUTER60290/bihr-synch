# 🚗 Guide d'utilisation - Filtre de compatibilité véhicule

## Vue d'ensemble

Le filtre de compatibilité véhicule permet aux clients de trouver facilement les pièces compatibles avec leur moto/véhicule.

---

## 🎯 Fonctionnalités

### Pour les clients (Frontend)
1. **Sélection en cascade**: Fabricant → Modèle → Année
2. **Filtrage intelligent**: Affichage uniquement des pièces compatibles
3. **Affichage sur page produit**: Liste des véhicules compatibles
4. **Interface responsive**: Fonctionne sur mobile, tablette, desktop

### Pour les administrateurs
1. **Widget WordPress**: Ajout facile dans les sidebars
2. **Shortcode**: Insertion dans n'importe quelle page/article
3. **Session persistante**: Le véhicule sélectionné reste en mémoire

---

## 📦 Installation

### Déjà fait automatiquement
✅ Fichiers créés et chargés
✅ Hooks AJAX enregistrés
✅ Assets CSS/JS enqueueués
✅ Widget enregistré

### Aucune configuration requise!

---

## 🎨 Utilisation

### Méthode 1: Shortcode (recommandé)

Insérez dans n'importe quelle page ou article:

```
[bihr_vehicle_filter]
```

#### Options disponibles:
```
[bihr_vehicle_filter title="Trouvez vos pièces" show_button="yes"]
```

**Paramètres:**
- `title`: Titre du filtre (défaut: "Trouvez vos pièces")
- `show_button`: Afficher le bouton de recherche ("yes" ou "no")

#### Exemples:
```
[bihr_vehicle_filter title="Recherche par véhicule"]
[bihr_vehicle_filter show_button="no"]
[bihr_vehicle_filter title="Quelle pièce cherchez-vous ?" show_button="yes"]
```

---

### Méthode 2: Widget

1. Aller dans **Apparence → Widgets**
2. Chercher **"🏍️ BIHR - Filtre Véhicule"**
3. Glisser-déposer dans une sidebar
4. Configurer le titre
5. Sauvegarder

**Emplacements suggérés:**
- Sidebar shop (page boutique)
- Sidebar produits
- Header/Footer
- Zone personnalisée

---

## 🎯 Pages recommandées pour le filtre

### Page boutique (Shop)
Créez une page "Trouvez vos pièces" avec le shortcode:
```
[bihr_vehicle_filter title="Trouvez les pièces pour votre moto"]
```

### Sidebar produits
Ajoutez le widget dans la sidebar des pages produits pour que les clients puissent filtrer pendant qu'ils naviguent.

### Page d'accueil
Section hero avec le filtre pour attirer l'attention:
```html
<section class="hero">
  <h1>Trouvez la pièce parfaite pour votre moto</h1>
  [bihr_vehicle_filter]
</section>
```

---

## 🎨 Personnalisation CSS

### Variables CSS disponibles

```css
/* Couleurs principales */
.bihr-vehicle-filter-wrapper {
  --primary-color: #0073aa;
  --border-color: #ddd;
  --background: #fff;
}

/* Tailles */
.bihr-filter-row {
  --gap: 15px;
  --min-width: 200px;
}
```

### Exemples de personnalisation

#### Changer les couleurs
```css
.bihr-filter-button {
  background-color: #ff6b00 !important; /* Orange */
}

.bihr-filter-button:hover {
  background-color: #e55a00 !important;
}
```

#### Modifier la largeur
```css
.bihr-vehicle-filter-wrapper {
  max-width: 800px;
  margin: 0 auto;
}
```

#### Style compact
```css
.bihr-filter-row {
  gap: 10px;
}

.bihr-filter-field select {
  padding: 8px 10px;
  font-size: 13px;
}
```

---

## 🔧 Configuration avancée

### Ajouter le filtre dans le header

**Fichier: `header.php` de votre thème**
```php
<?php if ( function_exists( 'do_shortcode' ) ) : ?>
  <div class="header-vehicle-filter">
    <?php echo do_shortcode( '[bihr_vehicle_filter]' ); ?>
  </div>
<?php endif; ?>
```

### Ajouter dans une page builder (Elementor, Divi, etc.)

1. Ajouter un bloc **Shortcode** ou **HTML**
2. Coller: `[bihr_vehicle_filter]`
3. Personnaliser via CSS custom du builder

---

## 📱 Responsive

Le filtre est **entièrement responsive**:

- **Desktop**: 3 colonnes
- **Tablette**: 2 colonnes
- **Mobile**: 1 colonne verticale

Tous les boutons s'adaptent automatiquement.

---

## 🎯 Flux utilisateur

### Étape 1: Sélection du fabricant
```
Client sélectionne: "Honda"
↓
AJAX charge les modèles Honda
```

### Étape 2: Sélection du modèle
```
Client sélectionne: "CBR 600 RR"
↓
AJAX charge les années disponibles
```

### Étape 3: Sélection de l'année/version
```
Client sélectionne: "2007 - PC37"
↓
Véhicule complet identifié
```

### Étape 4: Recherche
```
Client clique "Voir les pièces compatibles"
↓
AJAX filtre les produits
↓
Affichage des résultats (grille de produits)
```

---

## 🎨 Affichage sur page produit

### Automatique
Sur chaque page produit, si le produit a des compatibilités, affichage automatique:

```
🏍️ Véhicules compatibles

Honda CBR 600 RR (2007) PC37
Kawasaki ZX-6R (2009) 
Yamaha YZF-R6 (2008)
...

+ 15 autres véhicules compatibles
```

### Position
- **Par défaut**: Après le prix (priority 25)
- **Modifier**: Hook `woocommerce_single_product_summary`

#### Changer la position
```php
// Dans functions.php de votre thème
remove_action( 'woocommerce_single_product_summary', 
  array( 'BihrWI_Vehicle_Filter', 'display_product_compatibility' ), 25 );

add_action( 'woocommerce_after_single_product_summary', 
  array( new BihrWI_Vehicle_Filter(), 'display_product_compatibility' ), 10 );
```

---

## 🎁 Templates personnalisés

### Template filtre personnalisé

Créez: `wp-content/themes/votre-theme/bihr/vehicle-filter.php`

```php
<div class="custom-vehicle-filter">
  <h2>Ma recherche personnalisée</h2>
  <?php echo do_shortcode( '[bihr_vehicle_filter show_button="no"]' ); ?>
  <button onclick="myCustomSearch()">Rechercher</button>
</div>
```

---

## 🚀 Performances

### Cache des fabricants
Les fabricants sont chargés **une seule fois** au load de la page.

### AJAX optimisé
- Requêtes minimales (3 max: fabricants, modèles, années)
- Données mises en cache côté client

### Base de données
- Index sur `vehicle_code`, `manufacturer_code`, `part_number`
- Requêtes optimisées avec DISTINCT

---

## 🐛 Dépannage

### Le filtre ne s'affiche pas
**Vérifier:**
1. Shortcode correct: `[bihr_vehicle_filter]`
2. Plugin activé
3. WooCommerce actif
4. Données de compatibilité importées

### Les fabricants ne se chargent pas
**Vérifier:**
1. Console navigateur (F12) → Erreurs JS?
2. Données dans table `wp_bihr_vehicles`
3. AJAX fonctionne: `console.log(bihrVehicleFilter)`

### Produits ne s'affichent pas
**Vérifier:**
1. SKU des produits WooCommerce correspondent aux `part_number`
2. Données dans table `wp_bihr_vehicle_compatibility`
3. Console: erreurs AJAX?

### Style cassé
**Solutions:**
```css
/* Forcer le CSS si conflit avec le thème */
.bihr-vehicle-filter-wrapper * {
  box-sizing: border-box !important;
}
```

---

## 📊 Statistiques

### Vérifier les données
```sql
-- Nombre de véhicules
SELECT COUNT(*) FROM wp_bihr_vehicles;

-- Nombre de compatibilités
SELECT COUNT(*) FROM wp_bihr_vehicle_compatibility;

-- Produits avec compatibilités
SELECT COUNT(DISTINCT part_number) 
FROM wp_bihr_vehicle_compatibility;
```

---

## ✅ Checklist mise en ligne

- [ ] Données véhicules importées (VehiclesList.csv)
- [ ] Données compatibilités importées (fichiers par marque)
- [ ] Shortcode testé sur une page
- [ ] Widget ajouté dans sidebar
- [ ] Test sélection fabricant → modèle → année
- [ ] Test recherche de produits
- [ ] Vérifier affichage sur page produit
- [ ] Test responsive (mobile/tablette)
- [ ] CSS personnalisé si nécessaire

---

## 🎓 Exemples concrets

### E-commerce moto
```
Page: "Trouvez vos pièces"
Shortcode: [bihr_vehicle_filter title="Quelle pièce pour votre moto ?"]
+ Widget sidebar pour navigation
```

### Site vitrine + boutique
```
Homepage: Hero avec filtre géant
Shop: Widget sidebar discret
Produit: Compatibilités automatiques
```

### Multi-marques
```
Page par marque:
- Honda → [bihr_vehicle_filter title="Pièces Honda"]
- Yamaha → [bihr_vehicle_filter title="Pièces Yamaha"]
```

---

**Besoin d'aide?** Consultez les logs WordPress ou la console navigateur (F12).
