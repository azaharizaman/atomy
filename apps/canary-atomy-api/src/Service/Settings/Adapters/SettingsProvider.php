<?php

declare(strict_types=1);

namespace App\Service\Settings\Adapters;

use App\Repository\SettingRepository;
use Nexus\SettingsManagement\Contracts\SettingsProviderInterface;

final readonly class SettingsProvider implements SettingsProviderInterface
{
    public function __construct(
        private SettingRepository $settingRepository
    ) {}

    public function getSetting(string $key, string $tenantId): ?array
    {
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
        return $this->settingRepository->getAll();
    }

    public function resolveSettingValue(string $key, ?string $tenantId, ?string $userId): mixed
    {
        return $this->settingRepository->get($key);
    }

    public function settingExists(string $key, string $tenantId): bool
    {
        return $this->settingRepository->has($key);
    }

    public function getSettingsByCategory(string $category, string $tenantId): array
    {
        // For simplicity, return all as we don't have categories in repo yet
        return $this->settingRepository->getAll();
    }
}
