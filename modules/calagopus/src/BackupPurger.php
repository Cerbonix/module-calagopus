<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Modules\Calagopus\Models\CalagopusBackupPurge;
use Illuminate\Support\Collection;

class BackupPurger
{
    public const DELETED = 'deleted';

    public const ORPHANED = 'orphaned';

    public const FAILED = 'failed';

    /**
     * A backup the panel no longer knows is a success for us: the goal is that it stops existing.
     */
    public function purge(CalagopusBackupPurge $entry): string
    {
        $panel = $entry->server;

        if ($panel === null) {
            $entry->delete();

            return self::ORPHANED;
        }

        $response = Http::callApi($panel, "nodes/{$entry->node_uuid}/backups/{$entry->backup_uuid}", [], 'DELETE');

        if ($response->successful() || $response->status() === 404) {
            $entry->delete();

            return $response->successful() ? self::DELETED : self::ORPHANED;
        }

        return self::FAILED;
    }

    /**
     * @return array{deleted: int, orphaned: int, failed: int}
     */
    public function purgeAll(Collection $entries): array
    {
        $tally = [self::DELETED => 0, self::ORPHANED => 0, self::FAILED => 0];

        foreach ($entries as $entry) {
            $tally[$this->purge($entry)]++;
        }

        return $tally;
    }
}
