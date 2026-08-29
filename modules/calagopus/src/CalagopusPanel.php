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
use App\Modules\Calagopus\Models\CalagopusConfig as ConfigModel;

class CalagopusPanel extends AbstractPanelProvisioning
{
    protected string $uuid = 'calagopus';

    /**
     * Shown to staff, so it carries what support actually needs to act: identifiers, node, and the raw state.
     */
    public function renderAdmin(Service $service)
    {
        $panel = $service->server;

        if (! $panel instanceof Server) {
            return view('calagopus_admin::panel', ['server' => null, 'panelUrl' => null, 'reason' => 'no_panel']);
        }

        $server = CalagopusServerDTO::findByService($panel, $service);

        if ($server === null) {
            return view('calagopus_admin::panel', ['server' => null, 'panelUrl' => null, 'reason' => 'not_found']);
        }

        return view('calagopus_admin::panel', [
            'server' => $server,
            'panelUrl' => Http::publicUrl($panel).'/server/'.$server->uuid,
            'reason' => null,
        ]);
    }

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
            'panelUrl' => Http::publicUrl($panel).'/server/'.$server->uuid,
            'ssoUrl' => route('calagopus.sso', ['service' => $service]),
            'retentionDays' => (int) (ConfigModel::where('product_id', $service->product_id)->value('backup_retention_days') ?? 0),
        ]);
    }
}
