# Calagopus

Module de provisioning ClientXCMS pour **Calagopus**, panel de gestion de serveurs de jeu écrit en Rust. Il crée, suspend, réactive et résilie automatiquement les serveurs de jeu commandés sur votre espace client.

> **En développement.** Ce module ne provisionne encore rien. Seule la coquille est en place. Ne l'installez pas sur une instance de production.

## Prérequis

| | |
|---|---|
| ClientXCMS | Instance avec le système de modules v2 |
| Panel Calagopus | Version `1.1.x`, vérifié sur `1.1.4` |
| Accès réseau | Depuis ClientXCMS vers le panel, en HTTPS |

## Installation

1. Placer le dossier `calagopus` dans `modules/` de votre instance ClientXCMS.
2. Activer le module depuis l'administration, section Extensions.

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

Aucune autre permission n'est nécessaire. En particulier, `nodes.read` est inutile : le placement du serveur est délégué au panel.

`stats.read` ouvre aussi la lecture de l'inventaire matériel et logiciel de l'hôte du panel (processeur, noyau, architecture), au-delà du seul numéro de version utilisé par le module. Tenez-en compte dans votre évaluation de risque.

## Restriction par adresse IP : à lire avant la mise en service

Une clé dont la liste `allowed_ips` n'est pas vide renvoie **`403`** pour toute requête émise depuis une adresse non déclarée.

C'est le piège d'exploitation numéro un. Si votre instance ClientXCMS sort par plusieurs adresses (répartition de charge, haute disponibilité, bascule, passerelle secondaire), le provisioning fonctionnera tant que le trafic emprunte l'adresse déclarée, **puis tombera silencieusement à la première bascule**.

Deux règles :

1. Déclarez **toutes** vos adresses de sortie, pas seulement celle observée au moment de la configuration.
2. Ajoutez la mise à jour de cette liste à votre procédure d'ajout de nœud. Sinon la panne surviendra en production, des mois plus tard.

Laisser `allowed_ips` vide désactive la restriction. C'est plus simple, et moins sûr.

## Dépannage

| Symptôme | Cause la plus probable |
|---|---|
| `401` à la configuration | Clé invalide, expirée, ou mal recopiée. Le panel exige un jeton de 48 caractères exactement et rejette l'en-tête avant même de consulter sa base |
| `403` alors que la clé est bonne | L'adresse de sortie n'est pas dans `allowed_ips`, ou la permission manque dans la portée `admin_permissions` |
| `409` à la création d'un client | Un compte du panel utilise déjà cet e-mail ou cet identifiant. Le module réutilise le compte existant, ce n'est pas une erreur de provisioning |
| Version du panel signalée hors plage | Le module déclare les versions qu'il a réellement vérifiées. Au-delà, il ne garantit rien |

## Licence

Copyright (c) 2026 Cerbonix. Les conditions de licence ne sont pas encore arrêtées.

---

# Calagopus (English)

ClientXCMS provisioning module for **Calagopus**, a game server management panel written in Rust. It automatically creates, suspends, unsuspends and terminates the game servers ordered through your client area.

> **Work in progress.** This module does not provision anything yet. Only the shell is in place. Do not install it on a production instance.

## Requirements

| | |
|---|---|
| ClientXCMS | Instance with the v2 module system |
| Calagopus panel | Version `1.1.x`, verified against `1.1.4` |
| Network access | From ClientXCMS to the panel, over HTTPS |

## Installation

1. Put the `calagopus` folder into `modules/` on your ClientXCMS instance.
2. Enable the module from the admin area, Extensions section.

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

Nothing else is required. In particular, `nodes.read` is not needed: server placement is delegated to the panel.

`stats.read` also exposes the panel host hardware and software inventory (CPU, kernel, architecture), beyond the version number the module actually uses. Factor that into your risk assessment.

## IP restriction: read before going live

A key whose `allowed_ips` list is not empty returns **`403`** for any request coming from an undeclared address.

This is the number one operational trap. If your ClientXCMS instance egresses through several addresses (load balancing, high availability, failover, secondary gateway), provisioning will work as long as traffic uses the declared address, **then fail silently on the first failover**.

Two rules:

1. Declare **every** egress address, not just the one observed while configuring.
2. Add updating this list to your node provisioning runbook. Otherwise the outage will happen in production, months later.

Leaving `allowed_ips` empty disables the restriction. Simpler, and less safe.

## Troubleshooting

| Symptom | Most likely cause |
|---|---|
| `401` when configuring | Invalid, expired or mistyped key. The panel expects a 48 character token and rejects the header before even querying its database |
| `403` although the key is correct | The egress address is missing from `allowed_ips`, or the permission is missing from the `admin_permissions` scope |
| `409` when creating a customer | A panel account already uses that email or username. The module reuses the existing account, this is not a provisioning failure |
| Panel version reported out of range | The module declares the versions it has actually been verified against. Beyond those, it guarantees nothing |

## License

Copyright (c) 2026 Cerbonix. Licensing terms are not settled yet.
