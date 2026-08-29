<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Abstracts\AbstractServerType;
use App\DTO\Provisioning\ConnectionResponse;
use App\DTO\Provisioning\ServiceStateChangeDTO;
use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\DTO\CalagopusServerDTO;
use App\Modules\Calagopus\DTO\CalagopusUserDTO;
use App\Modules\Calagopus\Models\CalagopusConfig as ConfigModel;
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

    public function createAccount(Service $service): ServiceStateChangeDTO
    {
        return $this->perform($service, function (Server $panel, ConfigModel $config) use ($service) {
            if (CalagopusServerDTO::findByService($panel, $service) !== null) {
                return $this->lifecycle($service, true, 'already_provisioned');
            }

            $owner = CalagopusUserDTO::resolve($panel, $service->customer);
            $server = CalagopusServerDTO::deploy($panel, $config, $owner, $service);

            return $this->lifecycle($service, true, 'created', [
                'uuid' => $server->uuid,
                'address' => $server->address(),
                'panel_username' => $owner->username,
                'panel_password' => $owner->password,
                'account_created' => $owner->wasCreated,
            ]);
        });
    }

    public function suspendAccount(Service $service): ServiceStateChangeDTO
    {
        return $this->patchServer($service, ['suspended' => true], 'suspended');
    }

    public function unsuspendAccount(Service $service): ServiceStateChangeDTO
    {
        return $this->patchServer($service, ['suspended' => false], 'unsuspended');
    }

    public function onRenew(Service $service): ServiceStateChangeDTO
    {
        return $this->perform($service, function (Server $panel) use ($service) {
            $server = CalagopusServerDTO::findByService($panel, $service);

            if ($server === null) {
                return $this->lifecycle($service, false, 'not_found');
            }

            if (! $server->isSuspended) {
                return $this->lifecycle($service, true, 'nothing_to_do');
            }

            return $this->applyPatch($panel, $service, $server, ['suspended' => false], 'unsuspended');
        });
    }

    public function changeCustomer(Service $service, Customer $customer): ServiceStateChangeDTO
    {
        return $this->perform($service, function (Server $panel) use ($service, $customer) {
            $server = CalagopusServerDTO::findByService($panel, $service);

            if ($server === null) {
                return $this->lifecycle($service, false, 'not_found');
            }

            $owner = CalagopusUserDTO::resolve($panel, $customer);

            return $this->applyPatch($panel, $service, $server, ['owner_uuid' => $owner->uuid], 'customer_changed');
        });
    }

    public function expireAccount(Service $service): ServiceStateChangeDTO
    {
        return $this->perform($service, function (Server $panel) use ($service) {
            $server = CalagopusServerDTO::findByService($panel, $service);

            if ($server === null) {
                return $this->lifecycle($service, true, 'already_gone');
            }

            // delete_backups stays false: customer backups are never destroyed by an expiry without an explicit product decision.
            $response = Http::callApi($panel, 'servers/'.$server->uuid, ['force' => false, 'delete_backups' => false], 'DELETE');

            if (! $response->successful()) {
                return $this->lifecycle($service, false, 'panel_error', ['detail' => $response->errorMessage()]);
            }

            return $this->lifecycle($service, true, 'terminated');
        });
    }

    public function getSupportedOptions(): array
    {
        return [
            'additional_cpu' => __('provisioning.admin.configoptions.keys.additional_cpu'),
            'additional_memory' => __('provisioning.admin.configoptions.keys.additional_memory'),
            'additional_disk' => __('provisioning.admin.configoptions.keys.additional_disk'),
            'additional_swap' => __('provisioning.admin.configoptions.keys.additional_swap'),
            'additional_databases' => __('provisioning.admin.configoptions.keys.additional_databases'),
            'additional_backups' => __('provisioning.admin.configoptions.keys.additional_backups'),
            'additional_allocations' => __('provisioning.admin.configoptions.keys.additional_allocations'),
        ];
    }

    private function patchServer(Service $service, array $payload, string $messageKey): ServiceStateChangeDTO
    {
        return $this->perform($service, function (Server $panel) use ($service, $payload, $messageKey) {
            $server = CalagopusServerDTO::findByService($panel, $service);

            if ($server === null) {
                return $this->lifecycle($service, false, 'not_found');
            }

            return $this->applyPatch($panel, $service, $server, $payload, $messageKey);
        });
    }

    private function applyPatch(Server $panel, Service $service, CalagopusServerDTO $server, array $payload, string $messageKey): ServiceStateChangeDTO
    {
        $response = Http::callApi($panel, 'servers/'.$server->uuid, $payload, 'PATCH');

        if (! $response->successful()) {
            return $this->lifecycle($service, false, 'panel_error', ['detail' => $response->errorMessage()]);
        }

        return $this->lifecycle($service, true, $messageKey);
    }

    /**
     * Holds the type guard, the panel lookup and the promise that no exception ever escapes the contract.
     */
    private function perform(Service $service, callable $action): ServiceStateChangeDTO
    {
        if ($service->type !== $this->uuid) {
            return $this->lifecycle($service, false, 'wrong_type');
        }

        $panel = $service->server;

        if (! $panel instanceof Server) {
            return $this->lifecycle($service, false, 'no_panel');
        }

        $config = ConfigModel::where('product_id', $service->product_id)->first();

        if ($config === null) {
            return $this->lifecycle($service, false, 'no_config');
        }

        try {
            return $action($panel, $config);
        } catch (\Throwable $e) {
            return $this->lifecycle($service, false, 'panel_error', ['detail' => $e->getMessage()]);
        }
    }

    private function lifecycle(Service $service, bool $success, string $key, array $data = []): ServiceStateChangeDTO
    {
        $message = __('calagopus::messages.lifecycle.'.$key, ['detail' => $data['detail'] ?? '']);

        return new ServiceStateChangeDTO($service, $success, $message, $data);
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
