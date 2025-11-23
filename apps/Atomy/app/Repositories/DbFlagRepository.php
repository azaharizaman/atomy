<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FeatureFlag;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Exceptions\InvalidFlagDefinitionException;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;

/**
 * Database-backed implementation of FlagRepositoryInterface.
 *
 * Implements tenant inheritance:
 * - Tenant-specific flags override global flags
 * - Global flags (tenant_id = null) serve as defaults
 */
final readonly class DbFlagRepository implements FlagRepositoryInterface
{
    public function find(string $name, ?string $tenantId = null): ?FlagDefinitionInterface
    {
        // Try tenant-specific first (if tenant provided)
        if ($tenantId !== null) {
            $flag = FeatureFlag::query()
                ->where('tenant_id', $tenantId)
                ->where('name', $name)
                ->first();

            if ($flag !== null) {
                return $flag;
            }
        }

        // Fall back to global flag
        return FeatureFlag::query()
            ->whereNull('tenant_id')
            ->where('name', $name)
            ->first();
    }

    public function findMany(array $names, ?string $tenantId = null): array
    {
        if (empty($names)) {
            return [];
        }

        $flags = [];

        // Try tenant-specific first (if tenant provided)
        if ($tenantId !== null) {
            $tenantFlags = FeatureFlag::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('name', $names)
                ->get()
                ->keyBy('name');

            foreach ($tenantFlags as $name => $flag) {
                $flags[$name] = $flag;
            }
        }

        // Get global flags for names not found in tenant-specific
        $remainingNames = array_diff($names, array_keys($flags));

        if (!empty($remainingNames)) {
            $globalFlags = FeatureFlag::query()
                ->whereNull('tenant_id')
                ->whereIn('name', $remainingNames)
                ->get()
                ->keyBy('name');

            foreach ($globalFlags as $name => $flag) {
                $flags[$name] = $flag;
            }
        }

        return $flags;
    }

    public function save(FlagDefinitionInterface $flag): void
    {
        // If saving a FlagDefinition (not Eloquent model), convert to model
        if ($flag instanceof FlagDefinition) {
            $this->saveFlagDefinition($flag);
            return;
        }

        // If already an Eloquent model, save directly
        if ($flag instanceof FeatureFlag) {
            $flag->save();
            return;
        }

        throw InvalidFlagDefinitionException::invalidType(get_class($flag));
    }

    public function delete(string $name): void
    {
        // Delete all flags with this name (global + all tenant-specific)
        FeatureFlag::query()
            ->where('name', $name)
            ->delete();
    }

    public function all(?string $tenantId = null): array
    {
        $query = FeatureFlag::query();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->whereNull('tenant_id');
        }

        return $query->get()->keyBy('name')->all();
    }

    /**
     * Save a FlagDefinition value object as a new/updated Eloquent model.
     *
     * @param FlagDefinition $flag Flag definition to save
     */
    private function saveFlagDefinition(FlagDefinition $flag): void
    {
        FeatureFlag::query()->updateOrCreate(
            [
                'tenant_id' => null, // Assume global for now
                'name' => $flag->getName(),
            ],
            [
                'enabled' => $flag->isEnabled(),
                'strategy' => $flag->getStrategy(),
                'value' => $flag->getValue(),
                'override' => $flag->getOverride(),
                'metadata' => $flag->getMetadata(),
                // checksum calculated automatically via model event
            ]
        );
    }
}
