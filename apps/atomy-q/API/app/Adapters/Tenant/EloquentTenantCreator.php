<?php

declare(strict_types=1);

namespace App\Adapters\Tenant;

use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Contracts\TenantValidationInterface;
use Nexus\Tenant\Exceptions\DuplicateTenantCodeException;
use Nexus\Tenant\Exceptions\DuplicateTenantDomainException;
use Nexus\Tenant\Exceptions\DuplicateTenantNameException;
use Nexus\TenantOperations\Contracts\TenantCreatorAdapterInterface;

/**
 * Thin onboarding adapter that uses the package validation + persistence contracts.
 */
final readonly class EloquentTenantCreator implements TenantCreatorAdapterInterface
{
    public function __construct(
        private TenantPersistenceInterface $persistence,
        private TenantValidationInterface $validation,
    ) {
    }

    public function create(
        string $code,
        string $name,
        string $email,
        string $domain,
        ?string $timezone = null,
        ?string $locale = null,
        ?string $currency = null,
        ?array $metadata = null,
    ): string {
        $code = $this->normalizeCode($code);
        $name = trim($name);
        $email = strtolower(trim($email));
        $domain = $this->normalizeNullableString($domain) ?? $this->deriveDomainFromCode($code);
        $timezone = $this->normalizeNullableString($timezone) ?? 'UTC';
        $locale = $this->normalizeNullableString($locale) ?? 'en';
        $currency = $this->normalizeNullableString($currency) ?? 'USD';

        if ($this->validation->codeExists($code)) {
            throw DuplicateTenantCodeException::code($code);
        }

        if ($this->validation->nameExists($name)) {
            throw DuplicateTenantNameException::name($name);
        }

        if ($domain !== '' && $this->validation->domainExists($domain)) {
            throw DuplicateTenantDomainException::domain($domain);
        }

        $tenant = $this->persistence->create([
            'code' => $code,
            'name' => $name,
            'email' => $email,
            'domain' => $domain,
            'subdomain' => $this->deriveSubdomain($domain, $code),
            'status' => 'active',
            'timezone' => $timezone,
            'locale' => $locale,
            'currency' => $currency,
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'metadata' => $metadata ?? [],
            'onboarding_progress' => 100,
        ]);

        return $tenant->getId();
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function deriveDomainFromCode(string $code): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $code) ?? $code);
        $slug = trim($slug, '-');

        return $slug === '' ? 'tenant.local' : $slug . '.local';
    }

    private function deriveSubdomain(string $domain, string $code): ?string
    {
        $normalizedDomain = strtolower(trim($domain));
        if ($normalizedDomain !== '') {
            $parts = explode('.', $normalizedDomain);
            $candidate = trim((string) ($parts[0] ?? ''));

            return $candidate === '' ? null : $candidate;
        }

        $fallback = strtolower(preg_replace('/[^a-z0-9]+/', '-', $code) ?? $code);
        $fallback = trim($fallback, '-');

        return $fallback === '' ? null : $fallback;
    }
}
