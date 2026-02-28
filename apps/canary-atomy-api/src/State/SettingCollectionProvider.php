<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Setting as SettingResource;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\SettingsCoordinatorInterface;

/**
 * Collection provider for Setting resource.
 *
 * Fetches settings for the current tenant using the SettingsManagement orchestrator.
 * Multi-tenant aware - filters settings by tenant context.
 */
final class SettingCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly SettingsCoordinatorInterface $settingsCoordinator,
        private readonly TenantContext $tenantContext
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return iterable<SettingResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        // Get tenant ID for scoping
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if (!$tenantId) {
            return [];
        }

        // Get all settings for the current tenant
        $settings = $this->settingsCoordinator->getAllSettings($tenantId);

        foreach ($settings as $key => $value) {
            $setting = new SettingResource();
            $setting->key = $key;
            $setting->value = $value;
            $setting->type = gettype($value);
            $setting->isEncrypted = false;
            $setting->scope = 'tenant';
            $setting->isReadOnly = false;

            yield $setting;
        }
    }
}
