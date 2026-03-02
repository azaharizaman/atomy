<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Setting as SettingResource;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\SettingsCoordinatorInterface;

/**
 * Item provider for Setting resource.
 */
final class SettingItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly SettingsCoordinatorInterface $settingsCoordinator,
        private readonly TenantContext $tenantContext
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?SettingResource
    {
        $key = $uriVariables['key'] ?? null;
        if (!$key) {
            return null;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();

        // Resolve setting value from hierarchy
        $value = $this->settingsCoordinator->resolveSettingValue($key, $tenantId, null);

        if ($value === null) {
            return null;
        }

        $resource = new SettingResource();
        $resource->key = $key;
        $resource->value = $value;
        $resource->type = gettype($value);
        $resource->scope = $tenantId ? 'tenant' : 'application';

        return $resource;
    }
}
