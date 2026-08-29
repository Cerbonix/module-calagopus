<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Contracts\Provisioning\ImportServiceInterface;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\DTO\CalagopusServerDTO;

class ImportServiceCalagopus implements ImportServiceInterface
{
    private const PER_PAGE = 100;

    private const MAX_PAGES = 20;

    public function validate(): array
    {
        return [
            'calagopus_server_uuid' => ['required', 'string', 'between:8,36'],
        ];
    }

    public function render(Service $service, array $data = [])
    {
        $panel = $service->server;

        if (! $panel instanceof Server) {
            return view('calagopus_admin::import', ['service' => $service, 'servers' => [], 'unreachable' => true]);
        }

        return view('calagopus_admin::import', [
            'service' => $service,
            'servers' => $this->importableServers($panel, $service),
            'unreachable' => false,
        ]);
    }

    public function import(Service $service, array $data = []): ServiceStateChangeDTO
    {
        $panel = $service->server;

        if (! $panel instanceof Server) {
            return $this->result($service, false, 'no_panel');
        }

        $identifier = (string) ($data['calagopus_server_uuid'] ?? '');
        $response = Http::callApi($panel, 'servers/'.rawurlencode($identifier));

        if (! $response->successful() || ! isset($response->json()['server'])) {
            return $this->result($service, false, 'unknown_server');
        }

        $server = CalagopusServerDTO::fromArray($response->json()['server']);
        $owner = $server->externalId;

        if ($owner === (string) $service->id) {
            return $this->result($service, true, 'already_linked');
        }

        // ADR-03: external_id is the reconciliation key, so stealing one already in use would orphan the other service.
        if ($owner !== null && $owner !== '') {
            return $this->result($service, false, 'taken', ['service' => $owner]);
        }

        $patch = Http::callApi($panel, 'servers/'.$server->uuid, ['external_id' => (string) $service->id], 'PATCH');

        if (! $patch->successful()) {
            return $this->result($service, false, 'panel_error', ['detail' => $patch->errorMessage()]);
        }

        return $this->result($service, true, 'imported', ['uuid' => $server->uuid]);
    }

    /**
     * Servers already bound to another service are listed as such rather than hidden, so the admin understands why one is missing.
     */
    private function importableServers(Server $panel, Service $service): array
    {
        $servers = [];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = Http::callApi($panel, 'servers?page='.$page.'&per_page='.self::PER_PAGE);

            if (! $response->successful()) {
                break;
            }

            $batch = $response->json()['servers']['data'] ?? [];

            foreach ($batch as $raw) {
                if (! isset($raw['uuid'], $raw['name'])) {
                    continue;
                }

                $external = isset($raw['external_id']) ? (string) $raw['external_id'] : '';
                $free = $external === '' || $external === (string) $service->id;

                $servers[$raw['uuid']] = $free
                    ? $raw['name']
                    : __('calagopus::admin.import.taken_option', ['name' => $raw['name'], 'service' => $external]);
            }

            if (count($batch) < self::PER_PAGE) {
                break;
            }
        }

        return $servers;
    }

    private function result(Service $service, bool $success, string $key, array $data = []): ServiceStateChangeDTO
    {
        return new ServiceStateChangeDTO($service, $success, __('calagopus::admin.import.'.$key, [
            'service' => $data['service'] ?? '',
            'detail' => $data['detail'] ?? '',
        ]), $data);
    }
}
