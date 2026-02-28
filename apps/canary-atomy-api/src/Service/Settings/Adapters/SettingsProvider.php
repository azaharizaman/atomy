<?php

declare(strict_types=1);

namespace App\Service\Settings\Adapters;

use App\Repository\SettingPersistRepository;
use App\Service\TenantContext;
use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;
use Nexus\SettingsManagement\Contracts\SettingsPersistProviderInterface;
use Nexus\TenantOperations\Exceptions\TenantMismatchException;

final readonly class SettingsProvider implements SettingsProviderInterface, SettingsPersistProviderInterface
{
    public function __construct(
        private SettingRepositoryInterface $settingRepository,
        private SettingPersistRepository $persistRepository,
        private TenantContext $tenantContext
    ) {}

    private function validateTenant(?string $tenantId): void
    {
        if ($tenantId === null) return;
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
        // User-level resolution logic could be added here if supported by repo
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
        // Using prefix as a proxy for category
        return $this->settingRepository->getByPrefix($category);
    }

    public function update(string $key, mixed $value, ?string $tenantId = null, ?string $userId = null): void
    {
        $this->validateTenant($tenantId);
        $this->persistRepository->setForTenant($key, $value, $tenantId);
    }

    public function delete(string $key, ?string $tenantId = null, ?string $userId = null): void
    {
        $this->validateTenant($tenantId);
        $this->persistRepository->deleteForTenant($key, $tenantId);
    }

    public function bulkUpdate(array $settings, ?string $tenantId = null, ?string $userId = null): void
    {
        $this->validateTenant($tenantId);
        foreach ($settings as $key => $value) {
            $this->persistRepository->setForTenant($key, $value, $tenantId);
        }
    }
}
