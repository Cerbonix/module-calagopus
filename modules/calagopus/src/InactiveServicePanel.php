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

class InactiveServicePanel
{
    private const UNAVAILABLE = 'calagopus::panel.unavailable';

    /**
     * The core blanks the panel as soon as a service leaves the active state, which cuts a customer off a server they still paid for.
     */
    public function compose(View $view): void
    {
        $service = $view->getData()['service'] ?? null;

        if (! $service instanceof Service || $service->type !== 'calagopus' || $service->isActivated()) {
            return;
        }

        $rendered = (new CalagopusPanel)->render($service);

        if ($rendered->name() === self::UNAVAILABLE) {
            $rendered = $this->keptBackups($service);
        }

        if ($rendered === null) {
            return;
        }

        $view->with('panel_html', ($view->getData()['panel_html'] ?? '').$rendered->render());
    }

    /** Once the server is gone, the only thing left worth showing is the way back to the backups we kept. */
    private function keptBackups(Service $service): ?View
    {
        $kept = CalagopusBackupPurge::where('service_id', $service->id)->orderBy('purge_at');
        $count = $kept->count();

        if ($count === 0) {
            return null;
        }

        return view('calagopus::panel.terminated', [
            'count' => $count,
            'purgeAt' => $kept->first()->purge_at,
        ]);
    }
}
