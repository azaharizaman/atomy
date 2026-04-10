<?php

declare(strict_types=1);

namespace App\Adapters\Tenant;

use App\Models\Tenant;
use Nexus\Tenant\Contracts\TenantValidationInterface;

/**
 * Eloquent validation adapter for tenant uniqueness checks.
 */
final readonly class EloquentTenantValidation implements TenantValidationInterface
{
    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $normalized = strtolower(trim($code));
        if ($normalized === '') {
            return false;
        }

        return $this->queryExists('code', $normalized, $excludeId);
    }

    public function domainExists(string $domain, ?string $excludeId = null): bool
    {
        $normalized = strtolower(trim($domain));
        if ($normalized === '') {
            return false;
        }

        return $this->queryExists('domain', $normalized, $excludeId);
    }

    public function nameExists(string $name, ?string $excludeId = null): bool
    {
        $normalized = strtolower(trim($name));
        if ($normalized === '') {
            return false;
        }

        return $this->queryExists('name', $normalized, $excludeId);
    }

    private function queryExists(string $column, string $value, ?string $excludeId = null): bool
    {
        $query = Tenant::query()->withTrashed()->whereRaw("lower({$column}) = ?", [$value]);

        if ($excludeId !== null && trim($excludeId) !== '') {
            $query->whereKeyNot($excludeId);
        }

        return $query->exists();
    }
}
