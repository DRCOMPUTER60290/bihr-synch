# 📊 Résumé des Corrections - Gestion du Statut "Cart"

## 🎯 Objectif
Améliorer l'expérience utilisateur en fournissant un message d'erreur explicite quand l'endpoint `/Order/Data` n'est pas disponible car la commande est en statut **"Cart"** (panier).

## 🔴 Problème Initial
Lorsqu'une commande est synchronisée avec l'API BIHR et que son statut reste en "Cart", le plugin affichait une erreur générique sans expliquer pourquoi les données n'étaient pas disponibles.

**Message affiché avant :**
```
❌ Erreur: Impossible de récupérer Order/Data côté BIHR (voir logs).
```

## ✅ Solution Mise en Place

### 1️⃣ Modification : [includes/class-bihr-api-client.php](includes/class-bihr-api-client.php#L509-L600)

**Fonction modifiée:** `get_order_data($ticket_id)`

**Changements :**
```php
// Quand /Order/Data retourne une erreur (HTTP != 200)
if ( $code !== 200 ) {
    // Nouveau: Récupérer le statut via GenerationStatus
    $status_data = $this->get_order_generation_status( $ticket_id );
    
    // Si le statut est "Cart", retourner une erreur structurée
    if ( $status_data && $status_data['request_status'] === 'Cart' ) {
        return array(
            'error'           => true,  // ← Drapeau d'erreur explicite
            'message'         => 'Order/Data indisponible...',
            'request_status'  => 'Cart',
            'order_url'       => $status_data['order_url'],
            'ticket_id'       => $ticket_id,
        );
    }
    // ... fallback pour TicketId et autres cas
}
```

**Avantages :**
- ✅ Détection automatique du statut "Cart"
- ✅ Retour d'informations complètes (URL, statut, ticket)
- ✅ Logs améliorés avec "Commande en statut Cart (panier)"
- ✅ Fallback pour anciens comportements

### 2️⃣ Modification : [admin/class-bihr-admin.php](admin/class-bihr-admin.php#L142-L170)

**Fonction modifiée:** `ajax_get_order_data()`

**Changements :**
```php
$data = $this->api_client->get_order_data( $bihr_ticket_id );

// Nouveau: Vérifier le drapeau 'error'
if ( $data && isset( $data['error'] ) && $data['error'] ) {
    wp_send_json_error( array(
        'message'        => $data['message'],
        'request_status' => $data['request_status'] ?? '',
        'order_url'      => $data['order_url'] ?? '',
        'ticket_id'      => $data['ticket_id'] ?? '',
    ) );
}
```

**Avantages :**
- ✅ Extraction des détails de l'erreur
- ✅ Retour d'une réponse JSON structurée
- ✅ Simplification du code (suppression de la logique complexe)

### 3️⃣ Vérification : [admin/views/orders-settings-page.php](admin/views/orders-settings-page.php#L370-L440)

**Code JavaScript :** ✅ Déjà compatible

Le JavaScript affiche déjà les détails supplémentaires :
```javascript
if (payload.request_status) extra += '\nrequest_status: ' + payload.request_status;
if (payload.order_url) extra += '\norder_url: ' + payload.order_url;
if (payload.ticket_id) extra += '\nticket_id: ' + payload.ticket_id;
```

## 🎨 Résultat Visible pour l'Utilisateur

**Message affiché après :**
```
❌ Erreur: Order/Data indisponible tant que BIHR n'a pas généré une commande (statut=Cart).
request_status: Cart
order_url: https://www.mybihr.com/fr/fr/my-account/saved-carts/000013872145
ticket_id: a172b4ecb99b42208a62501a38be56b7
```

## 📈 Flux Amélioré

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Commande WooCommerce créée (#2942)                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Synchronisation vers BIHR (POST /Order/Creation)         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. Retour: TicketId (a172b4ecb99b42208a62501a38be56b7)      │
│    Status: Cart (panier)                                     │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. Utilisateur clique sur "Voir" pour Order/Data            │
│    (GET /Order/Data?orderId=...)                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. NOUVEAU: Plugin détecte statut "Cart"                    │
│    ├─ Appel GenerationStatus pour vérifier                  │
│    └─ Retour message explicite avec détails                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. Interface affiche:                                        │
│    "Order/Data indisponible (statut=Cart)"                  │
│    + URL BIHR + TicketId                                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼ (⏳ Attendre BIHR)
│
┌─────────────────────────────────────────────────────────────┐
│ 7. BIHR finalise (RequestStatus = "Order")                  │
│    Utilisateur clique "Actualiser"                          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 8. GET /Order/Data retourne 200 OK                          │
│    ✅ Données complètes affichées                            │
└─────────────────────────────────────────────────────────────┘
```

## 📋 Fichiers Modifiés

| Fichier | Lignes | Type | Impact |
|---------|--------|------|--------|
| `includes/class-bihr-api-client.php` | 509-600 | 🔧 Fonction | Détection du statut Cart + erreur structurée |
| `admin/class-bihr-admin.php` | 142-170 | 🔧 Fonction | Traitement du flag 'error' |
| `admin/views/orders-settings-page.php` | 370-440 | ✅ Déjà prêt | Affichage des détails (pas de change) |

## 🧪 Tests Effectués

```bash
✅ php -l includes/class-bihr-api-client.php    # Syntaxe OK
✅ php -l admin/class-bihr-admin.php            # Syntaxe OK
✅ git diff                                      # Changements acceptables
```

## 🚀 Déploiement

1. **Validation :** Tous les fichiers PHP passent la vérification de syntaxe
2. **Backward compatible :** Le fallback pour TicketId reste en place
3. **Prêt à tester :** Créer une commande WooCommerce et vérifier le message

## 📝 Documentation Additionnelle

- **[CART_STATUS_FIX.md](CART_STATUS_FIX.md)** - Explications détaillées
- **[CART_STATUS_TEST_FLOW.json](CART_STATUS_TEST_FLOW.json)** - Flow JSON complet
- **[test-cart-status-demo.sh](test-cart-status-demo.sh)** - Script de démonstration

## 💡 Points Clés

1. **Expérience utilisateur** : Message explicite au lieu de "voir les logs"
2. **Diagnostic** : URL BIHR + Statut + TicketId fournis d'emblée
3. **Robustesse** : Gestion des deux paramètres (`orderId` et `TicketId`)
4. **Logs enrichis** : Traçabilité améliorée pour l'admin
5. **Zéro rupture** : Pas de changement pour les cas où ça marche déjà

---

**Status** : ✅ Prêt pour intégration et test en production
