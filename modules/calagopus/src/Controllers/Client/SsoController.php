<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\DTO\CalagopusServerDTO;
use App\Modules\Calagopus\Http;

class SsoController extends Controller
{
    /**
     * The core guards its own service pages, but this route is ours, so the ownership check is ours too.
     */
    public function redirect(Service $service)
    {
        $customer = auth('web')->user();

        if ($customer === null || ! $customer->hasServicePermission($service, 'service.show')) {
            abort(404);
        }

        // Not gated on the active status: a cancelled service still runs until it expires, and a missing server falls back on its own below.
        abort_if($service->type !== 'calagopus', 404);

        $panel = $service->server;

        if (! $panel instanceof Server) {
            return $this->fallback($service, null);
        }

        $server = CalagopusServerDTO::findByService($panel, $service);

        if ($server === null) {
            return $this->fallback($service, null);
        }

        $ticket = $this->requestTicket($panel, $service);

        if ($ticket === null) {
            return $this->fallback($service, $server->uuid);
        }

        return redirect()->away($ticket);
    }

    private function requestTicket(Server $panel, Service $service): ?string
    {
        $secret = trim((string) $panel->username);

        if ($secret === '') {
            return null;
        }

        $owner = Http::callApi($panel, 'users/external/'.$service->customer_id);

        if (! $owner->successful() || ! isset($owner->json()['user']['uuid'])) {
            return null;
        }

        $response = Http::callPanel($panel, 'auth/ssotickets', [
            'secret' => $secret,
            'user_uuid' => $owner->json()['user']['uuid'],
        ]);

        $token = $response->json()['token'] ?? null;

        return is_string($token) && $token !== ''
            ? Http::publicUrl($panel).'/api/auth/ssotickets/'.rawurlencode($token)
            : null;
    }

    /**
     * A failed ticket must never strand the customer: the plain panel link still works, they just sign in themselves.
     */
    private function fallback(Service $service, ?string $serverUuid)
    {
        $panel = $service->server;

        if (! $panel instanceof Server) {
            return redirect()->route('front.services.show', $service)
                ->with('error', __('calagopus::client.sso.unavailable'));
        }

        $url = Http::publicUrl($panel).($serverUuid !== null ? '/server/'.$serverUuid : '');

        return redirect()->away($url);
    }
}
