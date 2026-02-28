<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Repository\SettingRepository;
use Nexus\TenantOperations\Contracts\SettingsInitializerAdapterInterface;

final readonly class SettingsInitializerAdapter implements SettingsInitializerAdapterInterface
{
    public function __construct(
        private SettingRepository $settingRepository
    ) {}

    public function initialize(string $tenantId, array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->settingRepository->set($key, $value);
        }

        // Defaults if not provided
        if (!isset($settings['timezone'])) $this->settingRepository->set('timezone', 'UTC');
        if (!isset($settings['currency'])) $this->settingRepository->set('currency', 'USD');
    }
}
