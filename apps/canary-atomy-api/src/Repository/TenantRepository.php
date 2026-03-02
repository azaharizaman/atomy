<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantPersistenceInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\Tenant\Contracts\TenantRepositoryInterface;
use Nexus\Tenant\Enums\TenantStatus;
use Nexus\Tenant\Exceptions\TenantNotFoundException;

/**
 * Tenant Repository.
 * 
 * Implements all Tenant package interfaces using Doctrine.
 * 
 * @extends ServiceEntityRepository<Tenant>
 */
final class TenantRepository extends ServiceEntityRepository implements TenantRepositoryInterface, TenantPersistenceInterface, TenantQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tenant::class);
    }

    /**
     * @throws TenantNotFoundException
     */
    private function getOrFail(string $id): Tenant
    {
        $tenant = $this->find($id);
        if (!$tenant instanceof Tenant) {
            throw new TenantNotFoundException(sprintf('Tenant with ID "%s" not found', $id));
        }
        return $tenant;
    }

    // TenantQueryInterface & TenantRepositoryInterface
    public function findById(string $id): ?TenantInterface { return $this->find($id); }
    public function findByCode(string $code): ?TenantInterface { return $this->findOneBy(['code' => $code]); }
    public function findByDomain(string $domain): ?TenantInterface { return $this->findOneBy(['domain' => $domain]); }
    public function findBySubdomain(string $subdomain): ?TenantInterface { return $this->findOneBy(['subdomain' => $subdomain]); }
    
    public function all(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        // Simple implementation for the repository interface
        $queryBuilder = $this->createQueryBuilder('t');
        
        // In a real app, we'd add filter logic here
        
        $total = count($queryBuilder->getQuery()->getResult());
        $data = $queryBuilder
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    // TenantPersistenceInterface & TenantRepositoryInterface
    public function create(array $data): TenantInterface
    {
        $tenant = new Tenant(
            $data['code'],
            $data['name'],
            $data['email']
        );

        if (isset($data['status'])) {
            $tenant->setStatus(TenantStatus::from($data['status']));
        }
        
        if (isset($data['domain'])) $tenant->setDomain($data['domain']);
        if (isset($data['subdomain'])) $tenant->setSubdomain($data['subdomain']);
        if (isset($data['metadata'])) $tenant->setMetadata($data['metadata']);

        $this->getEntityManager()->persist($tenant);
        $this->getEntityManager()->flush();

        return $tenant;
    }

    public function update(string $id, array $data): TenantInterface
    {
        $tenant = $this->getOrFail($id);

        if (isset($data['name'])) {
            // We need a setter for name, or use reflection
            $reflection = new \ReflectionClass($tenant);
            $property = $reflection->getProperty('name');
            $property->setValue($tenant, $data['name']);
        }

        if (isset($data['status'])) $tenant->setStatus(TenantStatus::from($data['status']));
        if (isset($data['domain'])) $tenant->setDomain($data['domain']);
        if (isset($data['subdomain'])) $tenant->setSubdomain($data['subdomain']);
        if (isset($data['metadata'])) $tenant->setMetadata($data['metadata']);

        $this->getEntityManager()->flush();

        return $tenant;
    }

    public function delete(string $id): bool
    {
        $tenant = $this->getOrFail($id);
        
        // Soft delete implementation
        $reflection = new \ReflectionClass($tenant);
        $property = $reflection->getProperty('deletedAt');
        $property->setValue($tenant, new \DateTimeImmutable());
        
        $this->getEntityManager()->flush();
        return true;
    }

    public function forceDelete(string $id): bool
    {
        $tenant = $this->getOrFail($id);
        $this->getEntityManager()->remove($tenant);
        $this->getEntityManager()->flush();
        return true;
    }

    public function restore(string $id): TenantInterface
    {
        $tenant = $this->getOrFail($id);
        $reflection = new \ReflectionClass($tenant);
        $property = $reflection->getProperty('deletedAt');
        $property->setValue($tenant, null);
        
        $this->getEntityManager()->flush();
        return $tenant;
    }

    // Legacy Repository Interface Methods
    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.code = :code')
            ->setParameter('code', $code);
        
        if ($excludeId) {
            $qb->andWhere('t.id != :id')
               ->setParameter('id', $excludeId);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function domainExists(string $domain, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.domain = :domain')
            ->setParameter('domain', $domain);
        
        if ($excludeId) {
            $qb->andWhere('t.id != :id')
               ->setParameter('id', $excludeId);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getActive(): array { return $this->findBy(['status' => TenantStatus::Active]); }
    public function getSuspended(): array { return $this->findBy(['status' => TenantStatus::Suspended]); }
    public function getTrials(): array { return $this->findBy(['status' => TenantStatus::Trial]); }
    
    public function getExpiredTrials(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.trialEndsAt < :now')
            ->setParameter('status', TenantStatus::Trial)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function getChildren(string $parentId): array { return $this->findBy(['parentId' => $parentId]); }

    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('t.status, count(t.id) as count')
            ->groupBy('t.status');
        
        $results = $qb->getQuery()->getResult();
        $stats = [
            'total' => 0,
            'active' => 0,
            'suspended' => 0,
            'trial' => 0,
            'archived' => 0,
        ];

        foreach ($results as $row) {
            $status = $row['status'] instanceof TenantStatus ? $row['status']->value : $row['status'];
            $stats[$status] = (int) $row['count'];
            $stats['total'] += (int) $row['count'];
        }

        return $stats;
    }

    public function search(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.name LIKE :q OR t.code LIKE :q OR t.email LIKE :q')
            ->setParameter('q', '%'.$query.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
