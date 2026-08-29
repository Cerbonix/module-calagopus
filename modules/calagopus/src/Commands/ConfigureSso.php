<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\Commands;

use App\Models\Provisioning\Server;
use App\Modules\Calagopus\Http;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ConfigureSso extends Command
{
    protected $signature = 'calagopus:sso {--server= : Panel id, when several are configured} {--secret= : Use this secret instead of generating one} {--show : Print the current state without changing anything}';

    protected $description = 'Set the shared secret on both sides so customers reach the panel already signed in.';

    public function handle(): int
    {
        $panel = $this->resolvePanel();

        if ($panel === null) {
            return self::FAILURE;
        }

        if ($this->option('show')) {
            $this->line(trim((string) $panel->username) === ''
                ? 'Single sign-on is not configured on this panel.'
                : 'A shared secret is set on ClientXCMS for this panel.');

            return self::SUCCESS;
        }

        $secret = (string) ($this->option('secret') ?: Str::random(48));

        if (strlen($secret) < 32) {
            $this->error('The shared secret must be at least 32 characters.');

            return self::FAILURE;
        }

        // The panel goes first: if it refuses, ClientXCMS keeps its previous secret and nothing is half-applied.
        $response = Http::callApi($panel, 'ssotickets/secret', ['secret' => $secret], 'PUT');

        if (! $response->successful()) {
            $this->error('The panel refused the secret: '.($response->errorMessage() ?: 'unknown error'));
            $this->line('The API key needs the ssotickets.manage permission, and the SSO extension must be installed.');

            return self::FAILURE;
        }

        $panel->username = $secret;
        $panel->save();

        $this->info('Single sign-on configured on both sides.');
        $this->line('Customers opening their server from the client area now land signed in.');

        return self::SUCCESS;
    }

    private function resolvePanel(): ?Server
    {
        $panels = Server::where('type', 'calagopus')->where('status', 'active')->get();

        if ($panels->isEmpty()) {
            $this->error('No active Calagopus panel is configured.');

            return null;
        }

        if ($this->option('server')) {
            $panel = $panels->firstWhere('id', (int) $this->option('server'));

            if ($panel === null) {
                $this->error('No Calagopus panel matches that id.');
            }

            return $panel;
        }

        if ($panels->count() > 1) {
            $this->error('Several panels are configured, pick one with --server=<id>:');
            foreach ($panels as $panel) {
                $this->line("  {$panel->id}  {$panel->name}");
            }

            return null;
        }

        return $panels->first();
    }
}
