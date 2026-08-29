<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\CalagopusPanel;
use App\Modules\Calagopus\Models\CalagopusConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalagopusClientPanelTest extends TestCase
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

    public function test_a_customer_cannot_read_the_service_of_another_customer(): void
    {
        $owner = Customer::factory()->create();
        $service = $this->service($owner);
        $stranger = Customer::factory()->create();

        $this->assertTrue($owner->hasServicePermission($service, 'service.show'));
        $this->assertFalse($stranger->hasServicePermission($service, 'service.show'),
            'a customer who owns nothing here must not pass the ownership check the core runs before rendering the panel');
    }

    public function test_it_shows_the_address_and_the_limits(): void
    {
        Http::fake(['*' => Http::response(['server' => $this->panelServer()])]);
        $service = $this->service(Customer::factory()->create());

        $html = (new CalagopusPanel)->render($service)->render();

        $this->assertStringContainsString('localhost:25565', $html);
        $this->assertStringContainsString('2 '.__('calagopus::client.unit.gb'), $html);
    }

    public function test_it_states_suspension_in_words_and_not_only_by_colour(): void
    {
        Http::fake(['*' => Http::response(['server' => $this->panelServer(true)])]);
        $service = $this->service(Customer::factory()->create());

        $html = (new CalagopusPanel)->render($service)->render();

        $this->assertStringContainsString(__('calagopus::client.state.suspended'), $html);
    }

    public function test_it_explains_itself_instead_of_breaking_when_the_panel_is_unreachable(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['server not found']], 404)]);
        $service = $this->service(Customer::factory()->create());

        $html = (new CalagopusPanel)->render($service)->render();

        $this->assertStringContainsString(__('calagopus::client.unavailable.not_found'), $html);
    }

    public function test_every_icon_is_hidden_from_assistive_technology(): void
    {
        Http::fake(['*' => Http::response(['server' => $this->panelServer()])]);
        $service = $this->service(Customer::factory()->create());

        $html = (new CalagopusPanel)->render($service)->render();

        $this->assertSame(
            substr_count($html, '<i class="bi'),
            substr_count($html, 'aria-hidden="true"'),
            'a decorative icon left exposed is announced as meaningless noise by a screen reader'
        );
    }

    private function panelServer(bool $suspended = false): array
    {
        return [
            'uuid' => '11111111-2222-3333-4444-555555555555',
            'uuid_short' => 'abcdef12',
            'name' => 'srv',
            'is_suspended' => $suspended,
            'external_id' => '1',
            'limits' => ['memory' => 2048, 'disk' => 10240, 'cpu' => 200],
            'allocation' => ['ip' => '127.0.0.1', 'port' => 25565, 'ip_alias' => 'localhost'],
        ];
    }

    private function service(Customer $owner): Service
    {
        $service = Service::factory()->create(['type' => 'calagopus', 'customer_id' => $owner->id]);

        $service->server->update([
            'type' => 'calagopus',
            'hostname' => 'panel.test',
            'port' => 443,
            'password' => str_repeat('a', 48),
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
