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
        $flags = $this->flagCoordinator->getAllFeatureFlags($tenantId);

        foreach ($flags as $flag) {
            $featureFlag = new FeatureFlagResource();
            $featureFlag->name = $flag->getName();
            $featureFlag->enabled = $flag->isEnabled();
            $featureFlag->strategy = $flag->getStrategy()->value;
            $featureFlag->value = $flag->getValue();
            $featureFlag->override = $flag->getOverride()?->value;
            $featureFlag->metadata = $flag->getMetadata();
            $featureFlag->scope = $tenantId !== null ? 'tenant' : 'global';

            yield $featureFlag;
        }
    }
}
