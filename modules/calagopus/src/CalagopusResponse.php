<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use Illuminate\Http\Client\Response;

class CalagopusResponse
{
    private function __construct(
        private readonly ?Response $response,
        private readonly ?string $transportError = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        return new self($response);
    }

    public static function unreachable(string $error): self
    {
        return new self(null, $error);
    }

    public function successful(): bool
    {
        return $this->response !== null && $this->response->successful();
    }

    public function status(): int
    {
        return $this->response?->status() ?? 0;
    }

    public function json(): array
    {
        $decoded = $this->response?->json();

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * The panel always reports failures as {"errors": ["...", "..."]}.
     */
    public function errors(): array
    {
        $errors = $this->json()['errors'] ?? [];

        return is_array($errors) ? array_filter($errors, 'is_string') : [];
    }

    public function errorMessage(): string
    {
        return implode(', ', $this->errors()) ?: ($this->transportError ?? '');
    }

    public function failureKey(): string
    {
        if ($this->response === null) {
            return 'unreachable';
        }

        // The panel returns 403 for both a rejected source IP and a missing permission, and only the message tells them apart.
        $message = strtolower($this->errorMessage());

        return match (true) {
            $this->successful() => 'ok',
            $this->status() === 401 && str_contains($message, 'authorization header') => 'malformed_key',
            $this->status() === 401 => 'invalid_key',
            $this->status() === 403 && str_contains($message, 'ip address') => 'ip_not_allowed',
            $this->status() === 403 => 'missing_permission',
            $this->status() === 409 => 'conflict',
            $this->status() === 417 => 'panel_refused',
            $this->status() === 429 => 'rate_limited',
            default => 'unexpected',
        };
    }
}
