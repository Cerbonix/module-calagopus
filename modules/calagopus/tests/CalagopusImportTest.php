<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\ImportServiceCalagopus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalagopusImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Extensions\ExtensionManager::writeExtensionJson([
            'modules' => [[
                'uuid' => 'calagopus',
                'version' => '0.1.0',
                'type' => 'module',
                'installed' => true,
                'enabled' => true,
                'api' => ['providers' => [['provider' => 'App\Modules\Calagopus\CalagopusServiceProvider']]],
            ]],
        ]);

        app('extension')->autoload(app());
        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_it_attaches_a_free_server_to_the_service(): void
    {
        $service = $this->service();
        Http::fake([
            '*/servers/*' => Http::response(['server' => $this->panelServer(null)]),
        ]);

        $result = (new ImportServiceCalagopus)->import($service, ['calagopus_server_uuid' => '11111111-2222-3333-4444-555555555555']);

        $this->assertTrue($result->success);
        Http::assertSent(fn ($request) => $request->method() !== 'PATCH'
            || $request->data() === ['external_id' => (string) $service->id]);
    }

    public function test_it_refuses_to_steal_a_server_already_bound_to_another_service(): void
    {
        $service = $this->service();
        Http::fake(['*' => Http::response(['server' => $this->panelServer('4242')])]);

        $result = (new ImportServiceCalagopus)->import($service, ['calagopus_server_uuid' => '11111111-2222-3333-4444-555555555555']);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('4242', $result->message);
        Http::assertNotSent(fn ($request) => $request->method() === 'PATCH');
    }

    public function test_importing_the_same_server_twice_changes_nothing(): void
    {
        $service = $this->service();
        Http::fake(['*' => Http::response(['server' => $this->panelServer((string) $service->id)])]);

        $result = (new ImportServiceCalagopus)->import($service, ['calagopus_server_uuid' => '11111111-2222-3333-4444-555555555555']);

        $this->assertTrue($result->success);
        Http::assertNotSent(fn ($request) => $request->method() === 'PATCH');
    }

    public function test_it_reports_an_unknown_identifier_without_touching_anything(): void
    {
        $service = $this->service();
        Http::fake(['*' => Http::response(['errors' => ['server not found']], 404)]);

        $result = (new ImportServiceCalagopus)->import($service, ['calagopus_server_uuid' => 'nope']);

        $this->assertFalse($result->success);
        Http::assertNotSent(fn ($request) => $request->method() === 'PATCH');
    }

    public function test_its_failures_never_leak_a_stack_trace_to_the_operator(): void
    {
        $service = $this->service();
        Http::fake(['*' => Http::response(['errors' => ['boom']], 500)]);

        $result = (new ImportServiceCalagopus)->import($service, ['calagopus_server_uuid' => 'nope']);

        $this->assertStringNotContainsString('/workspaces', $result->message);
        $this->assertStringNotContainsString('#0', $result->message);
    }

    private function panelServer(?string $externalId): array
    {
        return [
            'uuid' => '11111111-2222-3333-4444-555555555555',
            'uuid_short' => 'abcdef12',
            'name' => 'srv',
            'is_suspended' => false,
            'external_id' => $externalId,
            'limits' => [],
            'allocation' => null,
        ];
    }

    private function service(): Service
    {
        Customer::factory()->create();
        $service = Service::factory()->create(['type' => 'calagopus']);

        $service->server->update([
            'type' => 'calagopus', 'hostname' => 'panel.test', 'port' => 443, 'password' => str_repeat('a', 48),
        ]);

        return $service->fresh();
    }
}
