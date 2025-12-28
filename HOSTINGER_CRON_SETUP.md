# Configuration du Cron Serveur sur Hostinger

## Contexte
Si vos tâches WP-Cron ne se déclenchent pas automatiquement (problème courant sur Hostinger), vous devez configurer un **cron serveur** (tâche planifiée côté serveur) pour exécuter `wp-cron.php` régulièrement.

## ⚙️ Étapes Hostinger (hPanel)

### 1. Accès au panneau hPanel
- Allez sur [hPanel](https://hpanel.hostinger.com)
- Connectez-vous avec vos identifiants Hostinger
- Sélectionnez votre **domaine** dans la liste

### 2. Localiser les tâches planifiées
- Dans le menu de gauche, cherchez **"Tâches planifiées"** ou **"Cron Jobs"** (généralement sous "Outils" ou "Avancé")
- Cliquez dessus

### 3. Créer une nouvelle tâche
- Cliquez sur le bouton **"Ajouter une tâche cron"** ou **"+"**

### 4. Configurer la tâche

Remplissez les champs suivants :

| Champ | Valeur |
|-------|--------|
| **Exécution (Fréquence)** | Personnalisé → **Chaque 5 minutes** |
| **Commande** | `curl -s https://votre-domaine.com/wp-cron.php > /dev/null 2>&1` |
| **Email de notification** | (optionnel) Votre email ou laisser vide |

**⚠️ Important :** Remplacez `https://votre-domaine.com` par votre vrai domaine !

Exemple :
```bash
curl -s https://bisque-seal-123062.hostingersite.com/wp-cron.php > /dev/null 2>&1
```

### 5. Sauvegarder
- Cliquez sur **"Sauvegarder"** ou **"Ajouter"**
- Vous verrez confirmé : ✅ Tâche créée avec succès

## ✅ Vérification

### Dans hPanel
1. Allez dans **Tâches planifiées**
2. Vous verrez votre tâche avec le statut **"Actif"**
3. Cliquez sur la tâche pour voir l'**historique d'exécution**
   - Chaque exécution réussie est listée
   - Les erreurs s'affichent aussi

### Dans WordPress
1. Allez dans **BIHR → 📊 Logs**
2. Cliquez sur **"⚙️ Exécuter WP‑Cron maintenant"** pour tester
3. Regardez les logs, vous devriez voir :
   ```
   [TRACE] run_auto_prices_generation() appelée à 2025-12-28 12:05:00
   Cron Prices: génération démarrée (ticket_id=XXXXX).
   ```

Si ces messages apparaissent, **c'est gagné !** 🎉

## 🔧 Dépannage

### Problème : "Erreur de connexion" dans l'historique hPanel

**Cause :** L'URL n'est pas accessible ou mal formée

**Solution :**
1. Vérifiez l'URL (remplacez bien votre domaine)
2. Assurez-vous que WordPress n'est pas protégé par un mot de passe .htaccess
3. Si les requêtes HTTP sortantes sont bloquées, contactez le support Hostinger

### Problème : Pas d'exécution visible dans les logs

**Cause possible :** Le cron serveur s'exécute, mais l'action WordPress `bihrwi_auto_prices_generation` n'a pas été trouvée

**Solution :**
1. Allez dans **BIHR → ⚙️ WP‑Cron**
2. Vérifiez que vous avez configuré le **Planning Prices** (page Produits)
3. Revérifiez l'état du diagnostic (événements "EN RETARD" ou "OK" ?)

### Problème : Le cron s'exécute mais la génération Prices ne start pas

**Cause :** Votre API Bihr n'est peut-être pas configurée ou en erreur

**Solution :**
1. Allez dans **BIHR → 🔐 Authentification**
2. Vérifiez que vous êtes bien connecté à Bihr
3. Testez une génération manuelle : **BIHR → 📦 Produits → "Lancer la génération du catalog Prices"**

## 📋 Résumé rapide

| Action | Où ? | Résultat attendu |
|--------|------|------------------|
| Configurer cron | hPanel → Tâches planifiées | Statut "Actif" |
| Tester cron | BIHR → 📊 Logs → Bouton ⚙️ | `[TRACE] run_auto_prices_generation()...` |
| Vérifier événement | BIHR → ⚙️ WP‑Cron | Événements avec `✅ OK` ou `⚠️ EN RETARD` |

## 💬 Besoin d'aide ?

- **Support Hostinger :** [Contact Hostinger](https://support.hostinger.com)
- **Support WordPress :** Consultez la documentation WP-Cron officielle

---

**Note :** Cette configuration est permanente. Une fois en place, votre plugin BIHR fonctionnera sans intervention manuelle. 🚀
