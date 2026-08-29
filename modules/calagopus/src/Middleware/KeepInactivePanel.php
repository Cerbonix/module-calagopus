<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\Middleware;

use App\Models\Provisioning\Service;
use App\Modules\Calagopus\InactiveServicePanel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeepInactivePanel
{
    /**
     * The live refresh re-asks the core for the panel every few seconds and gets an empty string, which would wipe what the composer just drew.
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse || $request->route()?->getName() !== 'front.services.status') {
            return $response;
        }

        $payload = $response->getData(true);

        if (($payload['panel_html'] ?? null) !== '') {
            return $response;
        }

        $service = $request->route('service');

        if (! $service instanceof Service) {
            return $response;
        }

        $html = app(InactiveServicePanel::class)->htmlFor($service);

        if ($html === null) {
            return $response;
        }

        $payload['panel_html'] = $html;

        return $response->setData($payload);
    }
}
