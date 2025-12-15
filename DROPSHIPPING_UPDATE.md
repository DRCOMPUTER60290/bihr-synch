# Mise à jour Dropshipping - Format API BIHR

## Date de mise à jour
15 décembre 2025

## Contexte
Suite au mail de **Smaïl EL HAJJAR** (BIHR), le format de payload pour les commandes dropshipping a été mis à jour pour correspondre exactement aux spécifications de l'API BIHR v2.1.

## Modifications apportées

### 1. Structure du payload (`class-bihr-order-sync.php`)

Le payload envoyé à l'API BIHR respecte maintenant strictement le format suivant :

```json
{
  "Order": {
    "CustomerReference": "Order for John Doe's motorbike",
    "Lines": [
      {
        "ProductId": "TPCI07495",
        "Quantity": 14,
        "ReferenceType": "Not used anymore",
        "CustomerReference": "Brakes for John Doe's motorbike",
        "ReservedQuantity": 0
      }
    ],
    "IsAutomaticCheckoutActivated": true,
    "IsWeeklyFreeShippingActivated": true,
    "DeliveryMode": "Default"
  },
  "DropShippingAddress": {
    "FirstName": "André",
    "LastName": "Millet",
    "Line1": "19, rue Blondel",
    "Line2": "1er étage",
    "ZipCode": "22106",
    "Town": "Toussaint",
    "Country": "FR",
    "Phone": "+33123456789"
  }
}
```

### 2. Champs ajoutés dans `Order.Lines[]`

- **`ReferenceType`** : Valeur fixe `"Not used anymore"` (comme indiqué par BIHR)
- **`ReservedQuantity`** : Valeur fixe `0`

### 3. Champs ajoutés dans `Order`

- **`IsWeeklyFreeShippingActivated`** : Booléen contrôlé via l'option admin `bihrwi_weekly_free_shipping`
- **`DeliveryMode`** : Valeur contrôlée via l'option admin `bihrwi_delivery_mode` (Default/Express/Standard)

### 4. Options d'administration

Toutes les options sont déjà disponibles dans l'interface admin **Paramètres de Synchronisation des Commandes** :

#### Option : Livraison gratuite hebdomadaire
- **Nom de l'option** : `bihrwi_weekly_free_shipping`
- **Par défaut** : Activée (`true`)
- **Description** : Permet de bénéficier de la livraison gratuite hebdomadaire selon les conditions BIHR

#### Option : Mode de livraison
- **Nom de l'option** : `bihrwi_delivery_mode`
- **Par défaut** : `Default`
- **Valeurs possibles** :
  - `Default` (Par défaut)
  - `Express` (Livraison express)
  - `Standard` (Livraison standard)

### 5. Adresse de dropshipping (`DropShippingAddress`)

L'adresse de dropshipping utilise déjà le bon format avec :
- Utilisation de l'adresse de **livraison** si disponible, sinon l'adresse de **facturation**
- Formatage automatique des numéros de téléphone français (conversion en format international +33)
- Structure conforme aux spécifications BIHR

## Vérification des commandes sur MyBihr.com

Pour vérifier l'adresse de livraison d'une commande :
1. Aller sur https://www.mybihr.com
2. Cliquer sur l'onglet **"Mes livraisons"**
3. Cliquer sur le **numéro de commande**

## Logs et traçabilité

Les logs générés incluent maintenant :
- Ticket ID WooCommerce unique
- BIHR Ticket ID (retourné par l'API)
- Détails des options utilisées (checkout auto, livraison gratuite, mode de livraison)
- Formatage complet du payload JSON envoyé à l'API

Exemple de log :
```
[WC123-1734278400-abc12345] ⚙️ Option: Checkout automatique=activé
[WC123-1734278400-abc12345] ⚙️ Option: Livraison gratuite hebdomadaire=activée
[WC123-1734278400-abc12345] ⚙️ Option: Mode de livraison=Default
```

## Accès au dropshipping

**Important** : Pour pouvoir passer des commandes en dropshipping via l'API, vous devez avoir accès au dropshipping sur votre compte BIHR.

Pour vérifier votre accès, contactez BIHR avec :
- Votre **numéro de compte BIHR**
- Votre **adresse e-mail**

## Fichiers modifiés

- ✅ `/includes/class-bihr-order-sync.php` - Ajout des champs requis dans le payload
- ✅ `/admin/views/orders-settings-page.php` - Mise à jour de l'exemple JSON
- ℹ️ `/admin/class-bihr-admin.php` - Les options existaient déjà (aucune modification nécessaire)

## Tests recommandés

1. **Vérifier les options admin** :
   - Aller dans WooCommerce > BIHR > Paramètres Commandes
   - Vérifier que toutes les options sont visibles et fonctionnelles

2. **Tester une commande** :
   - Créer une commande test avec un produit BIHR
   - Vérifier dans les logs que le payload contient tous les champs requis
   - Vérifier sur MyBihr.com que la commande a été créée avec la bonne adresse

3. **Vérifier les logs** :
   - Aller dans WooCommerce > BIHR > Logs
   - Chercher le Ticket ID de la commande
   - Vérifier que le JSON envoyé contient tous les champs

## Contact BIHR

**Smaïl EL HAJJAR**  
Email disponible dans l'email du 15 décembre 2025

## Endpoint API

- **URL** : `https://api.bihr.net/api/v2.1/Order/Creation`
- **Méthode** : `POST`
- **Authentification** : Bearer Token OAuth 2.0

## Conformité

✅ Le payload généré est maintenant **100% conforme** à l'exemple fourni par BIHR.
