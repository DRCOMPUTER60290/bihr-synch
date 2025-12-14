# 🐛 TROUBLESHOOTING - Filtre Véhicule Vide

## 🔍 Symptôme

Les dropdowns du filtre véhicule (Fabricant, Modèle, Année) sont vides malgré:
- ✅ 63 669 véhicules importés
- ✅ 610 797 compatibilités importées
- ✅ Statistiques affichées correctement

## 📋 Diagnostic en 5 étapes

### Étape 1: Script de Debug PHP

**Accédez à:** `https://votresite.com/wp-content/plugins/BIHR-SYNCH/debug-vehicle-filter.php`

Ce script vérifie automatiquement:
- ✓ Existence des tables
- ✓ Nombre de véhicules et compatibilités
- ✓ Requête SQL des fabricants
- ✓ Hooks AJAX enregistrés
- ✓ Classe chargée

**Résultats attendus:**
```
1. Tables de base de données
   ✅ Table véhicules (wp_bihr_vehicles): Existe
   ✅ Table compatibilités (wp_bihr_vehicle_compatibility): Existe

2. Nombre de véhicules
   Total véhicules: 63669

3. Requête Fabricants
   Nombre de fabricants trouvés: 50+
   
   Liste:
   - YAMAHA
   - HONDA
   - SUZUKI
   - etc.
```

**Si fabricants = 0:**
→ Problème: Les colonnes `manufacturer_name` ou `manufacturer_code` sont NULL/vides
→ Solution: Voir [Étape 4](#étape-4-vérifier-les-données-csv)

### Étape 2: Console Navigateur (F12)

1. **Ouvrir la page avec le filtre véhicule**
2. **Appuyer sur F12** → Onglet "Console"
3. **Recharger la page** (Ctrl+F5)

**Messages attendus:**
```javascript
🚗 BIHR Vehicle Filter loaded
AJAX URL: https://votresite.com/wp-admin/admin-ajax.php
Nonce: Present
📡 Chargement des fabricants...
→ Envoi requête fabricants
← Réponse fabricants: {success: true, data: {...}}
✅ 50 fabricants trouvés
```

**Erreurs possibles:**

#### ❌ "Nonce: MISSING"
**Cause:** Le script `bihr-vehicle-filter.js` ne reçoit pas les paramètres
**Solution:**
```php
// Vérifier dans includes/class-bihr-vehicle-filter.php
wp_localize_script( 'bihr-vehicle-filter', 'bihrVehicleFilter', array(
    'ajaxurl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'bihr_vehicle_filter_nonce' ),
) );
```

#### ❌ "Erreur AJAX fabricants: 400/403/500"
**Cause:** Hook AJAX non enregistré ou nonce invalide
**Solution:** Vérifier que la classe `BihrWI_Vehicle_Filter` est bien instanciée dans le fichier principal

#### ❌ "Réponse invalide ou pas de fabricants"
**Cause:** La requête SQL ne retourne rien
**Solution:** Voir Étape 3

### Étape 3: Test AJAX Manuel

Dans la console du navigateur (F12), coller et exécuter:

```javascript
jQuery.ajax({
    url: '/wp-admin/admin-ajax.php',
    method: 'POST',
    data: {
        action: 'bihr_get_manufacturers',
        nonce: bihrVehicleFilter.nonce
    },
    success: function(response) {
        console.log('✅ Réponse:', response);
        if (response.success) {
            console.log('Fabricants:', response.data.manufacturers);
        }
    },
    error: function(xhr, status, error) {
        console.error('❌ Erreur:', error);
        console.log('Response:', xhr.responseText);
    }
});
```

**Résultats possibles:**

#### ✅ Success avec fabricants
```json
{
  "success": true,
  "data": {
    "manufacturers": [
      {
        "manufacturer_code": "YAMAHA",
        "manufacturer_name": "YAMAHA"
      },
      ...
    ]
  }
}
```
→ **Problème:** Le JavaScript ne met pas à jour les dropdowns
→ **Solution:** Vérifier les sélecteurs jQuery (`#bihr-manufacturer`)

#### ❌ Tableau vide
```json
{
  "success": true,
  "data": {
    "manufacturers": []
  }
}
```
→ **Problème:** Requête SQL ne retourne rien
→ **Solution:** Voir Étape 4

#### ❌ Erreur 400/403
```
{"success":false,"data":{"message":"Nonce invalide"}}
```
→ **Problème:** Vérification nonce échoue
→ **Solution:** Désactiver temporairement la vérification nonce pour tester

### Étape 4: Vérifier les Données CSV

**Problème fréquent:** Les colonnes `manufacturer_name` et `manufacturer_code` sont vides dans la table

**Vérification SQL directe:**

```sql
-- Compter les véhicules avec fabricant
SELECT COUNT(*) 
FROM wp_bihr_vehicles 
WHERE manufacturer_name IS NOT NULL 
AND manufacturer_name != '';

-- Voir un exemple de ligne
SELECT * 
FROM wp_bihr_vehicles 
LIMIT 1;
```

**Si COUNT = 0:**
→ **Cause:** Le CSV `VehiclesList.csv` ne contient pas les colonnes correctes ou le mapping est incorrect

**Solution:** Vérifier le mapping d'import dans `class-bihr-vehicle-compatibility.php`:

```php
// Ligne ~179
$vehicles[] = array(
    'vehicle_code'            => $row[0],
    'version_code'            => $row[1],
    'commercial_model_code'   => $row[2],
    'manufacturer_code'       => $row[3],  // ← Colonne 4 du CSV
    'vehicle_year'            => $row[4],
    'version_name'            => $row[5],
    'commercial_model_name'   => $row[6],
    'manufacturer_name'       => $row[7],  // ← Colonne 8 du CSV
    ...
);
```

**Vérifier le fichier CSV:**
- Ouvrir `VehiclesList.csv`
- Colonne 4 (index 3) doit contenir les codes fabricant (ex: "YAMAHA", "HONDA")
- Colonne 8 (index 7) doit contenir les noms fabricant

**Si le mapping est incorrect:**
1. Corriger les indices dans `import_vehicles_list()`
2. Réimporter: Page Compatibilité → "Importer la liste des véhicules"

### Étape 5: Réinitialisation Complète

Si rien ne fonctionne, réinitialiser tout:

```php
// 1. Vider les tables
TRUNCATE TABLE wp_bihr_vehicles;
TRUNCATE TABLE wp_bihr_vehicle_compatibility;

// 2. Recréer les tables
Page: WooCommerce > BIHR Synch > Compatibilité
Cliquer: "🔧 Créer/Recréer les tables"

// 3. Réimporter
Upload: VehiclesList.zip
Cliquer: "📥 Importer les véhicules"

// 4. Upload compatibilités
Upload: LinksList.zip
Cliquer: "🚀 Importer toutes les marques"
```

## 🔧 Solutions Rapides

### Solution 1: Désactiver temporairement la vérification nonce

**Fichier:** `includes/class-bihr-vehicle-filter.php`

```php
public function ajax_get_manufacturers() {
    // check_ajax_referer( 'bihr_vehicle_filter_nonce', 'nonce' ); // ← Commenter temporairement

    global $wpdb;
    $manufacturers = $wpdb->get_results(...);
    wp_send_json_success( array( 'manufacturers' => $manufacturers ) );
}
```

**⚠️ Important:** Remettre la vérification après le test!

### Solution 2: Forcer le rechargement des fabricants

Ajouter ce code dans le footer du thème pour tester:

```php
add_action( 'wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        setTimeout(function() {
            console.log('🔄 Rechargement forcé des fabricants...');
            $('#bihr-manufacturer').trigger('change');
        }, 2000);
    });
    </script>
    <?php
} );
```

### Solution 3: Vérifier que le widget/shortcode est correctement utilisé

**Shortcode:**
```
[bihr_vehicle_filter title="Trouvez vos pièces"]
```

**Widget:**
- Apparence → Widgets
- Ajouter "BIHR - Filtre Véhicule"
- Vérifier qu'il est dans une sidebar active

## 📊 Checklist Complète

- [ ] Script `debug-vehicle-filter.php` exécuté
- [ ] Tables existent et contiennent des données
- [ ] Fabricants trouvés dans la requête SQL
- [ ] Console browser affiche les logs
- [ ] AJAX retourne des données
- [ ] Nonce valide
- [ ] Hooks enregistrés
- [ ] Sélecteurs jQuery corrects
- [ ] JavaScript chargé sans erreur
- [ ] Shortcode/widget bien placé

## 🎯 Causes les Plus Fréquentes

**Top 3:**

1. **Colonnes manufacturer_name/manufacturer_code NULL** (70% des cas)
   → Réimporter VehiclesList.csv

2. **Hooks AJAX pas enregistrés** (20% des cas)
   → Vérifier que `BihrWI_Vehicle_Filter` est instancié dans le fichier principal

3. **JavaScript ne se charge pas** (10% des cas)
   → Vérifier la console pour erreurs JavaScript

## 📞 Support

Si après toutes ces étapes le problème persiste:

1. **Capturer les logs console** (F12 → Console → Screenshot)
2. **Exporter le résultat du script debug** (copy/paste)
3. **Vérifier la première ligne du CSV** `VehiclesList.csv`
4. **Contacter:** webmaster@drcomputer60290.fr

---

**Version:** 1.4.0  
**Date:** 14/12/2025  
**Auteur:** DrComputer60290 - Albert Benjamin
