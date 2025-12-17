# 📚 Guide d'Utilisation - Affichage Order/Data

**Pour les clients débutants qui cherchent à comprendre leurs commandes BIHR**

---

## 🎯 C'est quoi Order/Data ?

Order/Data est une **fonctionnalité avancée** qui récupère les informations détaillées d'une commande directement depuis le système BIHR (fournisseur automobile).

Elle vous permet de voir :
- ✅ Tous les articles commandés
- ✅ Les prix (HT, TTC)
- ✅ L'adresse de livraison
- ✅ Le statut de la commande
- ✅ Les dates importantes

---

## 🚀 Comment l'utiliser ?

### Étape 1️⃣ : Localiser le formulaire

Connectez-vous à l'administration WooCommerce et allez dans :
```
📋 BIHR Importer → Commandes Synchronisées → Section "Order/Data"
```

Vous verrez un formulaire avec deux champs :
```
[ID de commande WooCommerce]  [🔍 Récupérer]
```

### Étape 2️⃣ : Trouver l'ID de votre commande

L'ID de commande est affiché :
- **Sur le tableau des commandes:** Colonne "ID"
  ```
  # 1234 | Client | Date | Statut
  ```
- **Dans la commande elle-même:** En haut à gauche
  ```
  ✏️ Commande n° 1234 du 28 décembre 2024
  ```

### Étape 3️⃣ : Entrer l'ID

Saisissez le numéro (sans le #) :
```
┌─────────────────────────────────┐
│ 1234                            │  ← Juste le numéro
└─────────────────────────────────┘
```

### Étape 4️⃣ : Cliquer sur "Récupérer"

Attendez quelques secondes... 🔄

```
⏳ Chargement depuis BIHR…
```

---

## ✅ Résultats Attendus

Si tout fonctionne bien, vous verrez :

### 1. Indicateur de Succès
```
✅ OK - 28 décembre 2024 14:30
```

### 2. Sections d'Information

#### 📋 **Statut**
```
Statut:  [🟠 Cart] ou [🟢 Order]
Référence: WC-12345
```

#### 👤 **Client**
```
Référence Client: BIHR-98765
```

#### 📍 **Adresse de Livraison**
```
┌──────────────────────────┐
│ Jean Dupont              │
│ 123 Rue de la Paix      │
│ 75000 Paris             │
│ 🌍 France               │
└──────────────────────────┘
```

#### 📦 **Articles Commandés**
```
┌────┬──────────────┬───────┬─────────────────┐
│ N° │ Référence    │ Qté   │ Description     │
├────┼──────────────┼───────┼─────────────────┤
│ 1  │ PROD-001     │  2    │ Pièce automobile│
│ 2  │ PROD-002     │  1    │ Accessoire      │
└────┴──────────────┴───────┴─────────────────┘
```

#### 💰 **Montants**
```
🏷️ Prix HT:     21,00 €
📊 TVA:         4,20 €
✅ Prix TTC:    25,20 €
```

#### 🚚 **Informations de Livraison**
```
📅 Créé:       28 décembre 2024 14:30
🚚 Expédié:    29 décembre 2024 09:15
⚖️ Poids:      2.5 kg
🚛 Transporteur: DHL
```

---

## ⚠️ Messages d'Erreur Courants

### 🔴 "Erreur: Aucune donnée"
**Cause:** L'ID de commande n'existe pas ou n'est pas synchronisé avec BIHR

**Solution:**
- ✅ Vérifiez que l'ID est correct
- ✅ Attendez quelques minutes (synchronisation)
- ✅ Contactez support si le problème persiste

### 🔴 "Erreur réseau"
**Cause:** Problème de connexion à BIHR

**Solution:**
- ✅ Vérifiez votre connexion Internet
- ✅ Attendez quelques secondes
- ✅ Cliquez de nouveau sur "Récupérer"

### ⚠️ "Status Cart"
**C'est normal !** Cela signifie que la commande est toujours dans le panier et n'a pas été finalisée.

**Solution:**
- ✅ Continuez vos achats
- ✅ Finalisez la commande

### ⚠️ "⚠️ Pas d'articles trouvés"
**Cause:** La commande existe mais n'a pas d'articles

**Solution:**
- ✅ Ajoutez des articles et commandez de nouveau

---

## 🔍 Section "JSON Brut"

C'est la **section pour développeurs** qui affiche les données complètes reçues de BIHR.

```json
{
  "ResultCode": "Order",
  "Code": "WC-12345",
  "CustomerReference": "BIHR-98765",
  "OrderLines": [
    {
      "ProductCode": "PROD-001",
      "ProductId": 123,
      "Quantity": 2,
      "ProductDescription": "Pièce automobile"
    }
  ],
  "DeliveryOrders": [
    {
      "ExclVatPrice": 21.00,
      "InclVatPrice": 25.20,
      "CreationDate": "2024-12-28T14:30:00",
      "TransporterId": "DHL"
    }
  ]
}
```

**Vous ne devez pas toucher à cette section !** Elle est là pour le débogage technique.

---

## 💾 Utiliser les Données

### Pour les Clients Débutants

✅ **Ce que vous pouvez faire:**
- 📸 Prendre une capture d'écran pour votre dossier
- 📋 Copier les informations pour communiquer avec support
- ✔️ Vérifier que tous les articles sont présents
- 💵 Vérifier les montants avant de payer

### Pour les Développeurs

✅ **Accès aux données brutes:**
- 📊 Données JSON complètes dans la section "Données JSON Complètes"
- 🔧 Console navigateur (F12) avec logs `[BIHR]`
- 📈 Debugging complet dans DevTools

---

## 🎓 Bonnes Pratiques

### ✅ À FAIRE
- ✅ Vérifier l'ID de commande avant de cliquer
- ✅ Attendre la fin du chargement
- ✅ Prendre note des informations importantes
- ✅ Contacter support avec l'ID si problème

### ❌ À NE PAS FAIRE
- ❌ Modifier le JSON brut
- ❌ Cliquer multiple fois rapidement
- ❌ Ignorer les avertissements (⚠️)
- ❌ Donner l'accès admin à des non-autorisés

---

## 🆘 Besoin d'Aide ?

### Informations à Fournir

Si vous contactez le support, incluez :

```
📋 Informations Utiles:

ID Commande: ________________
Heure de la tentative: ______
Navigateur: _________________
Message d'erreur exact: ______
Actions effectuées avant: ____
```

### Support

📧 **Email:** support@votresite.com  
🔗 **Site:** www.votresite.com/support  
💬 **Chat:** Disponible sur le site  

---

## 🔐 Sécurité & Confidentialité

- 🔒 Vos données sont chiffrées
- 🔑 Authentification requise
- 📋 Audit des accès enregistré
- 🌐 Connexion sécurisée (HTTPS)

**Vos informations de client et de commande ne sont jamais partagées publiquement.**

---

## 📊 Exemple Pas à Pas

### Scénario: Vérifier une Commande

```
1️⃣ Vous avez passé une commande le 28/12/2024
   Vous recevez un email: "Votre commande #1234"

2️⃣ Vous vous connectez à l'admin
   
3️⃣ Vous allez sur BIHR → Order/Data

4️⃣ Vous entrez: 1234

5️⃣ Vous cliquez "Récupérer"

6️⃣ Vous voyez:
   ✅ OK - Données reçues
   
7️⃣ Vous vérifiez:
   - 📦 Articles: 2 pièces × Quantité = Correct ✓
   - 💰 Montant: 25,20 € = Correct ✓
   - 📍 Adresse: 75000 Paris = Correct ✓
   
8️⃣ Vous êtes satisfait et vous continuez
```

---

## 💡 Astuces

**Astuce 1: Raccourci Clavier**
```
Entrez dans le champ "ID de commande"
Appuyez sur [ENTRÉE] pour valider directement
```

**Astuce 2: Cache**
```
Première lecture = 🌐 Depuis BIHR (lent)
Lectures suivantes = 💾 Cache (rapide)
Cliquez [🔄 Rafraîchir] pour forcer la relecture
```

**Astuce 3: Debugging**
```
Appuyez sur [F12] pour ouvrir la console
Cherchez les lignes commençant par [BIHR]
```

---

## 📚 Ressources Complémentaires

- 📖 [Guide WooCommerce](https://woocommerce.com/documentation/)
- 🔧 [Documentation BIHR API](https://api.bihr.net/documentation/)
- 💻 [Support Technique](https://votresite.com/support/)

---

**Dernière mise à jour:** 28 décembre 2024  
**Version:** 1.0  
**Statut:** ✅ Prêt pour utilisation  
