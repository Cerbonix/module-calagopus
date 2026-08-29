<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Models\Provisioning\Server;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http as HttpClient;

class Http
{
    private const TIMEOUT = 10;

    private const PUBLIC_URL_TTL = 300;

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

    /**
     * The address ClientXCMS calls is not always the one a browser can open, so the panel is asked where it thinks it lives.
     */
    public static function publicUrl(Server $server): string
    {
        return Cache::remember("calagopus.public_url.{$server->id}", self::PUBLIC_URL_TTL, function () use ($server) {
            try {
                $response = HttpClient::acceptJson()->timeout(self::TIMEOUT)->get(self::baseUrl($server).'/api/settings');
                $url = $response->successful() ? (string) ($response->json()['app']['url'] ?? '') : '';
            } catch (\Throwable) {
                $url = '';
            }

            return rtrim($url, '/') ?: self::baseUrl($server);
        });
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
