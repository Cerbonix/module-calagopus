<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Models\Account\Customer;
use App\Models\Provisioning\Service;
use App\Modules\Calagopus\Controllers\Client\BackupController;
use App\Modules\Calagopus\Models\CalagopusBackupPurge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalagopusClientBackupsTest extends TestCase
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

    public function test_a_customer_only_sees_the_backups_of_their_own_services(): void
    {
        $mine = $this->keptBackup();
        $theirs = $this->keptBackup();

        $this->actingAs($mine->service->customer, 'web');
        $view = (new BackupController)->index();

        $listed = $view->getData()['entries']->pluck('backup_uuid');
        $this->assertContains($mine->backup_uuid, $listed);
        $this->assertNotContains($theirs->backup_uuid, $listed);
    }

    public function test_a_customer_cannot_delete_someone_elses_backups(): void
    {
        Http::fake();
        $theirs = $this->keptBackup();
        $stranger = Customer::factory()->create();

        $this->actingAs($stranger, 'web');
        (new BackupController)->destroy(Request::create('/', 'DELETE', ['service' => $theirs->service_id]));

        $this->assertDatabaseHas('calagopus_backup_purges', ['backup_uuid' => $theirs->backup_uuid]);
        Http::assertNothingSent();
    }

    public function test_an_anonymous_visitor_is_refused(): void
    {
        $this->keptBackup();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        (new BackupController)->index();
    }

    public function test_deleting_asks_the_panel_and_forgets_the_backup(): void
    {
        Http::fake(['*' => Http::response([], 204)]);
        $mine = $this->keptBackup();

        $this->actingAs($mine->service->customer, 'web');
        (new BackupController)->destroy(Request::create('/', 'DELETE', ['service' => $mine->service_id]));

        Http::assertSent(fn ($request) => str_contains($request->url(), "backups/{$mine->backup_uuid}") && $request->method() === 'DELETE');
        $this->assertDatabaseMissing('calagopus_backup_purges', ['backup_uuid' => $mine->backup_uuid]);
    }

    public function test_a_backup_the_panel_refuses_to_delete_is_kept_and_reported(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['nope']], 500)]);
        $mine = $this->keptBackup();

        $this->actingAs($mine->service->customer, 'web');
        $response = (new BackupController)->destroy(Request::create('/', 'DELETE', ['service' => $mine->service_id]));

        $this->assertDatabaseHas('calagopus_backup_purges', ['backup_uuid' => $mine->backup_uuid]);
        $this->assertNotNull($response->getSession()->get('error'));
    }

    public function test_a_customer_cannot_download_someone_elses_backup(): void
    {
        Http::fake();
        $theirs = $this->keptBackup();
        $stranger = Customer::factory()->create();

        $this->actingAs($stranger, 'web');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        (new BackupController)->download($theirs);
    }

    public function test_the_owner_is_sent_to_the_url_the_panel_signed(): void
    {
        Http::fake(['*/download' => Http::response(['url' => 'https://node.test/download/abc'])]);
        $mine = $this->keptBackup();

        $this->actingAs($mine->service->customer, 'web');
        $response = (new BackupController)->download($mine);

        $this->assertSame('https://node.test/download/abc', $response->getTargetUrl());
    }

    public function test_a_backup_the_panel_will_not_serve_does_not_strand_the_customer(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['nope']], 417)]);
        $mine = $this->keptBackup();

        $this->actingAs($mine->service->customer, 'web');
        $response = (new BackupController)->download($mine);

        $this->assertNotNull($response->getSession()->get('error'));
    }

    private function keptBackup(): CalagopusBackupPurge
    {
        $service = Service::factory()->create([
            'type' => 'calagopus',
            'status' => Service::STATUS_EXPIRED,
            'customer_id' => Customer::factory()->create()->id,
        ]);

        $service->server->update(['type' => 'calagopus', 'hostname' => 'panel.test', 'port' => 443, 'password' => str_repeat('a', 48)]);

        return CalagopusBackupPurge::create([
            'server_id' => $service->server_id,
            'service_id' => $service->id,
            'node_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'backup_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'backup_name' => 'weekly',
            'purge_at' => now()->addDays(30),
        ]);
    }
}
