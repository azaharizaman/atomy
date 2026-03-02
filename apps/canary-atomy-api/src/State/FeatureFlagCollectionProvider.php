<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\FeatureFlag as FeatureFlagResource;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\FeatureFlagCoordinatorInterface;

/**
 * Collection provider for FeatureFlag resource.
 *
 * Fetches feature flags for the current tenant using the SettingsManagement orchestrator.
 * Multi-tenant aware - returns tenant-specific flags with global fallback.
 */
final class FeatureFlagCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly FeatureFlagCoordinatorInterface $flagCoordinator,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<FeatureFlagResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        // Get tenant ID for scoping
        $tenantId = $this->tenantContext->getCurrentTenantId();

        // Get all flags for the tenant (includes global flags as fallback)
        // If tenantId is null, we pass empty string to get global flags
        $flags = $this->flagCoordinator->getAllFlags($tenantId ?? '');

        foreach ($flags as $flag) {
            $featureFlag = new FeatureFlagResource();
            
            if (is_array($flag)) {
                $featureFlag->name = $flag['name'] ?? $flag['key'] ?? 'unknown';
                $featureFlag->enabled = (bool)($flag['enabled'] ?? false);
                $featureFlag->strategy = $flag['strategy'] ?? 'system_wide';
                $featureFlag->value = $flag['value'] ?? null;
                $featureFlag->override = $flag['override'] ?? null;
                $featureFlag->metadata = $flag['metadata'] ?? [];
            } else {
                $featureFlag->name = $flag->getName();
                $featureFlag->enabled = $flag->isEnabled();
                $featureFlag->strategy = $flag->getStrategy() instanceof \BackedEnum ? $flag->getStrategy()->value : (string)$flag->getStrategy();
                $featureFlag->value = $flag->getValue();
                $featureFlag->override = $flag->getOverride() instanceof \BackedEnum ? $flag->getOverride()->value : $flag->getOverride();
                $featureFlag->metadata = $flag->getMetadata();
            }
            
            $featureFlag->scope = $tenantId !== null ? 'tenant' : 'global';

            yield $featureFlag;
        }
    }
}
