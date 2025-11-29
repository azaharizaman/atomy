<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FeatureFlag;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Eloquent implementation of FlagRepositoryInterface.
 */
final readonly class FeatureFlagRepository implements FlagRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    /**
     * {@inheritdoc}
     */
    public function find(string $name): ?FlagDefinitionInterface
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return null;
        }

        return FeatureFlag::query()
            ->tenant($tenantId)
            ->where('name', $name)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(string $id): ?FlagDefinitionInterface
    {
        return FeatureFlag::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return [];
        }

        return FeatureFlag::query()
            ->tenant($tenantId)
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function save(FlagDefinitionInterface $flag): void
    {
        if ($flag instanceof FeatureFlag) {
            $flag->save();
            return;
        }

        // If not an Eloquent model, create/update based on name
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context is required to save a flag');
        }

        FeatureFlag::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => $flag->getName(),
            ],
            [
                'enabled' => $flag->isEnabled(),
                'strategy' => $flag->getStrategy()->value,
                'value' => $flag->getValue(),
                'override' => $flag->getOverride()?->value,
                'metadata' => $flag->getMetadata(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return;
        }

        FeatureFlag::query()
            ->tenant($tenantId)
            ->where('name', $name)
            ->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name): bool
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return false;
        }

        return FeatureFlag::query()
            ->tenant($tenantId)
            ->where('name', $name)
            ->exists();
    }

    // ==========================================
    // Additional Methods (not in interface)
    // ==========================================

    /**
     * Create a new feature flag.
     */
    public function create(array $data): FeatureFlag
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context is required to create a flag');
        }

        $flag = new FeatureFlag();
        $flag->id = (string) new Ulid();
        $flag->tenant_id = $tenantId;
        $flag->name = $data['name'];
        $flag->description = $data['description'] ?? null;
        $flag->enabled = $data['enabled'] ?? false;
        $flag->strategy = $data['strategy'] ?? 'system_wide';
        $flag->value = $data['value'] ?? null;
        $flag->override = $data['override'] ?? null;
        $flag->metadata = $data['metadata'] ?? [];
        $flag->created_by = $data['created_by'] ?? null;
        $flag->updated_by = $data['updated_by'] ?? null;
        $flag->save();

        return $flag;
    }

    /**
     * Update a feature flag by ID.
     */
    public function update(string $id, array $data): ?FeatureFlag
    {
        $flag = FeatureFlag::find($id);

        if ($flag === null) {
            return null;
        }

        if (isset($data['name'])) {
            $flag->name = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $flag->description = $data['description'];
        }
        if (isset($data['enabled'])) {
            $flag->enabled = $data['enabled'];
        }
        if (isset($data['strategy'])) {
            $flag->strategy = $data['strategy'];
        }
        if (array_key_exists('value', $data)) {
            $flag->value = $data['value'];
        }
        if (array_key_exists('override', $data)) {
            $flag->override = $data['override'];
        }
        if (isset($data['metadata'])) {
            $flag->metadata = $data['metadata'];
        }
        if (isset($data['updated_by'])) {
            $flag->updated_by = $data['updated_by'];
        }

        $flag->save();

        return $flag;
    }

    /**
     * Delete a feature flag by ID.
     */
    public function deleteById(string $id): bool
    {
        $flag = FeatureFlag::find($id);

        if ($flag === null) {
            return false;
        }

        return (bool) $flag->delete();
    }

    /**
     * Toggle a flag's enabled state.
     */
    public function toggle(string $id): ?FeatureFlag
    {
        $flag = FeatureFlag::find($id);

        if ($flag === null) {
            return null;
        }

        $flag->toggle();
        $flag->save();

        return $flag;
    }

    /**
     * Get all enabled flags for the current tenant.
     */
    public function getEnabledFlags(): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId === null) {
            return [];
        }

        return FeatureFlag::query()
            ->tenant($tenantId)
            ->enabled()
            ->orderBy('name')
            ->get()
            ->all();
    }
}
