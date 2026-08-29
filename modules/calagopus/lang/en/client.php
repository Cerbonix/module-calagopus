<?php

return [
    'heading' => 'Your game server',
    'address' => 'Connection address',
    'address_pending' => 'Being assigned',
    'memory' => 'Memory',
    'disk' => 'Disk',
    'cpu' => 'CPU',
    'cpu_value' => ':value vCPU',
    'cpu_unlimited' => 'Unlimited',
    'open_panel' => 'Open the panel',
    'new_window' => '(opens in a new tab)',

    'sso' => [
        'unavailable' => 'The panel is not reachable right now.',
    ],

    'unit' => [
        'mb' => 'MB',
        'gb' => 'GB',
    ],

    'state' => [
        'suspended' => 'This server is suspended. It stays in place and its data is kept, but it is stopped and unreachable while the suspension lasts.',
    ],

    'unavailable' => [
        'no_panel' => 'No panel is attached to this service. Please contact support.',
        'not_found' => 'This server\'s details are not available right now. If it persists, please contact support.',
    ],

    'backups' => [
        'title' => 'Kept backups',
        'intro' => 'After a service is terminated, your backups are kept for a limited time before being deleted automatically. They stay available to you if you come back, or if the termination was a mistake. You can also ask for them to be deleted right away at any point.',
        'scope_note' => 'These are the application backups of your server, the ones you create from the panel. System backups of our own infrastructure are outside this scope: they follow our infrastructure lifecycle, in line with the guidance of the French data protection authority and with our privacy policy.',
        'empty' => 'No backup is being kept for your terminated services.',
        'table_caption' => 'Kept backups, with their automatic deletion date',
        'name' => 'Backup',
        'purge_at' => 'Deleted on',
        'unnamed' => 'Unnamed',
        'no_limit' => 'No time limit',
        'unknown_service' => 'Deleted service',
        'delete_open' => 'Delete this backup now|Delete these :count backups now',
        'delete_warning' => 'Deletion is final: these backups cannot be restored.',
        'delete_confirm' => 'Yes, delete permanently',
        'deleted' => 'Backup deleted.|:count backups deleted.',
        'nothing_to_delete' => 'No backup to delete.',
        'partly_failed' => 'One backup could not be deleted. Try again later or contact support.|:count backups could not be deleted. Try again later or contact support.',
    ],

    'retention' => [
        'notice' => 'If you terminate this service, your backups will be kept for :days days before automatic deletion.',
        'notice_unlimited' => 'If you terminate this service, your backups will be kept with no time limit.',
        'manage' => 'Manage my kept backups',
    ],
];
