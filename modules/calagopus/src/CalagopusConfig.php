<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Abstracts\AbstractConfig;
use App\Models\Provisioning\Server;
use App\Models\Store\Product;
use App\Modules\Calagopus\Models\CalagopusConfig as ConfigModel;
use Illuminate\Support\Facades\Cache;

class CalagopusConfig extends AbstractConfig
{
    private const CACHE_TTL = 300;

    protected string $model = ConfigModel::class;

    protected string $type = 'calagopus';

    public function render(Product $product)
    {
        $config = $this->getConfig($product->id, new ConfigModel);
        $server = $this->serverFor($config);

        return view('calagopus_admin::product-config', [
            'product' => $product,
            'config' => $config,
            'servers' => $this->servers->pluck('name', 'id'),
            'eggs' => $server ? $this->fetchEggs($server) : [],
            'locations' => $server ? $this->fetchLocations($server) : [],
            'currentLocations' => $config->location_uuids ?? [],
        ]);
    }

    public function validate(): array
    {
        return [
            'server_id' => 'required|numeric',
            'egg_uuid' => 'required|uuid',
            'location_uuids' => 'required|array|min:1',
            'location_uuids.*' => 'uuid',
            'port_start' => 'required|numeric|between:1,65535',
            'port_end' => 'required|numeric|between:1,65535|gte:port_start',
            'dedicated_ip' => 'boolean',
            'cpu' => 'required|numeric|min:0',
            'memory' => 'required|numeric|min:0',
            'memory_overhead' => 'required|numeric|min:0',
            'swap' => 'required|numeric|min:-1',
            'disk' => 'required|numeric|min:0',
            'io_weight' => 'nullable|numeric|between:0,1000',
            'allocations' => 'required|numeric|min:0',
            'databases' => 'required|numeric|min:0',
            'backups' => 'required|numeric|min:0',
            'schedules' => 'required|numeric|min:0',
            'image' => 'required|string|between:2,255',
            'startup' => 'required|string|between:1,8192',
            'server_name' => 'nullable|string|max:255',
            'server_description' => 'nullable|string|max:1024',
            'start_on_completion' => 'boolean',
            'skip_installer' => 'boolean',
        ];
    }

    public function storeConfig(Product $product, array $parameters)
    {
        $this->model::insert($this->encode($parameters) + ['product_id' => $product->id]);
    }

    public function updateConfig(Product $product, array $parameters)
    {
        $this->model::where('product_id', $product->id)->update($this->encode($parameters));
    }

    /**
     * Reads every egg in one call, which the panel supports, instead of one call per nest.
     */
    public function fetchEggs(Server $server): array
    {
        return Cache::remember("calagopus.eggs.{$server->id}", self::CACHE_TTL, function () use ($server) {
            $response = Http::callApi($server, 'nests/eggs');
            $eggs = [];

            foreach ($response->json()['nests'] ?? [] as $group) {
                $nest = $group['nest']['name'] ?? '';
                foreach ($group['eggs'] ?? [] as $egg) {
                    if (isset($egg['uuid'], $egg['name'])) {
                        $eggs[$egg['uuid']] = trim($nest.' / '.$egg['name'], ' /');
                    }
                }
            }

            return $eggs;
        });
    }

    public function fetchLocations(Server $server): array
    {
        return Cache::remember("calagopus.locations.{$server->id}", self::CACHE_TTL, function () use ($server) {
            $response = Http::callApi($server, 'locations?page=1&per_page=100');
            $locations = [];

            foreach ($response->json()['locations']['data'] ?? [] as $location) {
                if (isset($location['uuid'], $location['name'])) {
                    $locations[$location['uuid']] = $location['name'];
                }
            }

            return $locations;
        });
    }

    private function serverFor(ConfigModel $config): ?Server
    {
        if ($config->server_id !== null) {
            return $this->servers->firstWhere('id', $config->server_id) ?? $this->servers->first();
        }

        return $this->servers->first();
    }

    private function encode(array $parameters): array
    {
        if (isset($parameters['location_uuids']) && is_array($parameters['location_uuids'])) {
            $parameters['location_uuids'] = json_encode(array_values($parameters['location_uuids']));
        }

        return $parameters;
    }
}
