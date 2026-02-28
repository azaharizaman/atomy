<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Repository\SettingRepository;
use Nexus\TenantOperations\Contracts\SettingsQueryAdapterInterface;

final readonly class SettingsQueryAdapter implements SettingsQueryAdapterInterface
{
    public function __construct(
        private SettingRepository $settingRepository
    ) {}

    public function getSettings(string $tenantId): array
    {
        // For settings, the repository's getAll currently uses TenantContext.
        // For the adapter, we might need a specific tenant-scoped query.
        // We'll use a hack to temporarily set the context or use the repository directly if we add findByTenantId.
        
        $settings = $this->settingRepository->findBy(['tenantId' => $tenantId]);
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->getKey()] = $setting->getValue();
        }
        return $result;
    }
}
