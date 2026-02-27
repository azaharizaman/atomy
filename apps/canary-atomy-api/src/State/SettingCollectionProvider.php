<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Setting as SettingResource;
use App\Service\TenantContext;
use Nexus\Setting\Contracts\SettingRepositoryInterface;

/**
 * Collection provider for Setting resource.
 *
 * Fetches settings for the current tenant using the Setting package.
 * Multi-tenant aware - filters settings by tenant context.
 */
final class SettingCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly SettingRepositoryInterface $settingRepository,
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
        // Get all settings for the current tenant
        $settings = $this->settingRepository->getAll();

        foreach ($settings as $key => $value) {
            $setting = new SettingResource();
            $setting->key = $key;
            $setting->value = $value;
            $setting->type = gettype($value);
            $setting->isEncrypted = false;
            $setting->scope = $this->tenantContext->hasTenant() ? 'tenant' : 'application';
            $setting->isReadOnly = !$this->settingRepository->isWritable();

            yield $setting;
        }
    }
}
