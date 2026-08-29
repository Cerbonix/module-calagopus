<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\Models\CalagopusConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalagopusSsoTest extends TestCase
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

    public function test_a_customer_cannot_get_a_ticket_for_someone_elses_service(): void
    {
        Http::fake();
        $service = $this->service();
        $stranger = Customer::factory()->create();

        $this->actingAs($stranger, 'web');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        (new \App\Modules\Calagopus\Controllers\Client\SsoController)->redirect($service);
    }

    public function test_an_anonymous_visitor_cannot_get_a_ticket(): void
    {
        Http::fake();
        $service = $this->service();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        (new \App\Modules\Calagopus\Controllers\Client\SsoController)->redirect($service);
    }

    public function test_the_owner_is_sent_to_a_ticket_url(): void
    {
        Http::fake([
            '*/users/external/*' => Http::response(['user' => ['uuid' => '65e06000-9796-4f2f-ae5d-bd59b8120a90', 'email' => 'a@b.c', 'username' => 'ab']]),
            '*/servers/external/*' => Http::response(['server' => $this->panelServer()]),
            '*/api/settings' => Http::response(['app' => ['url' => 'https://panel.test']]),
            '*/auth/ssotickets' => Http::response(['token' => str_repeat('t', 48), 'expires_in' => 60]),
        ]);
        $service = $this->service();

        $this->actingAs($service->customer, 'web');
        $response = (new \App\Modules\Calagopus\Controllers\Client\SsoController)->redirect($service);

        $this->assertStringContainsString('/api/auth/ssotickets/', $response->getTargetUrl());
    }

    public function test_it_never_sends_the_shared_secret_in_the_url(): void
    {
        Http::fake([
            '*/users/external/*' => Http::response(['user' => ['uuid' => '65e06000-9796-4f2f-ae5d-bd59b8120a90', 'email' => 'a@b.c', 'username' => 'ab']]),
            '*/servers/external/*' => Http::response(['server' => $this->panelServer()]),
            '*' => Http::response(['token' => str_repeat('t', 48), 'expires_in' => 60]),
        ]);
        $service = $this->service();

        $this->actingAs($service->customer, 'web');
        (new \App\Modules\Calagopus\Controllers\Client\SsoController)->redirect($service);

        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'secret'));
    }

    public function test_a_panel_without_a_secret_falls_back_instead_of_stranding_the_customer(): void
    {
        Http::fake([
            '*/servers/external/*' => Http::response(['server' => $this->panelServer()]),
            '*' => Http::response([]),
        ]);
        $service = $this->service(withSecret: false);

        $this->actingAs($service->customer, 'web');
        $response = (new \App\Modules\Calagopus\Controllers\Client\SsoController)->redirect($service);

        $this->assertStringNotContainsString('/api/auth/ssotickets/', $response->getTargetUrl());
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
            'allocation' => null,
        ];
    }

    private function service(bool $withSecret = true): Service
    {
        Customer::factory()->create();
        $service = Service::factory()->create(['type' => 'calagopus', 'status' => Service::STATUS_ACTIVE]);

        $service->server->update([
            'type' => 'calagopus',
            'hostname' => 'panel.test',
            'port' => 443,
            'password' => str_repeat('a', 48),
            'username' => $withSecret ? str_repeat('s', 48) : '',
        ]);

        CalagopusConfig::create([
            'product_id' => $service->product_id,
            'server_id' => $service->server_id,
            'egg_uuid' => '99999999-8888-7777-6666-555555555555',
            'location_uuids' => ['aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'],
            'image' => 'ghcr.io/example/image:latest',
            'startup' => 'java -jar server.jar',
        ]);

        return $service->fresh();
    }
}
