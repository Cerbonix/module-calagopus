<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\DTO;

use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\Http;
use App\Modules\Calagopus\Models\CalagopusConfig;
use Illuminate\Support\Str;

class CalagopusServerDTO
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $uuidShort,
        public readonly string $name,
        public readonly bool $isSuspended,
        public readonly ?string $externalId = null,
        public readonly ?string $status = null,
        public readonly ?string $ip = null,
        public readonly ?int $port = null,
        public readonly ?string $ipAlias = null,
        public readonly array $limits = [],
        public readonly ?string $nodeUuid = null,
    ) {}

    /**
     * The panel reports suspension as is_suspended but accepts it as suspended on PATCH.
     */
    public static function fromArray(array $server): self
    {
        $allocation = $server['allocation'] ?? null;

        return new self(
            uuid: (string) $server['uuid'],
            uuidShort: (string) ($server['uuid_short'] ?? ''),
            name: (string) ($server['name'] ?? ''),
            isSuspended: (bool) ($server['is_suspended'] ?? false),
            externalId: isset($server['external_id']) ? (string) $server['external_id'] : null,
            status: isset($server['status']) ? (string) $server['status'] : null,
            ip: $allocation['ip'] ?? null,
            port: isset($allocation['port']) ? (int) $allocation['port'] : null,
            ipAlias: $allocation['ip_alias'] ?? null,
            limits: is_array($server['limits'] ?? null) ? $server['limits'] : [],
            nodeUuid: $server['node']['uuid'] ?? null,
        );
    }

    public static function findByService(Server $panel, Service $service): ?self
    {
        $response = Http::callApi($panel, 'servers/external/'.$service->id);

        if (! $response->successful() || ! isset($response->json()['server'])) {
            return null;
        }

        return self::fromArray($response->json()['server']);
    }

    public static function deploy(Server $panel, CalagopusConfig $config, CalagopusUserDTO $owner, Service $service): self
    {
        $response = Http::callApi($panel, 'servers/deploy', self::deploymentPayload($config, $owner, $service), 'POST');

        if (! $response->successful() || ! isset($response->json()['server'])) {
            throw new \RuntimeException($response->errorMessage() ?: 'the panel refused the deployment');
        }

        return self::fromArray($response->json()['server']);
    }

    public function address(): ?string
    {
        if ($this->ip === null || $this->port === null) {
            return null;
        }

        return ($this->ipAlias ?: $this->ip).':'.$this->port;
    }

    private static function deploymentPayload(CalagopusConfig $config, CalagopusUserDTO $owner, Service $service): array
    {
        return [
            'deployment' => [
                'location_uuids' => array_values($config->location_uuids ?? []),
                'allow_overallocation' => false,
                'allocations' => $config->allocationDeployment(),
            ],
            'owner_uuid' => $owner->uuid,
            'egg_uuid' => $config->egg_uuid,
            'external_id' => (string) $service->id,
            'name' => self::serverName($config, $service),
            'description' => $config->server_description,
            'limits' => $config->limits(),
            'feature_limits' => $config->featureLimits(),
            'variables' => [],
            'startup' => (string) $config->startup,
            'image' => (string) $config->image,
            'pinned_cpus' => [],
            'timezone' => null,
            'start_on_completion' => (bool) $config->start_on_completion,
            'skip_installer' => (bool) $config->skip_installer,
            'backup_configuration_uuid' => null,
            'allocation_uuid' => null,
            'allocation_uuids' => null,
            'hugepages_passthrough_enabled' => false,
            'kvm_passthrough_enabled' => false,
        ];
    }

    /**
     * The panel rejects names shorter than 3 characters, which a short product name would trigger.
     */
    private static function serverName(CalagopusConfig $config, Service $service): string
    {
        $name = trim((string) ($config->server_name ?: $service->name));

        return Str::limit(str_pad($name, 3, '-'), 255, '');
    }
}
