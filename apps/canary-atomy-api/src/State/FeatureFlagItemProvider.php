<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\FeatureFlag as FeatureFlagResource;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\FeatureFlagCoordinatorInterface;

/**
 * Item provider for FeatureFlag resource.
 */
final class FeatureFlagItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly FeatureFlagCoordinatorInterface $flagCoordinator,
        private readonly TenantContext $tenantContext
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?FeatureFlagResource
    {
        $name = $uriVariables['name'] ?? null;
        if (!$name) {
            return null;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();

        $flag = $this->flagCoordinator->getFeatureFlag($name, $tenantId);

        if (!$flag) {
            return null;
        }

        $resource = new FeatureFlagResource();
        $resource->name = $flag->getName();
        $resource->enabled = $flag->isEnabled();
        $resource->strategy = $flag->getStrategy()->value;
        $resource->value = $flag->getValue();
        $resource->override = $flag->getOverride()?->value;
        $resource->metadata = $flag->getMetadata();
        $resource->scope = $tenantId ? 'tenant' : 'global';

        return $resource;
    }
}
