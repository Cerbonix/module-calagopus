<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Abstracts\AbstractPanelProvisioning;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\DTO\CalagopusServerDTO;

class CalagopusPanel extends AbstractPanelProvisioning
{
    protected string $uuid = 'calagopus';

    /**
     * Ownership is already enforced by the core before this runs (Front\Provisioning\ServiceController::show).
     */
    public function render(Service $service, array $permissions = [])
    {
        $panel = $service->server;

        if (! $panel instanceof Server) {
            return view('calagopus::panel.unavailable', ['reason' => 'no_panel']);
        }

        $server = CalagopusServerDTO::findByService($panel, $service);

        if ($server === null) {
            return view('calagopus::panel.unavailable', ['reason' => 'not_found']);
        }

        return view('calagopus::panel.index', [
            'service' => $service,
            'server' => $server,
            'panelUrl' => Http::baseUrl($panel).'/server/'.$server->uuid,
        ]);
    }
}
