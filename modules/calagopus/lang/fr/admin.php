<?php

return [
    'config' => [
        'server' => 'Panel Calagopus',
        'egg' => 'Egg',
        'egg_help' => 'Modèle de serveur de jeu. La liste est chargée depuis le panel sélectionné.',
        'locations' => 'Emplacements',
        'locations_help' => 'Le panel choisit lui-même le nœud et le port parmi ces emplacements. Il en faut au moins un, sinon la commande échouera.',

        'port_start' => 'Port de début',
        'port_end' => 'Port de fin',
        'port_range_help' => 'Plage dans laquelle le panel choisit le port du serveur. Sans plage, aucun port n\'est attribué et le serveur est injoignable.',
        'dedicated_ip' => 'IP dédiée',
        'dedicated_ip_help' => 'Exige une adresse IP ne portant aucun autre serveur. La commande échoue si aucune n\'est libre.',

        'cpu' => 'Processeur',
        'cpu_help' => 'Pourcentage d\'un cœur : 100 pour 1 vCPU, 200 pour 2 vCPU. 0 pour illimité.',
        'memory' => 'Mémoire',
        'disk' => 'Disque',
        'swap' => 'Swap',
        'swap_help' => 'En MiB. 0 pour aucun swap, -1 pour illimité.',
        'memory_overhead' => 'Marge mémoire',
        'mib_help' => 'En MiB. 1024 MiB font 1 Go.',
        'io_weight' => 'Poids d\'entrées-sorties',
        'io_weight_help' => 'Poids relatif de 0 à 1000. Attention : sans effet si le nœud n\'a pas d\'ordonnanceur compatible (bfq ou iocost), et le panel ne le signale pas. À ne pas facturer sans avoir vérifié la configuration des nœuds.',

        'allocations' => 'Allocations',
        'allocations_help' => 'Nombre de ports attribués au serveur.',
        'databases' => 'Bases de données',
        'backups' => 'Sauvegardes',
        'schedules' => 'Tâches planifiées',
        'backup_retention_days' => 'Conservation des sauvegardes',
        'backup_retention_days_help' => 'Nombre de jours pendant lesquels les sauvegardes du client sont conservées après la résiliation, avant purge automatique. Zéro les conserve indéfiniment, à vos frais de stockage.',

        'image' => 'Image Docker',
        'image_help' => 'Laissez la valeur proposée par l\'egg si vous n\'avez pas de raison précise d\'en changer.',
        'startup' => 'Commande de démarrage',
        'startup_help' => 'Les variables de l\'egg y sont substituées par le panel.',
        'server_name' => 'Nom du serveur',
        'server_name_help' => 'Affiché au client dans le panel. Par défaut, le nom du produit.',
        'server_description' => 'Description du serveur',
        'start_on_completion' => 'Démarrer après l\'installation',
        'skip_installer' => 'Ignorer le script d\'installation',
        'skip_installer_help' => 'À n\'activer que si vous savez pourquoi : le serveur sera livré sans ses fichiers de base.',
    ],
];
