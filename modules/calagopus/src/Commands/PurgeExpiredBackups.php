<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\Commands;

use App\Modules\Calagopus\BackupPurger;
use App\Modules\Calagopus\Models\CalagopusBackupPurge;
use Illuminate\Console\Command;

class PurgeExpiredBackups extends Command
{
    protected $signature = 'calagopus:purge-backups {--limit=100 : Maximum backups handled in one run} {--dry-run : List what would be deleted without deleting anything}';

    protected $description = 'Delete Calagopus backups of terminated services whose retention has elapsed.';

    public function handle(BackupPurger $purger): int
    {
        $due = CalagopusBackupPurge::due()
            ->with('server')
            ->orderBy('purge_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        if ($due->isEmpty()) {
            $this->info('Nothing due.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $orphaned = 0;
        $failed = 0;

        foreach ($due as $entry) {
            if ($this->option('dry-run')) {
                $this->line(sprintf('would delete %s (due %s)', $entry->backup_uuid, $entry->purge_at));

                continue;
            }

            $outcome = $purger->purge($entry);

            if ($outcome === BackupPurger::FAILED) {
                $this->warn(sprintf('backup %s kept, will be retried', $entry->backup_uuid));
            }

            match ($outcome) {
                BackupPurger::DELETED => $deleted++,
                BackupPurger::ORPHANED => $orphaned++,
                default => $failed++,
            };
        }

        $this->info(sprintf('%d deleted, %d already gone, %d left for the next run.', $deleted, $orphaned, $failed));

        return self::SUCCESS;
    }
}
