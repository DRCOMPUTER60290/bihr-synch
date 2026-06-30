# Caractéristiques serveur O2Switch

> Relevé le 2026-06-30 via Novamira MCP

## Environnement PHP

| Paramètre | Valeur |
|---|---|
| PHP version | 8.2.31 |
| memory_limit | 512M |
| max_execution_time (web) | 30s *(ignoré en WP-CLI/bash)* |
| max_input_time | 360s |
| upload_max_filesize | 512M |
| post_max_size | 512M |
| max_input_vars | 250 000 |
| OPcache | activé |

## Environnement WordPress

| Paramètre | Valeur |
|---|---|
| WP_MEMORY_LIMIT (web) | 40M |
| WP_MAX_MEMORY_LIMIT (WP-CLI) | 512M |
| WP_CRON | désactivé (DISABLE_WP_CRON) |
| Serveur web | Apache |

## Environnement MySQL / MariaDB

| Paramètre | Valeur |
|---|---|
| Version | MariaDB 11.4.12 |
| wait_timeout | **260s** ← contrainte principale en batch |
| max_connections | 600 |
| max_allowed_packet | 512 MB |
| InnoDB buffer pool | **24 GB** |

## Ressources système

| Paramètre | Valeur |
|---|---|
| CPU cores | 64 |
| Disque libre | 115 GB |
| Disque total | 216 GB |

---

## Recommandations import bash (WP-CLI)

En WP-CLI, le timeout PHP de 30s n'existe pas. La seule limite temporelle est le `wait_timeout` MySQL de **260s** (4m20s).

| Scénario | Taille de batch conseillée |
|---|---|
| Produits simples, sans images | **500–1 000** produits/batch |
| Produits avec téléchargement d'images | **200–300** produits/batch |
| Produits avec variations complexes | **100–200** produits/batch |

### Règle à respecter

Un batch ne doit pas dépasser **260 secondes** d'exécution MySQL, sinon la connexion est coupée.

Avec les optimisations actuelles du plugin (cache lookup `wp_wc_product_meta_lookup`, defer term counting, batches AJAX), **500 produits/batch** est une valeur sûre pour un import sans images depuis la ligne de commande.
