<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;

/**
 * @extends ServiceEntityRepository<Tenant>
 */
class TenantRepository extends ServiceEntityRepository implements TenantQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    public function findById(string $id): ?TenantInterface
    {
        return $this->find($id);
    }

    public function findByCode(string $code): ?TenantInterface
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findByDomain(string $domain): ?TenantInterface
    {
        return $this->findOneBy(['domain' => $domain]);
    }

    public function findBySubdomain(string $subdomain): ?TenantInterface
    {
        return $this->findOneBy(['subdomain' => $subdomain]);
    }

    public function all(): array
    {
        return $this->findAll();
    }

    public function getChildren(string $parentId): array
    {
        return []; // Not implemented for canary
    }
}
