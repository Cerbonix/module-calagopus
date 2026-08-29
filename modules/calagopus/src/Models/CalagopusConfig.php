<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\Models;

use App\Models\Provisioning\Server;
use App\Models\Store\Product;
use Illuminate\Database\Eloquent\Model;

class CalagopusConfig extends Model
{
    protected $table = 'calagopus_configs';

    protected $fillable = [
        'product_id',
        'server_id',
        'egg_uuid',
        'location_uuids',
        'port_start',
        'port_end',
        'dedicated_ip',
        'cpu',
        'memory',
        'memory_overhead',
        'swap',
        'disk',
        'io_weight',
        'allocations',
        'databases',
        'backups',
        'schedules',
        'image',
        'startup',
        'server_name',
        'server_description',
        'start_on_completion',
        'skip_installer',
    ];

    protected $casts = [
        'location_uuids' => 'array',
        'start_on_completion' => 'boolean',
        'skip_installer' => 'boolean',
        'dedicated_ip' => 'boolean',
    ];

    protected $attributes = [
        'port_start' => 25565,
        'port_end' => 25595,
        'dedicated_ip' => false,
        'cpu' => 100,
        'memory' => 1024,
        'memory_overhead' => 0,
        'swap' => 0,
        'disk' => 5120,
        'allocations' => 1,
        'databases' => 0,
        'backups' => 0,
        'schedules' => 0,
        'start_on_completion' => true,
        'skip_installer' => false,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function limits(): array
    {
        return [
            'cpu' => (int) $this->cpu,
            'memory' => (int) $this->memory,
            'memory_overhead' => (int) $this->memory_overhead,
            'swap' => (int) $this->swap,
            'disk' => (int) $this->disk,
            'io_weight' => $this->io_weight === null ? null : (int) $this->io_weight,
        ];
    }

    /**
     * Left null the panel assigns no allocation at all, so the range is always sent.
     */
    public function allocationDeployment(): array
    {
        return [
            'dedicated' => (bool) $this->dedicated_ip,
            'primary' => [
                'start_port' => (int) $this->port_start,
                'end_port' => (int) $this->port_end,
                'assign_to_variable' => null,
            ],
            'additional' => [],
        ];
    }

    public function featureLimits(): array
    {
        return [
            'allocations' => (int) $this->allocations,
            'databases' => (int) $this->databases,
            'backups' => (int) $this->backups,
            'schedules' => (int) $this->schedules,
        ];
    }
}
