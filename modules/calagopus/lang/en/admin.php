<?php

return [
    'config' => [
        'server' => 'Calagopus panel',
        'egg' => 'Egg',
        'egg_help' => 'Game server template. The list is loaded from the selected panel.',
        'locations' => 'Locations',
        'locations_help' => 'The panel picks the node and the port itself among these locations. At least one is required, otherwise the order will fail.',

        'port_start' => 'First port',
        'port_end' => 'Last port',
        'port_range_help' => 'Range the panel picks the server port from. Without a range no port is assigned at all and the server ships unreachable.',
        'dedicated_ip' => 'Dedicated IP',
        'dedicated_ip_help' => 'Requires an address carrying no other server. The order fails if none is free.',

        'cpu' => 'CPU',
        'cpu_help' => 'Percentage of one core: 100 for 1 vCPU, 200 for 2 vCPU. 0 for unlimited.',
        'memory' => 'Memory',
        'disk' => 'Disk',
        'swap' => 'Swap',
        'swap_help' => 'In MiB. 0 for no swap, -1 for unlimited.',
        'memory_overhead' => 'Memory overhead',
        'mib_help' => 'In MiB. 1024 MiB is 1 GB.',
        'io_weight' => 'I/O weight',
        'io_weight_help' => 'Relative weight from 0 to 1000. Careful: it has no effect unless the node runs a compatible I/O scheduler (bfq or iocost), and the panel will not tell you. Do not bill it without checking your node configuration.',

        'allocations' => 'Allocations',
        'allocations_help' => 'Number of ports assigned to the server.',
        'databases' => 'Databases',
        'backups' => 'Backups',
        'schedules' => 'Schedules',

        'image' => 'Docker image',
        'image_help' => 'Keep the value suggested by the egg unless you have a specific reason to change it.',
        'startup' => 'Startup command',
        'startup_help' => 'Egg variables are substituted into it by the panel.',
        'server_name' => 'Server name',
        'server_name_help' => 'Shown to the customer in the panel. Defaults to the product name.',
        'server_description' => 'Server description',
        'start_on_completion' => 'Start after installation',
        'skip_installer' => 'Skip the install script',
        'skip_installer_help' => 'Only enable this if you know why: the server will be delivered without its base files.',
    ],
];
