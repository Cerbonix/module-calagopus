<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\InactiveServicePanel;
use App\Modules\Calagopus\Models\CalagopusBackupPurge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalagopusInactiveServicePanelTest extends TestCase
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

        if (! \Route::has('calagopus.backups.index')) {
            \Route::middleware('web')->group(module_path('calagopus', 'routes/web.php'));
            \Route::getRoutes()->refreshNameLookups();
        }
    }

    public function test_a_cancelled_service_keeps_access_to_a_server_that_still_runs(): void
    {
        $this->fakePanel(serverExists: true);
        $service = $this->service(Service::STATUS_CANCELLED);

        $html = $this->composed($service);

        $this->assertStringContainsString(route('calagopus.sso', ['service' => $service]), $html);
    }

    public function test_a_terminated_service_gets_a_way_back_to_its_backups(): void
    {
        $this->fakePanel(serverExists: false);
        $service = $this->service(Service::STATUS_EXPIRED);
        $this->keptBackup($service);

        $html = $this->composed($service);

        $this->assertStringContainsString(route('calagopus.backups.index'), $html);
    }

    public function test_it_keeps_whatever_the_core_already_rendered(): void
    {
        $this->fakePanel(serverExists: false);
        $service = $this->service(Service::STATUS_CANCELLED);
        $this->keptBackup($service);

        $html = $this->composed($service, '<p>core</p>');

        $this->assertStringStartsWith('<p>core</p>', $html);
    }

    public function test_an_active_service_is_left_alone(): void
    {
        $this->fakePanel(serverExists: true);
        $service = $this->service(Service::STATUS_ACTIVE);
        $this->keptBackup($service);

        $this->assertSame('<p>core</p>', $this->composed($service, '<p>core</p>'));
    }

    public function test_a_terminated_service_without_kept_backups_shows_nothing(): void
    {
        $this->fakePanel(serverExists: false);
        $service = $this->service(Service::STATUS_EXPIRED);

        $this->assertSame('', $this->composed($service));
    }

    public function test_another_product_type_is_left_alone(): void
    {
        $this->fakePanel(serverExists: false);
        $service = $this->service(Service::STATUS_EXPIRED, 'pterodactyl');
        $this->keptBackup($service);

        $this->assertSame('', $this->composed($service));
    }

    private function fakePanel(bool $serverExists): void
    {
        Http::fake([
            '*/servers/external/*' => $serverExists
                ? Http::response(['server' => [
                    'uuid' => '11111111-2222-3333-4444-555555555555',
                    'uuid_short' => 'abcdef12',
                    'name' => 'srv',
                    'is_suspended' => false,
                    'external_id' => '1',
                    'limits' => ['cpu' => 100, 'memory' => 1024, 'disk' => 5120],
                    'allocation' => null,
                ]])
                : Http::response(['errors' => ['not found']], 404),
            '*' => Http::response(['app' => ['url' => 'https://panel.test']]),
        ]);
    }

    private function composed(Service $service, string $existing = ''): string
    {
        $view = view('calagopus::panel.unavailable', ['reason' => 'not_found'])
            ->with('service', $service)
            ->with('panel_html', $existing);

        InactiveServicePanel::compose($view);

        return (string) ($view->getData()['panel_html'] ?? '');
    }

    private function keptBackup(Service $service): void
    {
        CalagopusBackupPurge::create([
            'server_id' => $service->server_id,
            'service_id' => $service->id,
            'node_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'backup_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'backup_name' => 'weekly',
            'purge_at' => now()->addDays(30),
        ]);
    }

    private function service(string $status, string $type = 'calagopus'): Service
    {
        $service = Service::factory()->create([
            'type' => $type,
            'status' => $status,
            'customer_id' => Customer::factory()->create()->id,
        ]);

        $service->server->update(['type' => 'calagopus', 'hostname' => 'panel.test', 'port' => 443, 'password' => str_repeat('a', 48)]);

        return $service->fresh();
    }
}
