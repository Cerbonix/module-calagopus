<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus\DTO;

use App\Models\Account\Customer;
use App\Models\Provisioning\Server;
use App\Modules\Calagopus\Http;
use Illuminate\Support\Str;

class CalagopusUserDTO
{
    /** Snapshot of GET /api/languages on panel 1.1.4; an unlisted locale simply falls back to English. */
    private const PANEL_LANGUAGES = ['ar', 'de', 'en', 'es', 'fr', 'it', 'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sv', 'tr', 'vi', 'zh'];

    public function __construct(
        public readonly string $uuid,
        public readonly string $email,
        public readonly string $username,
        public readonly ?string $password = null,
        public readonly bool $wasCreated = false,
    ) {}

    public static function fromArray(array $user, ?string $password = null, bool $wasCreated = false): self
    {
        return new self(
            uuid: (string) $user['uuid'],
            email: (string) $user['email'],
            username: (string) $user['username'],
            password: $password,
            wasCreated: $wasCreated,
        );
    }

    /**
     * Reuses the panel account bound to this customer, then any account already holding the email, and only creates one as a last resort.
     */
    public static function resolve(Server $server, Customer $customer): self
    {
        $existing = Http::callApi($server, 'users/external/'.$customer->id);

        if ($existing->successful() && isset($existing->json()['user'])) {
            return self::fromArray($existing->json()['user']);
        }

        $password = Str::password(20);
        $created = Http::callApi($server, 'users', self::creationPayload($customer, $password), 'POST');

        if ($created->successful() && isset($created->json()['user'])) {
            return self::fromArray($created->json()['user'], $password, true);
        }

        if ($created->status() === 409) {
            return self::adoptExistingAccount($server, $customer);
        }

        throw new \RuntimeException($created->errorMessage() ?: 'unable to create the panel account');
    }

    private static function adoptExistingAccount(Server $server, Customer $customer): self
    {
        $search = Http::callApi($server, 'users?page=1&per_page=100&search='.urlencode($customer->email));

        foreach ($search->json()['users']['data'] ?? [] as $user) {
            if (isset($user['email']) && strcasecmp($user['email'], $customer->email) === 0) {
                return self::fromArray($user);
            }
        }

        throw new \RuntimeException('the panel reports this email as taken but does not return the account');
    }

    private static function creationPayload(Customer $customer, string $password): array
    {
        return [
            'external_id' => (string) $customer->id,
            'username' => self::usernameFor($customer),
            'email' => $customer->email,
            'name_first' => Str::limit((string) ($customer->firstname ?: 'Client'), 255, ''),
            'name_last' => Str::limit((string) ($customer->lastname ?: 'Client'), 255, ''),
            'language' => self::languageFor($customer),
            'password' => $password,
            'admin' => false,
            'role_uuid' => null,
        ];
    }

    /**
     * The panel enforces 3 to 15 characters matching ^[a-zA-Z0-9_]+$, so the local part is sanitised and padded rather than sent as is.
     */
    public static function usernameFor(Customer $customer): string
    {
        $base = Str::of((string) Str::before($customer->email, '@'))
            ->ascii()
            ->replaceMatches('/[^a-zA-Z0-9_]/', '')
            ->lower()
            ->limit(9, '')
            ->toString();

        if (strlen($base) < 3) {
            $base = 'user'.$base;
        }

        return substr($base.'_'.Str::lower(Str::random(4)), 0, 15);
    }

    public static function languageFor(Customer $customer): string
    {
        $locale = Str::lower(substr((string) ($customer->lang ?? ''), 0, 2));

        return in_array($locale, self::PANEL_LANGUAGES, true) ? $locale : 'en';
    }
}
