<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Entity\FeatureFlag;
use App\Repository\FeatureFlagRepository;
use Nexus\TenantOperations\Contracts\FeatureConfiguratorAdapterInterface;

final readonly class FeatureConfiguratorAdapter implements FeatureConfiguratorAdapterInterface
{
    public function __construct(
        private FeatureFlagRepository $flagRepository
    ) {}

    public function configure(string $tenantId, array $features): void
    {
        if (empty($features)) {
            $features = [
                'analytics' => true,
                'reporting' => true,
            ];
        }

        foreach ($features as $name => $enabled) {
            $flag = $this->flagRepository->findByName($name, $tenantId);
            
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
