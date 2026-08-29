<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Modules\Calagopus\BackupPurger;
use App\Modules\Calagopus\Http;
use App\Modules\Calagopus\Models\CalagopusBackupPurge;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public const KEEP_ON_TERMINATION = 'calagopus_keep_backups';

    public function index()
    {
        $customer = $this->customer();

        return view('calagopus::backups.index', [
            'entries' => $this->kept($customer->id)->with('service')->orderBy('purge_at')->get(),
        ]);
    }

    /**
     * The panel hands back a signed URL, so the customer downloads straight from the node and our API key never leaves ClientXCMS.
     */
    public function download(CalagopusBackupPurge $backup)
    {
        $customer = $this->customer();

        abort_if($this->kept($customer->id)->whereKey($backup->getKey())->doesntExist(), 404);

        $panel = $backup->server;

        abort_if($panel === null, 404);

        $response = Http::callApi($panel, "nodes/{$backup->node_uuid}/backups/{$backup->backup_uuid}/download");
        $url = $response->successful() ? ($response->json()['url'] ?? null) : null;

        if (! is_string($url) || $url === '') {
            return back()->with('error', __('calagopus::client.backups.download_failed'));
        }

        return redirect()->away($url);
    }

    public function preference(Request $request, \App\Models\Provisioning\Service $service)
    {
        $customer = $this->customer();

        abort_if(! $customer->hasServicePermission($service, 'service.show'), 404);
        abort_if($service->type !== 'calagopus', 404);

        $service->attachMetadata(self::KEEP_ON_TERMINATION, $request->boolean('keep') ? '1' : '0');

        return back()->with('success', __('calagopus::client.retention.saved'));
    }

    public function destroy(Request $request)
    {
        $customer = $this->customer();
        $serviceId = $request->integer('service');

        $entries = $this->kept($customer->id)
            ->when($serviceId > 0, fn ($query) => $query->where('service_id', $serviceId))
            ->get();

        if ($entries->isEmpty()) {
            return redirect()->route('calagopus.backups.index')
                ->with('error', __('calagopus::client.backups.nothing_to_delete'));
        }

        $tally = app(BackupPurger::class)->purgeAll($entries);
        $gone = $tally[BackupPurger::DELETED] + $tally[BackupPurger::ORPHANED];

        if ($tally[BackupPurger::FAILED] > 0) {
            return redirect()->route('calagopus.backups.index')
                ->with('error', trans_choice('calagopus::client.backups.partly_failed', $tally[BackupPurger::FAILED], ['count' => $tally[BackupPurger::FAILED]]));
        }

        return redirect()->route('calagopus.backups.index')
            ->with('success', trans_choice('calagopus::client.backups.deleted', $gone, ['count' => $gone]));
    }

    /**
     * Scoping every query on the signed-in customer is what keeps one customer from erasing another's backups.
     */
    private function kept(int $customerId)
    {
        return CalagopusBackupPurge::whereHas('service', fn ($query) => $query->where('customer_id', $customerId));
    }

    private function customer()
    {
        $customer = auth('web')->user();

        abort_if($customer === null, 404);

        return $customer;
    }
}
