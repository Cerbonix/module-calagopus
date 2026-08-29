<?php

return [
    'heading' => 'Votre serveur de jeu',
    'address' => 'Adresse de connexion',
    'address_pending' => 'En cours d\'attribution',
    'memory' => 'Mémoire',
    'disk' => 'Disque',
    'cpu' => 'Processeur',
    'cpu_value' => ':value vCPU',
    'cpu_unlimited' => 'Non limité',
    'open_panel' => 'Ouvrir le panel',
    'new_window' => '(s\'ouvre dans un nouvel onglet)',

    'sso' => [
        'unavailable' => 'Le panel n\'est pas joignable pour le moment.',
    ],

    'unit' => [
        'mb' => 'Mo',
        'gb' => 'Go',
    ],

    'state' => [
        'suspended' => 'Ce serveur est suspendu. Il reste en place et ses données sont conservées, mais il est arrêté et injoignable tant que la suspension dure.',
    ],

    'unavailable' => [
        'no_panel' => 'Aucun panel n\'est associé à ce service. Contactez le support.',
        'not_found' => 'Les informations de ce serveur ne sont pas disponibles pour le moment. Si cela persiste, contactez le support.',
    ],

    'backups' => [
        'title' => 'Sauvegardes conservées',
        'intro' => 'Après la résiliation d\'un service, vos sauvegardes sont conservées un temps limité avant d\'être supprimées automatiquement. Elles restent à votre disposition si vous revenez chez nous, ou si la résiliation vient d\'une erreur de manipulation. Vous pouvez aussi demander leur suppression immédiate à tout moment.',
        'scope_note' => 'Il s\'agit des sauvegardes applicatives de votre serveur, celles que vous créez depuis le panel. Les sauvegardes système de notre infrastructure n\'entrent pas dans ce périmètre : réalisées aux seules fins de continuité de service, elles suivent leur propre cycle de vie et sont conservées pour une durée limitée, conformément à la réglementation applicable en matière de protection des données et à notre politique de confidentialité.',
        'empty' => 'Aucune sauvegarde n\'est conservée pour vos services résiliés.',
        'table_caption' => 'Sauvegardes conservées, avec leur date de suppression automatique',
        'name' => 'Sauvegarde',
        'purge_at' => 'Supprimée le',
        'unnamed' => 'Sans nom',
        'actions' => 'Actions',
        'download' => 'Télécharger',
        'download_failed' => 'Le téléchargement n\'est pas disponible pour cette sauvegarde. Réessayez plus tard ou contactez le support.',
        'no_limit' => 'Sans limite de durée',
        'unknown_service' => 'Service supprimé',
        'delete_open' => 'Supprimer cette sauvegarde maintenant|Supprimer ces :count sauvegardes maintenant',
        'delete_warning' => 'La suppression est définitive : ces sauvegardes ne pourront pas être restaurées.',
        'delete_confirm' => 'Oui, supprimer définitivement',
        'deleted' => 'Sauvegarde supprimée.|:count sauvegardes supprimées.',
        'nothing_to_delete' => 'Aucune sauvegarde à supprimer.',
        'partly_failed' => 'Une sauvegarde n\'a pas pu être supprimée. Réessayez plus tard ou contactez le support.|:count sauvegardes n\'ont pas pu être supprimées. Réessayez plus tard ou contactez le support.',
    ],

    'retention' => [
        'notice' => 'Si vous résiliez ce service, vos sauvegardes seront conservées :days jours avant suppression automatique.',
        'notice_unlimited' => 'Si vous résiliez ce service, vos sauvegardes seront conservées sans limite de durée.',
        'manage' => 'Gérer mes sauvegardes conservées',
        'heading' => 'Vos sauvegardes en cas de résiliation',
        'keep_days' => 'Conserver mes sauvegardes :days jours après la résiliation',
        'keep_unlimited' => 'Conserver mes sauvegardes sans limite de durée',
        'discard' => 'Supprimer mes sauvegardes dès la résiliation',
        'save' => 'Enregistrer ce choix',
        'saved' => 'Votre choix a été enregistré.',
    ],
];
