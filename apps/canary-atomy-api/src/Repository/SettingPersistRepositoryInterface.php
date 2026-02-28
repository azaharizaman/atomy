<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * Interface for settings persistence operations.
 */
interface SettingPersistRepositoryInterface
{
    public function set(string $key, mixed $value): void;

    public function setForTenant(string $key, mixed $value, ?string $tenantId): void;

    public function delete(string $key): void;

    public function deleteForTenant(string $key, ?string $tenantId): void;

    public function bulkSet(array $settings): void;
}
