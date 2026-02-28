<?php

declare(strict_types=1);

namespace App\Service\Tenant\Adapters;

use App\Repository\FeatureFlagRepository;
use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\TenantOperations\Contracts\FeatureQueryAdapterInterface;

final readonly class FeatureQueryAdapter implements FeatureQueryAdapterInterface
{
    public function __construct(
        private FeatureFlagRepository $repository,
        private FeatureFlagManagerInterface $manager
    ) {}

    public function isEnabled(string $tenantId, string $featureKey): bool
    {
        return $this->manager->isEnabled($featureKey, ['tenant_id' => $tenantId]);
    }

    public function getAll(string $tenantId): array
    {
        return $this->getFeatures($tenantId);
    }

    public function getFeatures(string $tenantId): array
    {
        $all = $this->repository->all($tenantId);
        $result = [];
        foreach ($all as $flag) {
            $result[$flag->getName()] = $this->isEnabled($tenantId, $flag->getName());
        }
        return $result;
    }
}
