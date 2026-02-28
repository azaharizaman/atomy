<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FeatureFlag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;

/**
 * Feature Flag Repository.
 * 
 * @extends ServiceEntityRepository<FeatureFlag>
 */
final class FeatureFlagRepository extends ServiceEntityRepository implements FlagRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureFlag::class);
    }

    public function findByName(string $name, ?string $tenantId = null): ?FlagDefinitionInterface
    {
        // Tenant inheritance:
        // 1. Check for tenant-specific flag
        if ($tenantId !== null) {
            $flag = $this->findOneBy(['name' => $name, 'tenantId' => $tenantId]);
            if ($flag) return $flag;
        }

        // 2. Check for global flag
        return $this->findOneBy(['name' => $name, 'tenantId' => null]);
    }

    public function findMany(array $names, ?string $tenantId = null): array
    {
        $flags = [];
        foreach ($names as $name) {
            $flag = $this->findByName($name, $tenantId);
            if ($flag) {
                $flags[$name] = $flag;
            }
        }
        return $flags;
    }

    public function save(FlagDefinitionInterface $flag): void
    {
        if (!$flag instanceof FeatureFlag) {
            throw new \InvalidArgumentException('Flag must be an instance of ' . FeatureFlag::class);
        }

        $this->getEntityManager()->persist($flag);
        $this->getEntityManager()->flush();
    }

    public function saveForTenant(FlagDefinitionInterface $flag, ?string $tenantId = null): void
    {
        if (!$flag instanceof FeatureFlag) {
            throw new \InvalidArgumentException('Flag must be an instance of ' . FeatureFlag::class);
        }

        $flag->setTenantId($tenantId);
        $this->save($flag);
    }

    public function delete(string $name, ?string $tenantId = null): void
    {
        $flag = $this->findOneBy(['name' => $name, 'tenantId' => $tenantId]);
        if ($flag) {
            $this->getEntityManager()->remove($flag);
            $this->getEntityManager()->flush();
        }
    }

    public function all(?string $tenantId = null): array
    {
        if ($tenantId === null) {
            return $this->findBy(['tenantId' => null]);
        }

        // Return tenant flags with global fallback for missing ones
        $globalFlags = $this->findBy(['tenantId' => null]);
        $tenantFlags = $this->findBy(['tenantId' => $tenantId]);

        $flags = [];
        foreach ($globalFlags as $flag) {
            $flags[$flag->getName()] = $flag;
        }
        foreach ($tenantFlags as $flag) {
            $flags[$flag->getName()] = $flag;
        }

        return array_values($flags);
    }
}
