<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Models\Provisioning\Service;
use App\Modules\Calagopus\Models\CalagopusBackupPurge;
use Illuminate\View\View;

class KeptBackupsNotice
{
    public static function compose(View $view): void
    {
        $service = $view->getData()['service'] ?? null;

        if (! $service instanceof Service || $service->type !== 'calagopus' || $service->isActivated()) {
            return;
        }

        $kept = CalagopusBackupPurge::where('service_id', $service->id)->orderBy('purge_at');
        $count = $kept->count();

        if ($count === 0) {
            return;
        }

        $view->with('panel_html', ($view->getData()['panel_html'] ?? '').view('calagopus::panel.terminated', [
            'count' => $count,
            'purgeAt' => $kept->first()->purge_at,
        ])->render());
    }
}
