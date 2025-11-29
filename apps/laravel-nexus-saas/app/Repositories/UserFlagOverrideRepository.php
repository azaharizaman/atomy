<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\UserFlagOverride;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Repository for managing user-level flag overrides.
 */
final readonly class UserFlagOverrideRepository
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    /**
     * Find a user flag override by user ID and flag name.
     */
    public function findByUserAndFlag(string $userId, string $flagName): ?UserFlagOverride
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return null;
        }

        return UserFlagOverride::query()
            ->tenant($tenantId)
            ->forUser($userId)
            ->forFlag($flagName)
            ->first();
    }

    /**
     * Find a user flag override by ID.
     */
    public function findById(string $id): ?UserFlagOverride
    {
        return UserFlagOverride::find($id);
    }

    /**
     * Get all overrides for a specific user.
     */
    public function getAllForUser(string $userId): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return [];
        }

        return UserFlagOverride::query()
            ->tenant($tenantId)
            ->forUser($userId)
            ->orderBy('flag_name')
            ->get()
            ->all();
    }

    /**
     * Get all active (non-expired) overrides for a user.
     */
    public function getActiveForUser(string $userId): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return [];
        }

        return UserFlagOverride::query()
            ->tenant($tenantId)
            ->forUser($userId)
            ->active()
            ->orderBy('flag_name')
            ->get()
            ->all();
    }

    /**
     * Get all overrides for a specific flag.
     */
    public function getAllForFlag(string $flagName): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return [];
        }

        return UserFlagOverride::query()
            ->tenant($tenantId)
            ->forFlag($flagName)
            ->orderBy('user_id')
            ->get()
            ->all();
    }

    /**
     * Create a user flag override.
     */
    public function create(array $data): UserFlagOverride
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context is required to create a user flag override');
        }

        $override = new UserFlagOverride();
        $override->id = (string) new Ulid();
        $override->tenant_id = $tenantId;
        $override->user_id = $data['user_id'];
        $override->flag_name = $data['flag_name'];
        $override->enabled = $data['enabled'] ?? false;
        $override->value = $data['value'] ?? null;
        $override->reason = $data['reason'] ?? null;
        $override->expires_at = $data['expires_at'] ?? null;
        $override->created_by = $data['created_by'] ?? null;
        $override->updated_by = $data['updated_by'] ?? null;
        $override->save();

        return $override;
    }

    /**
     * Update a user flag override.
     */
    public function update(string $id, array $data): ?UserFlagOverride
    {
        $override = UserFlagOverride::find($id);

        if ($override === null) {
            return null;
        }

        if (isset($data['enabled'])) {
            $override->enabled = $data['enabled'];
        }
        if (array_key_exists('value', $data)) {
            $override->value = $data['value'];
        }
        if (array_key_exists('reason', $data)) {
            $override->reason = $data['reason'];
        }
        if (array_key_exists('expires_at', $data)) {
            $override->expires_at = $data['expires_at'];
        }
        if (isset($data['updated_by'])) {
            $override->updated_by = $data['updated_by'];
        }

        $override->save();

        return $override;
    }

    /**
     * Update or create a user flag override.
     */
    public function upsert(string $userId, string $flagName, array $data): UserFlagOverride
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context is required');
        }

        $override = $this->findByUserAndFlag($userId, $flagName);

        if ($override !== null) {
            return $this->update($override->id, $data);
        }

        return $this->create(array_merge($data, [
            'user_id' => $userId,
            'flag_name' => $flagName,
        ]));
    }

    /**
     * Delete a user flag override by ID.
     */
    public function deleteById(string $id): bool
    {
        $override = UserFlagOverride::find($id);

        if ($override === null) {
            return false;
        }

        return (bool) $override->delete();
    }

    /**
     * Delete a user flag override by user ID and flag name.
     */
    public function deleteByUserAndFlag(string $userId, string $flagName): bool
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return false;
        }

        return UserFlagOverride::query()
            ->tenant($tenantId)
            ->forUser($userId)
            ->forFlag($flagName)
            ->delete() > 0;
    }

    /**
     * Delete all overrides for a user.
     */
    public function deleteAllForUser(string $userId): int
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return 0;
        }

        return UserFlagOverride::query()
            ->tenant($tenantId)
            ->forUser($userId)
            ->delete();
    }

    /**
     * Delete all expired overrides.
     */
    public function deleteExpired(): int
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return 0;
        }

        return UserFlagOverride::query()
            ->tenant($tenantId)
            ->expired()
            ->delete();
    }

    /**
     * Check if an override exists for user and flag.
     */
    public function exists(string $userId, string $flagName): bool
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return false;
        }

        return UserFlagOverride::query()
            ->tenant($tenantId)
            ->forUser($userId)
            ->forFlag($flagName)
            ->exists();
    }

    /**
     * Check if a flag is enabled for a specific user (considering overrides).
     */
    public function isEnabledForUser(string $userId, string $flagName): ?bool
    {
        $override = $this->findByUserAndFlag($userId, $flagName);

        if ($override === null || $override->isExpired()) {
            return null; // No active override, use default flag value
        }

        return $override->isEnabled();
    }
}
