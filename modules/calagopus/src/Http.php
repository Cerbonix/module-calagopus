<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Models\Provisioning\Server;
use Illuminate\Support\Facades\Http as HttpClient;

class Http
{
    private const TIMEOUT = 10;

    public static function callApi(Server $server, string $endpoint, array $data = [], string $method = 'GET'): CalagopusResponse
    {
        $url = self::baseUrl($server).'/api/admin/'.ltrim($endpoint, '/');

        try {
            $response = HttpClient::withToken(self::apiKey($server))
                ->acceptJson()
                ->timeout(self::TIMEOUT)
                ->send($method, $url, ['json' => $data]);

            return CalagopusResponse::fromResponse($response);
        } catch (\Throwable $e) {
            return CalagopusResponse::unreachable($e->getMessage());
        }
    }

    public static function baseUrl(Server $server): string
    {
        $hostname = rtrim($server->hostname, '/');

        if (str_starts_with($hostname, 'http://') || str_starts_with($hostname, 'https://')) {
            return $hostname;
        }

        return ($server->port == 443 ? 'https' : 'http').'://'.$hostname;
    }

    private static function apiKey(Server $server): string
    {
        return trim((string) $server->password);
    }
}
