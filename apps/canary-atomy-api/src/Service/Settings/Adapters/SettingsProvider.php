<?php

declare(strict_types=1);

namespace App\Service\Settings\Adapters;

use App\Repository\SettingRepository;
use App\Service\TenantContext;
use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;
use Nexus\TenantOperations\Exceptions\TenantMismatchException;

final readonly class SettingsProvider implements SettingsProviderInterface
{
    public function __construct(
        private SettingRepository $settingRepository,
        private TenantContext $tenantContext
    ) {}

    private function validateTenant(string $tenantId): void
    {
        $currentId = $this->tenantContext->getCurrentTenantId();
        if ($currentId !== null && $tenantId !== $currentId) {
            throw TenantMismatchException::forTenant($tenantId, $currentId);
        }
    }

    public function getSetting(string $key, string $tenantId): ?array
    {
        $this->validateTenant($tenantId);
        $value = $this->settingRepository->get($key);
        if ($value === null) return null;

        return [
            'key' => $key,
            'value' => $value,
            'type' => gettype($value),
            'scope' => 'tenant',
        ];
    }

    public function getAllSettings(string $tenantId): array
    {
        $this->validateTenant($tenantId);
        return $this->settingRepository->getAll();
    }

    public function resolveSettingValue(string $key, ?string $tenantId, ?string $userId): mixed
    {
        if ($tenantId !== null) {
            $this->validateTenant($tenantId);
        }
        return $this->settingRepository->get($key);
    }

    public function settingExists(string $key, string $tenantId): bool
    {
        $this->validateTenant($tenantId);
        return $this->settingRepository->has($key);
    }

    public function getSettingsByCategory(string $category, string $tenantId): array
    {
        $this->validateTenant($tenantId);
        return $this->settingRepository->getAll();
    }
}
