<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\CalagopusServerType;
use App\Modules\Calagopus\Models\CalagopusConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalagopusLifecycleTest extends TestCase
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

    public function test_it_refuses_a_service_that_belongs_to_another_module_without_calling_the_panel(): void
    {
        Http::fake();
        $service = $this->service(['type' => 'none']);

        $result = (new CalagopusServerType)->createAccount($service);

        $this->assertFalse($result->success);
        Http::assertNothingSent();
    }

    public function test_it_stops_when_the_product_has_no_configuration(): void
    {
        Http::fake();
        $service = $this->service([], false);

        $result = (new CalagopusServerType)->createAccount($service);

        $this->assertFalse($result->success);
        Http::assertNothingSent();
    }

    public function test_it_does_not_deploy_twice_for_the_same_service(): void
    {
        Http::fake([
            '*/servers/external/*' => Http::response(['server' => $this->panelServer()]),
        ]);

        $result = (new CalagopusServerType)->createAccount($this->service());

        $this->assertTrue($result->success);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'servers/deploy'));
    }

    public function test_it_never_asks_the_panel_to_delete_customer_backups(): void
    {
        Http::fake([
            '*/servers/external/*' => Http::response(['server' => $this->panelServer()]),
            '*' => Http::response([]),
        ]);

        $result = (new CalagopusServerType)->expireAccount($this->service());

        $this->assertTrue($result->success);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->data()['delete_backups'] === false
            && $request->data()['force'] === false);
    }

    public function test_it_suspends_using_the_field_the_panel_accepts_on_write(): void
    {
        Http::fake([
            '*/servers/external/*' => Http::response(['server' => $this->panelServer()]),
            '*' => Http::response([]),
        ]);

        $result = (new CalagopusServerType)->suspendAccount($this->service());

        $this->assertTrue($result->success);
        Http::assertSent(fn ($request) => $request->method() === 'PATCH' && $request->data() === ['suspended' => true]);
    }

    public function test_it_always_asks_the_panel_for_a_port_because_none_is_assigned_otherwise(): void
    {
        Http::fake([
            '*/servers/external/*' => Http::response(['errors' => ['server not found']], 404),
            '*/users/external/*' => Http::response(['user' => ['uuid' => '77777777-8888-9999-aaaa-bbbbbbbbbbbb', 'email' => 'a@b.c', 'username' => 'ab']]),
            '*/servers/deploy' => Http::response(['server' => $this->panelServer()]),
        ]);

        (new CalagopusServerType)->createAccount($this->service());

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'servers/deploy')) {
                return false;
            }
            $allocations = $request->data()['deployment']['allocations'] ?? null;

            return $allocations !== null
                && $allocations['primary']['start_port'] === 25565
                && $allocations['primary']['end_port'] === 25595;
        });
    }

    public function test_it_reports_a_missing_server_instead_of_pretending_it_suspended_one(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['server not found']], 404)]);

        $result = (new CalagopusServerType)->suspendAccount($this->service());

        $this->assertFalse($result->success);
    }

    public function test_it_turns_a_panel_failure_into_a_failed_state_change_rather_than_an_exception(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['internal server error']], 500)]);

        $result = (new CalagopusServerType)->createAccount($this->service());

        $this->assertFalse($result->success);
        $this->assertNotSame('', $result->message);
    }

    public function test_it_rebuilds_limits_from_scratch_so_replaying_an_option_does_not_stack_it(): void
    {
        Http::fake([
            '*/servers/external/*' => Http::response(['server' => $this->panelServer()]),
            '*' => Http::response([]),
        ]);

        $service = $this->service();
        $option = \App\Models\Billing\ConfigOption::create([
            'type' => 'number', 'key' => 'additional_memory', 'name' => 'Extra memory', 'sort_order' => 1,
        ]);
        \App\Models\Provisioning\ConfigOptionService::create([
            'config_option_id' => $option->id,
            'service_id' => $service->id,
            'key' => 'additional_memory',
            'value' => '2048',
        ]);

        $type = new CalagopusServerType;
        $type->addOption($service->fresh(), $option);
        $type->addOption($service->fresh(), $option);

        $patches = [];
        Http::assertSent(function ($request) use (&$patches) {
            if ($request->method() === 'PATCH') {
                $patches[] = $request->data()['limits']['memory'];
            }

            return true;
        });

        $this->assertSame([3072, 3072], $patches, 'the base 1024 plus the 2048 option, and never 5120');
    }

    private function panelServer(): array
    {
        return [
            'uuid' => '11111111-2222-3333-4444-555555555555',
            'uuid_short' => 'abcdef12',
            'name' => 'srv',
            'is_suspended' => false,
            'external_id' => '1',
            'limits' => [],
            'allocation' => ['ip' => '127.0.0.1', 'port' => 25565, 'ip_alias' => null],
        ];
    }

    private function service(array $overrides = [], bool $withConfig = true): Service
    {
        Customer::factory()->create();
        $service = Service::factory()->create(array_merge(['type' => 'calagopus'], $overrides));

        $service->server->update([
            'type' => 'calagopus',
            'hostname' => 'panel.test',
            'port' => 443,
            'password' => str_repeat('a', 48),
        ]);

        if ($withConfig) {
            CalagopusConfig::create([
                'product_id' => $service->product_id,
                'server_id' => $service->server_id,
                'egg_uuid' => '99999999-8888-7777-6666-555555555555',
                'location_uuids' => ['aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'],
                'image' => 'ghcr.io/example/image:latest',
                'startup' => 'java -jar server.jar',
            ]);
        }

        return $service->fresh();
    }
}
