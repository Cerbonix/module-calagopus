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
| Authentification unique | Le client arrive connecté sur son serveur, sans saisir de mot de passe. Nécessite l'extension du panel |

## Prérequis

| | |
|---|---|
| ClientXCMS | Instance avec le système de modules v2 |
| Panel Calagopus | Version `1.1.x`, vérifié sur `1.1.4` |
| Accès réseau | Depuis ClientXCMS vers le panel, en HTTPS |
| Pour l'authentification unique seulement | Un panel en image `heavy`, seule variante capable d'accueillir des extensions. Décidez-le avant la mise en production : changer d'image ensuite se fait, mais pas en un clic |

Les commandes `php artisan` de ce document se lancent à la racine de votre instance ClientXCMS, avec l'utilisateur qui fait tourner l'application. Si elle tourne en conteneur, préfixez-les de la manière habituelle chez vous, par exemple `docker exec <conteneur> php artisan …`.

## Installation

1. Récupérer le module dans le dossier `modules/` de votre instance :

   ```sh
   git clone https://github.com/Cerbonix/module-calagopus.git /tmp/module-calagopus
   cp -r /tmp/module-calagopus/modules/calagopus modules/calagopus
   ```

2. L'activer dans **Paramètres > Extensions** (`/admin/settings/extensions`).
3. Créer la clé API sur le panel, comme décrit à la section suivante.
4. Déclarer le panel dans **Paramètres > Approvisionnement > Serveurs**, bouton « Créer un serveur » (`/admin/servers/create`), en choisissant le type `Calagopus`.

Les libellés de ce formulaire sont ceux du noyau ClientXCMS, communs à tous les panels. Deux d'entre eux ne veulent pas dire ce qu'ils annoncent ici :

| Champ affiché | Ce que le module y attend |
|---|---|
| Nom | Le nom du panel dans votre administration, libre |
| Type de serveur | `Calagopus` |
| Nom d'hôte | L'adresse d'API du panel, par exemple `panel.example.net` |
| Adresse IP | Facultatif, informatif |
| Port | `443` en HTTPS |
| **Nom d'utilisateur** | **Le secret partagé de l'authentification unique**, à laisser vide pour l'instant. La commande `php artisan calagopus:sso` l'écrira |
| **Mot de passe** | **La clé API du panel**, 48 caractères |

5. Lancer **Tester la connexion** sur la fiche du serveur. Il vérifie la version du panel, chaque permission de lecture de la clé, et l'état de l'authentification unique.

## Créer la clé API

Le module s'authentifie avec une clé API de panel, en-tête `Authorization: Bearer`. Créez un **compte de service dédié**, pas un compte humain, et une clé qui ne sert qu'à ClientXCMS.

Sur le panel, connectez-vous avec ce compte de service et ouvrez **`/account/api-keys`**, puis créez la clé. Elle n'est affichée qu'une fois : recopiez-la immédiatement dans le champ « Mot de passe » du serveur ClientXCMS.

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
| `eggs.read` | Lister les *eggs* pour la configuration des produits. Un egg est le modèle d'un jeu sur le panel : image de conteneur, commande de démarrage, variables. « Minecraft Vanilla » et « Rust » sont deux eggs |
| `locations.read` | Lister les emplacements, groupes de nœuds parmi lesquels le panel choisit où poser le serveur |
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

## Deux adresses, deux usages

Le module utilise **deux adresses différentes**, et les confondre est le piège le plus courant à la mise en service.

| Adresse | D'où elle vient | Qui l'utilise |
|---|---|---|
| Adresse d'API | Le champ « Nom d'hôte » du serveur, dans ClientXCMS | ClientXCMS, pour appeler le panel de serveur à serveur |
| Adresse publique | Le réglage `app.url` **du panel**, lu via `GET /api/settings` et mis en cache 5 minutes | Le navigateur du client, quand il clique sur « Ouvrir le panel » |

Le module ne recopie pas l'adresse publique : il la **demande au panel**, qui est seul à savoir où il se croit. Si le panel ne répond rien d'exploitable, le module retombe sur l'adresse d'API.

**Conséquence à vérifier une fois, au déploiement.** Si `app.url` est mal renseigné dans votre panel, vos clients recevront un lien mort, et le test de connexion ne le verra pas : il n'emprunte que l'adresse d'API. Les deux doivent être correctes, et elles diffèrent légitimement dès que le panel est derrière un réseau interne, un mandataire ou un VPN.

Le même partage existe côté nœud : renseignez son `public_url` si son `url` n'est pas joignable depuis un navigateur, sans quoi la console du client restera déconnectée.

## Configurer un produit

Dans **Paramètres de la boutique > Produits** (`/admin/products`), ouvrez le produit, choisissez le type de produit `Calagopus`, puis renseignez sa configuration : l'egg à déployer, le ou les emplacements autorisés, l'image de conteneur, la commande de démarrage, les ressources et la plage de ports. Les listes d'eggs et d'emplacements sont chargées depuis votre panel, donc un produit ne peut se configurer qu'une fois le serveur déclaré et le test de connexion passé.

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

La résiliation ne détruit jamais les sauvegardes du client. Chaque produit déclare une durée de conservation en jours, **30 par défaut**, `0` conservant sans limite.

Conserver `0` vous expose au titre du principe de limitation de la conservation : des données personnelles d'anciens clients s'accumulent alors sans terme. Ne le choisissez qu'en connaissance de cause.

### Ce que voit le client

Tant que son service est actif, la fiche de service annonce la durée de conservation applicable en cas de résiliation, et renvoie vers `/calagopus/backups`.

Cette page reste accessible après la résiliation, ce qui est le point important : le noyau cesse d'afficher le panel du module dès que le service n'est plus actif (`ProvisioningTabDTO::renderPanel`), donc l'information ne pouvait pas y vivre. Le client y retrouve ses sauvegardes conservées, leur date de suppression automatique, et un bouton de suppression immédiate protégé par une confirmation.

Chaque requête de cette page est bornée au client connecté par une jointure sur le service : personne ne peut lister ni supprimer les sauvegardes d'un autre.

Le texte affiché distingue explicitement les sauvegardes applicatives, seules concernées, des sauvegardes système de votre infrastructure, qui suivent leur propre cycle de vie. Adaptez-le à votre politique de confidentialité si la vôtre diffère.

Les sauvegardes sont relevées **au moment de la résiliation**, tant que le serveur existe encore : une fois celui-ci supprimé, plus rien ne les rattache à quoi que ce soit.

La purge est une tâche planifiée quotidienne. Elle est bornée, rejouable sans dommage, et ne touche jamais une sauvegarde dont la durée n'est pas écoulée :

```sh
php artisan calagopus:purge-backups --dry-run   # liste sans rien supprimer
php artisan calagopus:purge-backups --limit=50  # borne un passage
```

Lancez-la une première fois avec `--dry-run`.

## Authentification unique (SSO)

Sans elle, le client qui clique sur « Ouvrir le panel » arrive sur la page de connexion du panel et doit saisir un mot de passe. Avec elle, il arrive **déjà connecté sur son serveur**.

### Ce qu'il faut

| | |
|---|---|
| Côté panel | L'extension [`Cerbonix/calagopus-sso-clientxcms`](https://github.com/Cerbonix/calagopus-sso-clientxcms) installée, ce qui suppose un panel en image `heavy`. Sa propre documentation couvre la construction et l'installation |
| Côté clé API | La permission `ssotickets.manage` en plus des autres |
| Des deux côtés | Un même secret partagé |

### Configuration

Une seule commande, qui s'occupe des deux côtés :

```sh
php artisan calagopus:sso            # génère le secret et le pose partout
php artisan calagopus:sso --show     # dit simplement si c'est configuré
php artisan calagopus:sso --secret=…  # impose votre propre secret
```

Elle donne d'abord le secret au panel. S'il refuse, ClientXCMS conserve l'ancien : jamais de configuration à moitié appliquée.

Le secret doit être connu **des deux côtés**. Le saisir à la main dans le champ « Nom d'utilisateur » ne configure donc que la moitié ClientXCMS : le panel, lui, n'apprend rien, et vos clients continuent d'arriver sur la page de connexion. C'est tout l'objet de la commande, qui sert les deux côtés d'un coup.

Le test de connexion du serveur demande réellement un ticket au panel pour trancher, et vous dit lequel de ces cas vous concerne :

| Réponse du test | Ce qu'elle veut dire |
|---|---|
| L'authentification unique est configurée | Les deux secrets s'accordent, rien à faire |
| Le secret partagé ne correspond pas | Les deux côtés divergent, rejouez la commande |
| L'extension n'est pas installée sur le panel | Voir les prérequis ci-dessus |
| L'extension ne répond pas | Le panel est joignable, son extension non |

### Où vit le secret, et pourquoi là

Côté ClientXCMS, dans le champ **Nom d'utilisateur** du serveur. Ce nom ne l'annonce pas, et c'est délibéré : ce champ est **chiffré au repos**, contrairement aux métadonnées du serveur, qui sont stockées en clair. Un secret partagé ne se range pas en clair, même dans sa propre base.

Vous n'avez normalement pas à y toucher : la commande ci-dessus l'écrit pour vous.

Côté panel, le secret est stocké chiffré dans les réglages de l'extension.

### Comment ça marche

1. Le client clique sur « Ouvrir le panel » dans son espace client.
2. ClientXCMS vérifie que le service lui appartient, puis demande un **ticket** au panel, en présentant le secret partagé.
3. Le panel renvoie un ticket à usage unique, valable une minute.
4. Le client est redirigé dessus, le panel ouvre sa session et l'amène sur son serveur.

Le secret ne quitte jamais le dialogue entre les deux serveurs : il ne passe ni par le navigateur, ni par une URL.

### Si le SSO n'est pas configuré

Rien ne casse. Le bouton renvoie le client vers le panel comme un lien ordinaire, il s'y connecte lui-même. La même chose s'applique si le panel ne répond pas : le client n'est jamais laissé sans issue.

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
| Single sign-on | The customer lands signed in on their server, with no password to type. Requires the panel extension |

## Requirements

| | |
|---|---|
| ClientXCMS | Instance with the v2 module system |
| Calagopus panel | Version `1.1.x`, verified against `1.1.4` |
| Network access | From ClientXCMS to the panel, over HTTPS |
| For single sign-on only | A panel running the `heavy` image, the only variant able to host extensions. Decide before going live: switching later is possible, but not in one click |

The `php artisan` commands in this document run at the root of your ClientXCMS instance, as the user running the application. If it runs in a container, prefix them the way you usually do, for instance `docker exec <container> php artisan …`.

## Installation

1. Get the module into the `modules/` folder of your instance:

   ```sh
   git clone https://github.com/Cerbonix/module-calagopus.git /tmp/module-calagopus
   cp -r /tmp/module-calagopus/modules/calagopus modules/calagopus
   ```

2. Enable it under **Paramètres > Extensions** (`/admin/settings/extensions`).
3. Create the API key on the panel, as described in the next section.
4. Declare the panel under **Paramètres > Approvisionnement > Serveurs**, "Créer un serveur" button (`/admin/servers/create`), picking the `Calagopus` type.

   The ClientXCMS admin area currently ships French labels only, whatever your locale, hence the French menu names above. The URLs are the reliable anchor.

The labels on that form come from the ClientXCMS core and are shared by every panel. Two of them do not mean what they say here:

| Displayed field | What the module expects |
|---|---|
| Nom | The panel name in your admin area, free text |
| Type de serveur | `Calagopus` |
| Nom d'hôte | The panel API address, for instance `panel.example.net` |
| Adresse IP | Optional, informational |
| Port | `443` over HTTPS |
| **Nom d'utilisateur** | **The single sign-on shared secret**, leave it empty for now. `php artisan calagopus:sso` will write it |
| **Mot de passe** | **The panel API key**, 48 characters |

5. Run **Test connection** on the server page. It checks the panel version, every read permission on the key, and the single sign-on state.

## Creating the API key

The module authenticates with a panel API key, using the `Authorization: Bearer` header. Create a **dedicated service account**, not a human one, and a key used only by ClientXCMS.

On the panel, sign in as that service account, open **`/account/api-keys`**, then create the key. It is shown only once: copy it straight into the "Password" field of the ClientXCMS server.

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
| `eggs.read` | List the *eggs* used when configuring products. An egg is a game template on the panel: container image, startup command, variables. "Minecraft Vanilla" and "Rust" are two eggs |
| `locations.read` | List locations, the node groups the panel picks from when placing the server |
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

## Two addresses, two uses

The module uses **two different addresses**, and mixing them up is the most common go-live trap.

| Address | Where it comes from | Who uses it |
|---|---|---|
| API address | The server "Hostname" field, in ClientXCMS | ClientXCMS, to call the panel server to server |
| Public address | The panel's own `app.url` setting, read through `GET /api/settings` and cached for 5 minutes | The customer's browser, when clicking "Open the panel" |

The module does not copy the public address: it **asks the panel**, which alone knows where it thinks it lives. If the panel returns nothing usable, the module falls back to the API address.

**Check this once, at deployment.** If `app.url` is wrong in your panel, your customers get a dead link, and the connection test will not catch it: it only ever uses the API address. Both must be correct, and they legitimately differ as soon as the panel sits behind an internal network, a proxy or a VPN.

The same split exists on the node: set its `public_url` when its `url` is not reachable from a browser, otherwise the customer console stays disconnected.

## Configuring a product

Under **Paramètres de la boutique > Produits** (`/admin/products`), open the product, pick the `Calagopus` product type, then fill in its configuration: the egg to deploy, the allowed locations, the container image, the startup command, the resources and the port range. The egg and location lists are loaded from your panel, so a product can only be configured once the server is declared and the connection test passes.

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

Termination never destroys customer backups. Each product declares a retention in days, **30 by default**, `0` keeping them forever.

Keeping `0` exposes you under the storage limitation principle: personal data of former customers then piles up with no end date. Only pick it knowingly.

### What the customer sees

While the service is active, the service page states the retention that would apply on termination, and links to `/calagopus/backups`.

That page stays reachable after termination, which is the point: the core stops rendering the module panel as soon as the service is no longer active (`ProvisioningTabDTO::renderPanel`), so the information could not live there. The customer finds their kept backups, their automatic deletion date, and an immediate deletion button behind a confirmation.

Every query on that page is scoped to the signed-in customer through a join on the service: nobody can list or delete someone else's backups.

The wording tells application backups, the only ones concerned, apart from the system backups of your infrastructure, which follow their own lifecycle. Adjust it to your privacy policy if yours differs.

Backups are recorded **at termination time**, while the server still exists: once it is gone, nothing ties them to anything.

The purge is a daily scheduled task. It is bounded, safe to replay, and never touches a backup whose retention has not elapsed:

```sh
php artisan calagopus:purge-backups --dry-run   # list without deleting
php artisan calagopus:purge-backups --limit=50  # bound a single run
```

Run it with `--dry-run` the first time.

## Single sign-on (SSO)

Without it, a customer clicking "Open the panel" lands on the panel login page and has to type a password. With it, they land **already signed in on their server**.

### What it takes

| | |
|---|---|
| On the panel | The [`Cerbonix/calagopus-sso-clientxcms`](https://github.com/Cerbonix/calagopus-sso-clientxcms) extension installed, which implies a panel running the `heavy` image. Its own documentation covers building and installing it |
| On the API key | The `ssotickets.manage` permission, on top of the others |
| On both sides | One shared secret |

### Setting it up

A single command handles both sides:

```sh
php artisan calagopus:sso            # generate the secret and set it everywhere
php artisan calagopus:sso --show     # just report whether it is configured
php artisan calagopus:sso --secret=…  # use your own secret
```

It gives the secret to the panel first. If the panel refuses, ClientXCMS keeps the previous one: never a half-applied setup.

The secret has to be known **on both sides**. Typing it by hand into the "Nom d'utilisateur" field therefore configures the ClientXCMS half only: the panel learns nothing, and your customers keep landing on the login page. That is what the command is for, it serves both sides at once.

The server connection test actually asks the panel for a ticket to settle it, and tells you which case you are in:

| Test answer | What it means |
|---|---|
| Single sign-on is configured | Both secrets agree, nothing to do |
| The shared secret does not match | The two sides diverge, run the command again |
| The extension is not installed on the panel | See the requirements above |
| The extension does not answer | The panel is reachable, its extension is not |

### Where the secret lives, and why there

On the ClientXCMS side, in the server **Username** field. That name does not advertise it, and that is deliberate: this field is **encrypted at rest**, unlike server metadata, which is stored in clear text. A shared secret does not belong in clear text, not even in your own database.

You normally never touch it: the command above writes it for you.

On the panel side, the secret is stored encrypted in the extension settings.

### How it works

1. The customer clicks "Open the panel" in the client area.
2. ClientXCMS checks the service is theirs, then asks the panel for a **ticket**, presenting the shared secret.
3. The panel returns a single-use ticket, valid for one minute.
4. The customer is redirected onto it, the panel opens their session and takes them to their server.

The secret never leaves the server-to-server conversation: it goes through neither the browser nor a URL.

### When SSO is not configured

Nothing breaks. The button sends the customer to the panel as a plain link and they sign in themselves. Same if the panel does not answer: the customer is never left stranded.

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
