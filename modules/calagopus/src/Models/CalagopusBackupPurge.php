<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\Models;

use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\DTO\CalagopusServerDTO;
use App\Modules\Calagopus\Http;
use Illuminate\Database\Eloquent\Model;

class CalagopusBackupPurge extends Model
{
    private const PER_PAGE = 100;

    private const MAX_PAGES = 50;

    protected $table = 'calagopus_backup_purges';

    protected $fillable = [
        'server_id',
        'service_id',
        'node_uuid',
        'backup_uuid',
        'backup_name',
        'purge_at',
    ];

    protected $casts = [
        'purge_at' => 'datetime',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeDue($query)
    {
        return $query->where('purge_at', '<=', now());
    }

    /**
     * Must run while the server still exists: once deleted, its backups no longer name it.
     */
    public static function recordFor(Server $panel, CalagopusServerDTO $server, Service $service, int $retentionDays): int
    {
        if ($retentionDays <= 0 || $server->nodeUuid === null) {
            return 0;
        }

        $purgeAt = now()->addDays($retentionDays);
        $recorded = 0;

        foreach (self::readBackups($panel, $server) as $backup) {
            if (! isset($backup['uuid'])) {
                continue;
            }

            self::updateOrCreate(['backup_uuid' => $backup['uuid']], [
                'server_id' => $panel->id,
                'service_id' => $service->id,
                'node_uuid' => $server->nodeUuid,
                'backup_name' => $backup['name'] ?? null,
                'purge_at' => $purgeAt,
            ]);

            $recorded++;
        }

        return $recorded;
    }

    private static function readBackups(Server $panel, CalagopusServerDTO $server): array
    {
        $all = [];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = Http::callApi($panel, "servers/{$server->uuid}/backups?page={$page}&per_page=".self::PER_PAGE);

            if (! $response->successful()) {
                break;
            }

            $batch = $response->json()['backups']['data'] ?? [];
            $all = array_merge($all, $batch);

            if (count($batch) < self::PER_PAGE) {
                break;
            }
        }

        return $all;
    }
}
