<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Abstracts\AbstractServerType;
use App\DTO\Provisioning\ConnectionResponse;
use App\Models\Provisioning\Server;
use GuzzleHttp\Psr7\Response as PsrResponse;

class CalagopusServerType extends AbstractServerType
{
    public const SUPPORTED_MIN = '1.1.4';

    public const SUPPORTED_BELOW = '1.2.0';

    /** Endpoints read to prove the key actually carries each permission it needs. */
    private const REQUIRED_READS = [
        'users?per_page=1' => 'users.read',
        'servers?per_page=1' => 'servers.read',
        'locations?per_page=1' => 'locations.read',
        'nests/eggs' => 'eggs.read',
    ];

    protected string $uuid = 'calagopus';

    protected string $title = 'Calagopus';

    public function validate(): array
    {
        return [
            'hostname' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'password' => ['required', 'string', 'size:48'],
        ];
    }

    public function testConnection(array $params): ConnectionResponse
    {
        $server = new Server;
        $server->fill($params);

        if (trim((string) $server->password) === '') {
            return $this->result(false, $this->trans('empty_key'));
        }

        $overview = Http::callApi($server, 'system/overview');

        if (! $overview->successful()) {
            return $this->result(false, $this->trans($overview->failureKey(), [
                'detail' => $overview->errorMessage(),
            ]));
        }

        $missing = $this->missingPermissions($server);

        if ($missing !== []) {
            return $this->result(false, $this->trans('missing_permissions', [
                'permissions' => implode(', ', $missing),
            ]));
        }

        $version = (string) ($overview->json()['version'] ?? '');

        return $this->result(true, $this->successMessage($version));
    }

    private function missingPermissions(Server $server): array
    {
        $missing = [];

        foreach (self::REQUIRED_READS as $endpoint => $permission) {
            if (! Http::callApi($server, $endpoint)->successful()) {
                $missing[] = $permission;
            }
        }

        return $missing;
    }

    private function successMessage(string $version): string
    {
        if ($version === '') {
            return $this->trans('ok_unknown_version');
        }

        $supported = version_compare($version, self::SUPPORTED_MIN, '>=')
            && version_compare($version, self::SUPPORTED_BELOW, '<');

        return $this->trans($supported ? 'ok' : 'ok_untested_version', [
            'version' => $version,
            'min' => self::SUPPORTED_MIN,
            'below' => self::SUPPORTED_BELOW,
        ]);
    }

    private function trans(string $key, array $replace = []): string
    {
        return __('calagopus::messages.connection.'.$key, $replace);
    }

    private function result(bool $successful, string $message): ConnectionResponse
    {
        return new ConnectionResponse(new PsrResponse($successful ? 200 : 400, [], $message));
    }
}
