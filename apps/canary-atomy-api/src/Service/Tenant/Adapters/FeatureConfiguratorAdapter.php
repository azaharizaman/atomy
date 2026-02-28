<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Repository\FeatureFlagRepository;
use Nexus\TenantOperations\Contracts\FeatureConfiguratorAdapterInterface;

final readonly class FeatureConfiguratorAdapter implements FeatureConfiguratorAdapterInterface
{
    public function __construct(
        private FeatureFlagRepository $flagRepository
    ) {}

    public function configure(string $tenantId, array $features): void
    {
        foreach ($features as $name => $enabled) {
            $this->flagRepository->update($name, ['enabled' => (bool)$enabled], $tenantId);
        }

        // Defaults
        if (empty($features)) {
            $this->flagRepository->update('analytics', ['enabled' => true], $tenantId);
            $this->flagRepository->update('reporting', ['enabled' => true], $tenantId);
        }
    }
}
