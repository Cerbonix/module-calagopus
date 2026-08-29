<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Models\Provisioning\Server;
use App\Modules\Calagopus\CalagopusResponse;
use App\Modules\Calagopus\CalagopusServerType;
use App\Modules\Calagopus\Http as CalagopusHttp;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalagopusConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The test environment starts with no extension registered, so the module namespace has to be loaded explicitly.
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
    }

    public static function failureCases(): array
    {
        return [
            'missing header' => [401, ['missing authorization'], 'invalid_key'],
            'malformed header' => [401, ['invalid authorization header'], 'malformed_key'],
            'revoked key' => [401, ['invalid api key'], 'invalid_key'],
            'source ip rejected' => [403, ['ip address not allowed for this api key'], 'ip_not_allowed'],
            'permission denied' => [403, ['you do not have permission to perform this action: users.read'], 'missing_permission'],
            'conflict' => [409, ['server with external id already exists'], 'conflict'],
            'panel refused' => [417, ['failed to delete server'], 'panel_refused'],
            'rate limited' => [429, [], 'rate_limited'],
            'server error' => [500, ['internal server error'], 'unexpected'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('failureCases')]
    public function test_it_maps_each_panel_failure_to_its_own_cause(int $status, array $errors, string $expected): void
    {
        $response = CalagopusResponse::fromResponse($this->panelResponse($status, ['errors' => $errors]));

        $this->assertSame($expected, $response->failureKey());
    }

    public function test_it_reports_an_unreachable_panel_apart_from_a_rejected_one(): void
    {
        $this->assertSame('unreachable', CalagopusResponse::unreachable('connection refused')->failureKey());
        $this->assertSame(0, CalagopusResponse::unreachable('connection refused')->status());
    }

    public function test_it_ignores_a_malformed_error_body_instead_of_crashing(): void
    {
        $response = CalagopusResponse::fromResponse($this->panelResponse(403, ['errors' => 'not-an-array']));

        $this->assertSame([], $response->errors());
        $this->assertSame('missing_permission', $response->failureKey());
    }

    public function test_it_requires_an_api_key_of_the_exact_length_the_panel_accepts(): void
    {
        $rules = (new CalagopusServerType)->validate();

        $this->assertContains('size:48', $rules['password']);
        $this->assertContains('required', $rules['password']);
    }

    public function test_it_builds_the_base_url_from_hostname_and_port(): void
    {
        $this->assertSame('https://panel.example.net', CalagopusHttp::baseUrl($this->server(['hostname' => 'panel.example.net', 'port' => 443])));
        $this->assertSame('http://panel.example.net', CalagopusHttp::baseUrl($this->server(['hostname' => 'panel.example.net', 'port' => 8000])));
        $this->assertSame('https://panel.example.net', CalagopusHttp::baseUrl($this->server(['hostname' => 'https://panel.example.net/', 'port' => 8000])));
    }

    public function test_it_refuses_to_call_the_panel_without_a_readable_key(): void
    {
        Http::fake();

        $result = (new CalagopusServerType)->testConnection($this->params(['password' => '']));

        $this->assertFalse($result->successful());
        $this->assertSame(__('calagopus::messages.connection.empty_key'), $result->toString());
        Http::assertNothingSent();
    }

    public function test_it_succeeds_when_the_panel_answers_and_the_key_carries_every_permission(): void
    {
        Http::fake([
            '*/system/overview' => Http::response(['version' => '1.1.4']),
            '*' => Http::response(['data' => []]),
        ]);

        $result = (new CalagopusServerType)->testConnection($this->params());

        $this->assertTrue($result->successful());
        $this->assertStringContainsString('1.1.4', $result->toString());
    }

    public function test_it_warns_when_the_panel_runs_a_version_that_was_never_verified(): void
    {
        Http::fake([
            '*/system/overview' => Http::response(['version' => '1.9.0']),
            '*' => Http::response(['data' => []]),
        ]);

        $result = (new CalagopusServerType)->testConnection($this->params());

        $this->assertTrue($result->successful());
        $this->assertSame(__('calagopus::messages.connection.ok_untested_version', [
            'version' => '1.9.0',
            'min' => CalagopusServerType::SUPPORTED_MIN,
            'below' => CalagopusServerType::SUPPORTED_BELOW,
        ]), $result->toString());
    }

    public function test_it_names_every_permission_the_key_is_missing(): void
    {
        Http::fake([
            '*/system/overview' => Http::response(['version' => '1.1.4']),
            '*/servers*' => Http::response(['servers' => []]),
            '*' => Http::response(['errors' => ['you do not have permission to perform this action']], 403),
        ]);

        $result = (new CalagopusServerType)->testConnection($this->params());

        $this->assertFalse($result->successful());
        $this->assertStringContainsString('users.read', $result->toString());
        $this->assertStringContainsString('locations.read', $result->toString());
        $this->assertStringContainsString('eggs.read', $result->toString());
        $this->assertStringNotContainsString('servers.read', $result->toString());
    }

    public function test_it_never_puts_the_api_key_in_the_message_it_returns(): void
    {
        $key = str_repeat('k', 48);
        Http::fake(['*' => Http::response(['errors' => ['invalid api key']], 401)]);

        $result = (new CalagopusServerType)->testConnection($this->params(['password' => $key]));

        $this->assertStringNotContainsString($key, $result->toString());
    }

    private function panelResponse(int $status, array $body): ClientResponse
    {
        return new ClientResponse(new PsrResponse($status, ['Content-Type' => 'application/json'], json_encode($body)));
    }

    private function server(array $overrides = []): Server
    {
        $server = new Server;
        $server->fill(array_merge($this->params(), $overrides));

        return $server;
    }

    private function params(array $overrides = []): array
    {
        return array_merge([
            'name' => 'panel',
            'type' => 'calagopus',
            'address' => 'panel.example.net',
            'hostname' => 'panel.example.net',
            'port' => 443,
            'username' => '',
            'password' => str_repeat('a', 48),
        ], $overrides);
    }
}
