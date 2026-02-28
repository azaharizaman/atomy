<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Entity\FeatureFlag;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\TenantOperations\Contracts\FeatureConfiguratorAdapterInterface;

final readonly class FeatureConfiguratorAdapter implements FeatureConfiguratorAdapterInterface
{
    public function __construct(
        private FlagRepositoryInterface $flagRepository
    ) {}

    public function configure(string $tenantId, array $features): void
    {
        if (empty($features)) {
            $features = [
                'analytics' => true,
                'reporting' => true,
            ];
        }

        // Fetch all flags for this tenant once
        $existingFlags = $this->flagRepository->all($tenantId);
        $flagsByName = [];
        foreach ($existingFlags as $f) {
            // We only care about tenant-specific flags here to avoid mutating global fallbacks
            if ($f->getTenantId() === $tenantId) {
                $flagsByName[$f->getName()] = $f;
            }
        }

        foreach ($features as $name => $enabled) {
            $flag = $flagsByName[$name] ?? null;
            
            if (!$flag) {
                $flag = new FeatureFlag($name);
            }

            if ($flag instanceof FeatureFlag) {
                $flag->setEnabled((bool)$enabled);
                $this->flagRepository->saveForTenant($flag, $tenantId);
            }
        }
    }
}
