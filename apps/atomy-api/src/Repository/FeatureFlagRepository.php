<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FeatureFlag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Symfony\Component\Uid\Ulid;

/**
 * Doctrine repository for FeatureFlag entities.
 *
 * This is the Doctrine-specific repository for entity operations.
 * For Nexus FeatureFlags package integration, use FeatureFlagAdapter
 * which implements FlagRepositoryInterface.
 *
 * @extends ServiceEntityRepository<FeatureFlag>
 */
final class FeatureFlagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureFlag::class);
    }

    /**
     * Find all flags for a specific tenant.
     *
     * @param string $tenantId
     * @return array<FeatureFlag>
     */
    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId], ['name' => 'ASC']);
    }

    /**
     * Find a flag by ID.
     *
     * @param string $id
     * @return FeatureFlag|null
     */
    public function findById(string $id): ?FeatureFlag
    {
        return parent::find($id);
    }

    /**
     * Find a flag by name and tenant.
     *
     * @param string $name
     * @param string $tenantId
     * @return FeatureFlag|null
     */
    public function findByNameAndTenant(string $name, string $tenantId): ?FeatureFlag
    {
        return $this->findOneBy([
            'name' => $name,
            'tenantId' => $tenantId,
        ]);
    }

    /**
     * Find multiple flags by names.
     *
     * @param array<string> $names
     * @param string|null $tenantId
     * @return array<FeatureFlag>
     */
    public function findByNames(array $names, ?string $tenantId = null): array
    {
        if (empty($names)) {
            return [];
        }

        $qb = $this->createQueryBuilder('f')
            ->where('f.name IN (:names)')
            ->setParameter('names', $names);

        if ($tenantId !== null) {
            $qb->andWhere('f.tenantId = :tenantId')
                ->setParameter('tenantId', $tenantId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all flags, optionally filtered by tenant.
     *
     * @param string|null $tenantId
     * @return array<FeatureFlag>
     */
    public function findAllFlags(?string $tenantId = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.name', 'ASC');

        if ($tenantId !== null) {
            $qb->where('f.tenantId = :tenantId')
                ->setParameter('tenantId', $tenantId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Delete a flag by name and tenant.
     *
     * @param string $name
     * @param string|null $tenantId
     * @return void
     */
    public function deleteByNameAndTenant(string $name, ?string $tenantId = null): void
    {
        $qb = $this->createQueryBuilder('f')
            ->delete()
            ->where('f.name = :name')
            ->setParameter('name', $name);

        if ($tenantId !== null) {
            $qb->andWhere('f.tenantId = :tenantId')
                ->setParameter('tenantId', $tenantId);
        }

        $qb->getQuery()->execute();
    }

    /**
     * Persist a flag entity.
     *
     * @param FeatureFlag $flag
     * @return void
     */
    public function persist(FeatureFlag $flag): void
    {
        $em = $this->getEntityManager();
        $em->persist($flag);
        $em->flush();
    }

    /**
     * Create a new feature flag.
     *
     * @param array<string, mixed> $data
     * @return FeatureFlag
     */
    public function create(array $data): FeatureFlag
    {
        $em = $this->getEntityManager();

        $id = $data['id'] ?? (string) new Ulid();
        $strategy = is_string($data['strategy'] ?? null)
            ? FlagStrategy::from($data['strategy'])
            : ($data['strategy'] ?? FlagStrategy::SYSTEM_WIDE);

        $override = null;
        if (isset($data['override']) && $data['override'] !== null) {
            $override = is_string($data['override'])
                ? FlagOverride::from($data['override'])
                : $data['override'];
        }

        $flag = new FeatureFlag(
            id: $id,
            tenantId: $data['tenant_id'],
            name: $data['name'],
            enabled: $data['enabled'] ?? false,
            strategy: $strategy,
            value: $data['value'] ?? null,
            description: $data['description'] ?? null,
            override: $override,
            metadata: $data['metadata'] ?? [],
            createdBy: $data['created_by'] ?? null
        );

        $em->persist($flag);
        $em->flush();

        return $flag;
    }

    /**
     * Update an existing feature flag.
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return FeatureFlag
     * @throws \RuntimeException If flag not found
     */
    public function update(string $id, array $data): FeatureFlag
    {
        $flag = $this->findById($id);
        if ($flag === null) {
            throw new \RuntimeException("Feature flag not found: {$id}");
        }

        if (isset($data['enabled'])) {
            $flag->setEnabled($data['enabled']);
        }

        if (isset($data['description'])) {
            $flag->setDescription($data['description']);
        }

        if (isset($data['strategy'])) {
            $strategy = is_string($data['strategy'])
                ? FlagStrategy::from($data['strategy'])
                : $data['strategy'];
            $flag->setStrategy($strategy);
        }

        if (array_key_exists('value', $data)) {
            $flag->setValue($data['value']);
        }

        if (array_key_exists('override', $data)) {
            $override = null;
            if ($data['override'] !== null) {
                $override = is_string($data['override'])
                    ? FlagOverride::from($data['override'])
                    : $data['override'];
            }
            $flag->setOverride($override);
        }

        if (isset($data['metadata'])) {
            $flag->setMetadata($data['metadata']);
        }

        if (isset($data['updated_by'])) {
            $flag->setUpdatedBy($data['updated_by']);
        }

        $this->getEntityManager()->flush();

        return $flag;
    }

    /**
     * Delete a flag by ID.
     *
     * @param string $id
     * @return void
     */
    public function deleteById(string $id): void
    {
        $flag = $this->findById($id);
        if ($flag !== null) {
            $em = $this->getEntityManager();
            $em->remove($flag);
            $em->flush();
        }
    }

    /**
     * Check if a flag name exists for a tenant.
     *
     * @param string $name
     * @param string $tenantId
     * @param string|null $excludeId Exclude this ID from check (for updates)
     * @return bool
     */
    public function nameExists(string $name, string $tenantId, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.name = :name')
            ->andWhere('f.tenantId = :tenantId')
            ->setParameter('name', $name)
            ->setParameter('tenantId', $tenantId);

        if ($excludeId !== null) {
            $qb->andWhere('f.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Toggle the enabled state of a flag.
     *
     * @param string $id
     * @return FeatureFlag
     */
    public function toggle(string $id): FeatureFlag
    {
        $flag = $this->findById($id);
        if ($flag === null) {
            throw new \RuntimeException("Feature flag not found: {$id}");
        }

        $flag->setEnabled(!$flag->isEnabled());
        $this->getEntityManager()->flush();

        return $flag;
    }

    /**
     * Get flag count by tenant.
     *
     * @param string $tenantId
     * @return int
     */
    public function countByTenant(string $tenantId): int
    {
        return $this->count(['tenantId' => $tenantId]);
    }

    /**
     * Get enabled flags count by tenant.
     *
     * @param string $tenantId
     * @return int
     */
    public function countEnabledByTenant(string $tenantId): int
    {
        return $this->count(['tenantId' => $tenantId, 'enabled' => true]);
    }
}
