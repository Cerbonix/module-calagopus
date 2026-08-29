# Calagopus

Module de provisioning ClientXCMS pour **Calagopus**, panel de gestion de serveurs de jeu écrit en Rust. Une commande client crée le serveur, une suspension l'arrête, une résiliation le supprime, sans intervention manuelle.

## Ce que fait le module

| Capacité | Détail |
|---|---|
| Création | Crée le compte client sur le panel s'il n'existe pas, puis déploie le serveur. Le panel choisit lui-même le nœud et le port |
| Suspension et réactivation | Suivent l'état de facturation du service |
| Renouvellement | Réactive un serveur suspendu, ne fait rien s'il était déjà actif |
| Montée en gamme et options | Recalcule les limites depuis le produit et les options souscrites, puis les applique |
| Changement de client | Transfère la propriété du serveur sur le panel |
| Résiliation | Supprime le serveur et **conserve les sauvegardes** selon la durée choisie par produit |
| Purge des sauvegardes | Tâche planifiée qui supprime les sauvegardes conservées une fois leur durée écoulée |
| Espace client | Adresse de connexion, ressources, état, accès au panel |
| Import | Rattache un serveur déjà présent sur le panel à un service existant |

## Prérequis

| | |
|---|---|
| ClientXCMS | Instance avec le système de modules v2 |
| Panel Calagopus | Version `1.1.x`, vérifié sur `1.1.4` |
| Accès réseau | Depuis ClientXCMS vers le panel, en HTTPS |

## Installation

1. Placer le dossier `calagopus` dans `modules/` de votre instance ClientXCMS.
2. Activer le module depuis l'administration, section Extensions.
3. Ajouter le panel dans Provisioning, puis lancer le test de connexion.

## Créer la clé API

Le module s'authentifie avec une clé API de panel, en-tête `Authorization: Bearer`. Créez un **compte de service dédié**, pas un compte humain, et une clé qui ne sert qu'à ClientXCMS.

Le compte porteur ne doit **pas** être administrateur du panel. L'autorisation effective est l'intersection des permissions du compte et des portées de la clé : un compte administrateur annule le bénéfice d'une clé restreinte.

Permissions à accorder, dans la portée **`admin_permissions`** de la clé :

| Permission | Pourquoi |
|---|---|
| `users.create` | Créer le compte du client sur le panel |
| `users.read` | Retrouver un compte existant par son identifiant externe |
| `servers.create` | Créer le serveur |
| `servers.read` | Lire l'état du serveur |
| `servers.update` | Suspendre, réactiver, changer les limites ou le propriétaire |
| `servers.delete` | Résilier |
| `eggs.read` | Lister les eggs pour la configuration des produits |
| `locations.read` | Alimenter le placement automatique |
| `stats.read` | Lire la version du panel, vérifiée à la connexion |

Ajoutez `backups.read` et `backups.delete` si vous utilisez la conservation des sauvegardes.

Le test de connexion vérifie réellement chacune de ces lectures et nomme celles qui manquent. Il ne peut pas vérifier les écritures sans créer quelque chose : `users.create`, `servers.create`, `servers.update` et `servers.delete` restent à votre charge.

`stats.read` ouvre aussi la lecture de l'inventaire matériel et logiciel de l'hôte du panel, au-delà du seul numéro de version utilisé par le module. Tenez-en compte dans votre évaluation de risque.

## Restriction par adresse IP : à lire avant la mise en service

Une clé dont la liste `allowed_ips` n'est pas vide renvoie **`403`** pour toute requête émise depuis une adresse non déclarée.

C'est le piège d'exploitation numéro un. Si votre instance ClientXCMS sort par plusieurs adresses (répartition de charge, haute disponibilité, bascule, passerelle secondaire), le provisioning fonctionnera tant que le trafic emprunte l'adresse déclarée, **puis tombera silencieusement à la première bascule**.

Deux règles :

1. Déclarez **toutes** vos adresses de sortie, pas seulement celle observée au moment de la configuration.
2. Ajoutez la mise à jour de cette liste à votre procédure d'ajout de nœud.

Laisser `allowed_ips` vide désactive la restriction. C'est plus simple, et moins sûr.

## Configurer un produit

### Les unités ne sont pas celles de Pterodactyl

| Champ | Unité |
|---|---|
| Processeur | **Pourcentage d'un cœur** : 100 pour 1 vCPU, 200 pour 2 vCPU, 0 pour illimité |
| Mémoire, disque, swap, marge mémoire | **MiB**, pas Go. 1024 MiB font 1 Go |
| Swap | 0 pour aucun swap, -1 pour illimité |
| Poids d'entrées-sorties | Poids relatif de 0 à 1000, sans unité physique |

### La plage de ports est obligatoire

Le panel n'attribue **aucun port** si aucune plage n'est demandée, et il ne signale pas d'erreur : le serveur est créé, la commande réussit, et le serveur est injoignable. Renseignez toujours une plage cohérente avec les allocations de vos nœuds.

### Le poids d'entrées-sorties peut n'avoir aucun effet

Il n'est appliqué que si le nœud dispose d'un ordonnanceur compatible, `bfq` ou `iocost`. Sinon, la valeur est ignorée et l'avertissement reste dans les journaux du daemon, sans jamais remonter au panel ni ici. Vérifiez vos nœuds avant de facturer cette ressource.

## Conservation des sauvegardes

La résiliation ne détruit jamais les sauvegardes du client. Chaque produit déclare une durée de conservation en jours, `0` conservant sans limite.

Les sauvegardes sont relevées **au moment de la résiliation**, tant que le serveur existe encore : une fois celui-ci supprimé, plus rien ne les rattache à quoi que ce soit.

La purge est une tâche planifiée quotidienne. Elle est bornée, rejouable sans dommage, et ne touche jamais une sauvegarde dont la durée n'est pas écoulée :

```sh
php artisan calagopus:purge-backups --dry-run   # liste sans rien supprimer
php artisan calagopus:purge-backups --limit=50  # borne un passage
```

Lancez-la une première fois avec `--dry-run`.

## Importer un serveur existant

Depuis la fiche d'un service, choisissez le serveur du panel à rattacher. Un serveur déjà rattaché à un autre service est **refusé**, pas volé : l'identifiant externe est la clé de réconciliation, l'écraser rendrait l'autre service orphelin. Réimporter le même serveur sur le même service ne change rien.

## Dépannage

| Symptôme | Cause la plus probable |
|---|---|
| `401` à la configuration | Clé invalide, expirée, ou mal recopiée. Le panel exige un jeton de 48 caractères exactement |
| Message « aucune clé API lisible » | Le champ est vide, ou la clé enregistrée n'est plus déchiffrable, ce qui arrive après une rotation de la clé d'application du site |
| `403` alors que la clé est bonne | L'adresse de sortie n'est pas dans `allowed_ips`, ou une permission manque |
| `409` à la création d'un client | Un compte du panel utilise déjà cet e-mail. Le module réutilise le compte existant, ce n'est pas une erreur |
| Serveur créé mais injoignable | Aucune plage de ports configurée sur le produit |
| Ressources non appliquées après une montée en gamme | Le panel accepte la modification puis la transmet au nœud sans attendre. Si le nœud est injoignable, l'erreur reste dans les journaux du panel |

## Licence

Copyright (c) 2026 Cerbonix. Les conditions de licence ne sont pas encore arrêtées.

---

# Calagopus (English)

ClientXCMS provisioning module for **Calagopus**, a game server management panel written in Rust. A customer order creates the server, a suspension stops it, a termination removes it, with no manual step.

## What it does

| Capability | Detail |
|---|---|
| Creation | Creates the customer account on the panel if missing, then deploys the server. The panel picks the node and the port itself |
| Suspend and unsuspend | Follow the billing state of the service |
| Renewal | Unsuspends a suspended server, does nothing if it was already active |
| Upgrades and options | Rebuilds limits from the product plus subscribed options, then applies them |
| Customer change | Transfers server ownership on the panel |
| Termination | Deletes the server and **keeps backups** for the retention set per product |
| Backup purge | Scheduled task deleting kept backups once their retention has elapsed |
| Client area | Connection address, resources, state, panel access |
| Import | Attaches a server already present on the panel to an existing service |

## Requirements

| | |
|---|---|
| ClientXCMS | Instance with the v2 module system |
| Calagopus panel | Version `1.1.x`, verified against `1.1.4` |
| Network access | From ClientXCMS to the panel, over HTTPS |

## Installation

1. Put the `calagopus` folder into `modules/` on your ClientXCMS instance.
2. Enable the module from the admin area, Extensions section.
3. Add the panel under Provisioning, then run the connection test.

## Creating the API key

The module authenticates with a panel API key, using the `Authorization: Bearer` header. Create a **dedicated service account**, not a human one, and a key used only by ClientXCMS.

The owning account must **not** be a panel administrator. Effective authorization is the intersection of the account permissions and the key scopes: an administrator account cancels the benefit of a restricted key.

Permissions to grant, in the key's **`admin_permissions`** scope:

| Permission | Why |
|---|---|
| `users.create` | Create the customer account on the panel |
| `users.read` | Look up an existing account by external id |
| `servers.create` | Create the server |
| `servers.read` | Read server state |
| `servers.update` | Suspend, unsuspend, change limits or owner |
| `servers.delete` | Terminate |
| `eggs.read` | List eggs for product configuration |
| `locations.read` | Feed automatic placement |
| `stats.read` | Read the panel version, checked on connection test |

Add `backups.read` and `backups.delete` if you use backup retention.

The connection test actually exercises each of those reads and names the missing ones. It cannot check writes without creating something, so `users.create`, `servers.create`, `servers.update` and `servers.delete` remain your responsibility.

`stats.read` also exposes the panel host hardware and software inventory, beyond the version number the module uses. Factor that into your risk assessment.

## IP restriction: read before going live

A key whose `allowed_ips` list is not empty returns **`403`** for any request coming from an undeclared address.

This is the number one operational trap. If your ClientXCMS instance egresses through several addresses (load balancing, high availability, failover, secondary gateway), provisioning will work as long as traffic uses the declared address, **then fail silently on the first failover**.

Two rules:

1. Declare **every** egress address, not just the one observed while configuring.
2. Add updating this list to your node provisioning runbook.

Leaving `allowed_ips` empty disables the restriction. Simpler, and less safe.

## Configuring a product

### Units are not Pterodactyl's

| Field | Unit |
|---|---|
| CPU | **Percentage of one core**: 100 for 1 vCPU, 200 for 2 vCPU, 0 for unlimited |
| Memory, disk, swap, memory overhead | **MiB**, not GB. 1024 MiB is 1 GB |
| Swap | 0 for no swap, -1 for unlimited |
| I/O weight | Relative weight from 0 to 1000, no physical unit |

### The port range is mandatory

The panel assigns **no port at all** when no range is requested, and reports no error: the server is created, the order succeeds, and the server is unreachable. Always set a range consistent with your nodes' allocations.

### I/O weight may have no effect

It only applies when the node runs a compatible scheduler, `bfq` or `iocost`. Otherwise the value is ignored and the warning stays in the daemon logs, never reaching the panel nor this module. Check your nodes before billing that resource.

## Backup retention

Termination never destroys customer backups. Each product declares a retention in days, `0` keeping them forever.

Backups are recorded **at termination time**, while the server still exists: once it is gone, nothing ties them to anything.

The purge is a daily scheduled task. It is bounded, safe to replay, and never touches a backup whose retention has not elapsed:

```sh
php artisan calagopus:purge-backups --dry-run   # list without deleting
php artisan calagopus:purge-backups --limit=50  # bound a single run
```

Run it with `--dry-run` the first time.

## Importing an existing server

From a service page, pick the panel server to attach. A server already attached to another service is **refused**, not stolen: the external id is the reconciliation key, and overwriting it would orphan the other service. Re-importing the same server onto the same service changes nothing.

## Troubleshooting

| Symptom | Most likely cause |
|---|---|
| `401` when configuring | Invalid, expired or mistyped key. The panel expects a 48 character token |
| "No readable API key" | The field is empty, or the stored key can no longer be decrypted, which happens after the site application key was rotated |
| `403` although the key is correct | The egress address is missing from `allowed_ips`, or a permission is missing |
| `409` when creating a customer | A panel account already uses that email. The module reuses it, this is not a failure |
| Server created but unreachable | No port range configured on the product |
| Resources not applied after an upgrade | The panel accepts the change then forwards it to the node without waiting. If the node is unreachable, the error stays in the panel logs |

## License

Copyright (c) 2026 Cerbonix. Licensing terms are not settled yet.
