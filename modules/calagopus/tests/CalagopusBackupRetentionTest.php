<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\CalagopusServerType;
use App\Modules\Calagopus\Models\CalagopusBackupPurge;
use App\Modules\Calagopus\Models\CalagopusConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalagopusBackupRetentionTest extends TestCase
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

    public function test_it_records_the_backups_before_the_server_is_deleted(): void
    {
        $this->fakePanel();
        $service = $this->service(30);

        (new CalagopusServerType)->expireAccount($service);

        $this->assertDatabaseCount('calagopus_backup_purges', 2);
        $this->assertSame(
            now()->addDays(30)->toDateString(),
            CalagopusBackupPurge::first()->purge_at->toDateString()
        );
    }

    public function test_it_records_nothing_when_the_product_keeps_backups_forever(): void
    {
        $this->fakePanel();

        (new CalagopusServerType)->expireAccount($this->service(0));

        $this->assertDatabaseCount('calagopus_backup_purges', 0);
    }

    public function test_it_never_asks_the_panel_to_delete_backups_unless_the_customer_asked(): void
    {
        $this->fakePanel();

        (new CalagopusServerType)->expireAccount($this->service(30));

        Http::assertSent(fn ($request) => $request->method() !== 'DELETE'
            || $request->data()['delete_backups'] === false);
    }

    public function test_a_customer_who_declined_retention_gets_their_backups_dropped_on_the_spot(): void
    {
        $this->fakePanel();
        $service = $this->service(30);
        $service->attachMetadata(\App\Modules\Calagopus\Controllers\Client\BackupController::KEEP_ON_TERMINATION, '0');

        (new CalagopusServerType)->expireAccount($service->fresh());

        Http::assertSent(fn ($request) => $request->method() !== 'DELETE'
            || $request->data()['delete_backups'] === true);
        $this->assertDatabaseCount('calagopus_backup_purges', 0);
    }

    public function test_an_explicit_choice_to_keep_behaves_like_the_default(): void
    {
        $this->fakePanel();
        $service = $this->service(30);
        $service->attachMetadata(\App\Modules\Calagopus\Controllers\Client\BackupController::KEEP_ON_TERMINATION, '1');

        (new CalagopusServerType)->expireAccount($service->fresh());

        $this->assertDatabaseCount('calagopus_backup_purges', 2);
    }

    public function test_the_purge_leaves_alone_a_backup_whose_retention_has_not_elapsed(): void
    {
        $this->fakePanel();
        $service = $this->service(30);
        CalagopusBackupPurge::create([
            'server_id' => $service->server_id, 'service_id' => $service->id, 'node_uuid' => '33333333-4444-5555-6666-777777777777',
            'backup_uuid' => 'aaaaaaaa-1111-2222-3333-444444444444', 'purge_at' => now()->addDay(),
        ]);

        $this->artisan('calagopus:purge-backups')->assertSuccessful();

        $this->assertDatabaseCount('calagopus_backup_purges', 1);
    }

    public function test_the_purge_forgets_a_backup_the_panel_no_longer_knows(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['backup not found']], 404)]);
        $service = $this->service(30);
        CalagopusBackupPurge::create([
            'server_id' => $service->server_id, 'service_id' => $service->id, 'node_uuid' => '33333333-4444-5555-6666-777777777777',
            'backup_uuid' => 'aaaaaaaa-1111-2222-3333-444444444444', 'purge_at' => now()->subDay(),
        ]);

        $this->artisan('calagopus:purge-backups')->assertSuccessful();

        $this->assertDatabaseCount('calagopus_backup_purges', 0);
    }

    public function test_the_purge_keeps_the_entry_when_the_panel_fails_so_the_next_run_retries(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['internal server error']], 500)]);
        $service = $this->service(30);
        CalagopusBackupPurge::create([
            'server_id' => $service->server_id, 'service_id' => $service->id, 'node_uuid' => '33333333-4444-5555-6666-777777777777',
            'backup_uuid' => 'aaaaaaaa-1111-2222-3333-444444444444', 'purge_at' => now()->subDay(),
        ]);

        $this->artisan('calagopus:purge-backups')->assertSuccessful();

        $this->assertDatabaseCount('calagopus_backup_purges', 1);
    }

    private function fakePanel(): void
    {
        Http::fake([
            '*/servers/external/*' => Http::response(['server' => [
                'uuid' => '11111111-2222-3333-4444-555555555555',
                'uuid_short' => 'abcdef12',
                'name' => 'srv',
                'is_suspended' => false,
                'external_id' => '1',
                'limits' => [],
                'allocation' => null,
                'node' => ['uuid' => '33333333-4444-5555-6666-777777777777'],
            ]]),
            '*/backups*' => Http::response(['backups' => ['data' => [
                ['uuid' => 'aaaaaaaa-1111-2222-3333-444444444444', 'name' => 'daily'],
                ['uuid' => 'bbbbbbbb-1111-2222-3333-444444444444', 'name' => 'weekly'],
            ]]]),
            '*' => Http::response([]),
        ]);
    }

    private function service(int $retentionDays): Service
    {
        Customer::factory()->create();
        $service = Service::factory()->create(['type' => 'calagopus']);

        $service->server->update([
            'type' => 'calagopus', 'hostname' => 'panel.test', 'port' => 443, 'password' => str_repeat('a', 48),
        ]);

        CalagopusConfig::create([
            'product_id' => $service->product_id,
            'server_id' => $service->server_id,
            'egg_uuid' => '99999999-8888-7777-6666-555555555555',
            'location_uuids' => ['aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee'],
            'image' => 'ghcr.io/example/image:latest',
            'startup' => 'java -jar server.jar',
            'backup_retention_days' => $retentionDays,
        ]);

        return $service->fresh();
    }
}
