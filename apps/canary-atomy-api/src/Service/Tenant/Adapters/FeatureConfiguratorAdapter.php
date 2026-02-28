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

        foreach ($features as $name => $enabled) {
            // Find specifically for this tenant, avoiding global fallback
            $flags = $this->flagRepository->all($tenantId);
            $flag = null;
            foreach ($flags as $f) {
                if ($f->getName() === $name && $f->getTenantId() === $tenantId) {
                    $flag = $f;
                    break;
                }
            }
            
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
