# 🔧 Correction : Gestion du statut "Cart" dans Order/Data

## 📋 Problème

Lorsqu'une commande est synchronisée avec l'API BIHR et que le statut reste en **"Cart"** (panier), l'endpoint `/Order/Data` n'est pas disponible. Le plugin affichait simplement une erreur générique sans expliquer la raison réelle du blocage.

**Logs observés :**
```
[2025-12-17 09:58:25] === RÉPONSE GenerationStatus ===
[2025-12-17 09:58:25]   {
[2025-12-17 09:58:25]       "OrderUrl": "https://www.mybihr.com/fr/fr/my-account/saved-carts/000013872145",
[2025-12-17 09:58:25]       "OrderQueuePosition": 0,
[2025-12-17 09:58:25]       "RequestStatus": "Cart"
[2025-12-17 09:58:25]   }
```

## ✅ Solution Apportée

### 1. **Amélioration du client API** ([includes/class-bihr-api-client.php](includes/class-bihr-api-client.php#L509-L600))

La méthode `get_order_data()` a été améliorée pour :
- Détecter le statut **"Cart"** via l'endpoint `GenerationStatus`
- Retourner un objet d'erreur structuré avec les informations utiles :
  - `error: true` - Indique qu'il s'agit d'une erreur explicite
  - `message` - Message d'erreur explicite
  - `request_status` - Statut actuel de la commande ("Cart", "Order", etc.)
  - `order_url` - URL de la commande sur BIHR
  - `ticket_id` - TicketId utilisé

**Exemple de réponse pour une commande en statut "Cart" :**
```json
{
  "error": true,
  "message": "Order/Data indisponible tant que BIHR n'a pas généré une commande (statut=Cart).",
  "request_status": "Cart",
  "order_url": "https://www.mybihr.com/fr/fr/my-account/saved-carts/000013872145",
  "ticket_id": "a172b4ecb99b42208a62501a38be56b7"
}
```

### 2. **Mise à jour du contrôleur AJAX** ([admin/class-bihr-admin.php](admin/class-bihr-admin.php#L142-L170))

La fonction `ajax_get_order_data()` a été simplifiée et améliorée pour :
- Vérifier si la réponse contient un drapeau `error: true`
- Retourner une erreur JSON structurée avec les informations détaillées
- Laisser l'affichage des détails au JavaScript frontend

### 3. **Interface JavaScript** ([admin/views/orders-settings-page.php](admin/views/orders-settings-page.php#L370-L440))

Le JavaScript frontend affiche déjà les informations supplémentaires :
```javascript
if (payload.request_status) extra += '\nrequest_status: ' + payload.request_status;
if (payload.order_url) extra += '\norder_url: ' + payload.order_url;
if (payload.ticket_id) extra += '\nticket_id: ' + payload.ticket_id;
```

## 🎯 Comportement Amélioré

**Avant :**
```
❌ Erreur: Impossible de récupérer Order/Data côté BIHR (voir logs).
```

**Après :**
```
❌ Erreur: Order/Data indisponible tant que BIHR n'a pas généré une commande (statut=Cart).
request_status: Cart
order_url: https://www.mybihr.com/fr/fr/my-account/saved-carts/000013872145
ticket_id: a172b4ecb99b42208a62501a38be56b7
```

## 📌 Cas d'usage

### ✅ Quand ça marche

Lorsque le statut de la commande passe à `"Order"` ou un autre statut final, l'endpoint `/Order/Data` devient disponible et retourne les données complètes de la commande.

### ⏳ Quand c'est bloqué

Tant que la commande est en statut `"Cart"`, l'utilisateur peut :
1. Consulter l'URL BIHR fournie pour suivre manuellement
2. Vérifier les logs pour plus de détails
3. Attendre que BIHR finisse le traitement de la commande
4. Cliquer sur "Actualiser" pour vérifier l'évolution du statut

## 🔍 Tests Recommandés

1. **Test avec statut "Cart" :**
   - Créer une commande WooCommerce
   - Vérifier le log du plugin
   - Cliquer sur "Voir" dans Order/Data
   - Vérifier le message d'erreur explicite

2. **Test avec statut "Order" :**
   - Attendre que BIHR change le statut
   - Actualiser la page
   - Vérifier que les données Order/Data s'affichent correctement

3. **Test du fallback TicketId :**
   - Vérifier que les instances anciennes acceptent toujours `TicketId` en paramètre

## 📊 Impact

- ✅ Meilleure UX : Message d'erreur plus explicite
- ✅ Meilleur debugging : Informations détaillées sur l'état réel
- ✅ Backward compatible : Ne casse pas les intégrations existantes
- ✅ Logs enrichis : Traçabilité améliorée
