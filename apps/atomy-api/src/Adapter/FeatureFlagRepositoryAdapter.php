<?php

declare(strict_types=1);

namespace App\Adapter;

use App\Entity\FeatureFlag;
use App\Repository\FeatureFlagRepository;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Symfony\Component\Uid\Ulid;

/**
 * Adapter implementing FlagRepositoryInterface for Nexus FeatureFlags package.
 *
 * This adapter bridges the Doctrine FeatureFlagRepository with the
 * package's FlagRepositoryInterface contract, following the Adapter pattern.
 *
 * @implements FlagRepositoryInterface
 */
final readonly class FeatureFlagRepositoryAdapter implements FlagRepositoryInterface
{
    public function __construct(
        private FeatureFlagRepository $repository
    ) {}

    /**
     * {@inheritdoc}
     */
    public function find(string $name, ?string $tenantId = null): ?FlagDefinitionInterface
    {
        if ($tenantId === null) {
            // Without tenant context, we cannot look up the flag
            return null;
        }

        return $this->repository->findByNameAndTenant($name, $tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function findMany(array $names, ?string $tenantId = null): array
    {
        if (empty($names)) {
            return [];
        }

        return $this->repository->findByNames($names, $tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function save(FlagDefinitionInterface $flag): void
    {
        if ($flag instanceof FeatureFlag) {
            $this->repository->persist($flag);
            return;
        }

        // If it's not our entity, we need to find or create it
        $tenantId = $this->extractTenantId($flag);
        if ($tenantId === null) {
            throw new \RuntimeException('Cannot save flag without tenant context');
        }

        $existing = $this->repository->findByNameAndTenant($flag->getName(), $tenantId);

        if ($existing !== null) {
            // Update existing flag
            $existing->setEnabled($flag->isEnabled());
            $existing->setStrategy($flag->getStrategy());
            $existing->setValue($flag->getValue());
            $existing->setOverride($flag->getOverride());
            $existing->setMetadata($flag->getMetadata());
            $this->repository->persist($existing);
        } else {
            // Create new flag from definition
            $this->repository->create([
                'tenant_id' => $tenantId,
                'name' => $flag->getName(),
                'enabled' => $flag->isEnabled(),
                'strategy' => $flag->getStrategy(),
                'value' => $flag->getValue(),
                'override' => $flag->getOverride(),
                'metadata' => $flag->getMetadata(),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name, ?string $tenantId = null): void
    {
        $this->repository->deleteByNameAndTenant($name, $tenantId);
    }

    /**
     * {@inheritdoc}
     */
    public function all(?string $tenantId = null): array
    {
        return $this->repository->findAllFlags($tenantId);
    }

    /**
     * Extract tenant ID from a flag definition.
     *
     * @param FlagDefinitionInterface $flag
     * @return string|null
     */
    private function extractTenantId(FlagDefinitionInterface $flag): ?string
    {
        // If it's our entity, we can get the tenant ID directly
        if ($flag instanceof FeatureFlag) {
            return $flag->getTenantId();
        }

        // Try to get from metadata
        $metadata = $flag->getMetadata();
        if (isset($metadata['tenant_id'])) {
            return $metadata['tenant_id'];
        }

        return null;
    }
}
