<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Identity\Contracts\PermissionInterface;
use Nexus\Identity\Contracts\PermissionRepositoryInterface;
use Nexus\Identity\Exceptions\PermissionNotFoundException;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<Permission>
 */
final class PermissionRepository extends ServiceEntityRepository implements PermissionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    public function findById(string $id): PermissionInterface
    {
        $permission = $this->find($id);
        if ($permission === null) {
            throw new PermissionNotFoundException("Permission with ID '$id' not found");
        }
        return $permission;
    }

    public function findByName(string $name): PermissionInterface
    {
        $permission = $this->findOneBy(['name' => $name]);
        if ($permission === null) {
            throw new PermissionNotFoundException("Permission with name '$name' not found");
        }
        return $permission;
    }

    public function findByNameOrNull(string $name): ?PermissionInterface
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): PermissionInterface
    {
        $permission = new Permission(
            id: (string) new Ulid(),
            name: $data['name'],
            resource: $data['resource'],
            action: $data['action'],
            description: $data['description'] ?? null,
        );

        $em = $this->getEntityManager();
        $em->persist($permission);
        $em->flush();

        return $permission;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): PermissionInterface
    {
        $permission = $this->findById($id);
        assert($permission instanceof Permission);

        if (isset($data['name'])) {
            $permission->setName($data['name']);
        }
        if (isset($data['resource'])) {
            $permission->setResource($data['resource']);
        }
        if (isset($data['action'])) {
            $permission->setAction($data['action']);
        }
        if (array_key_exists('description', $data)) {
            $permission->setDescription($data['description']);
        }

        $this->getEntityManager()->flush();
        return $permission;
    }

    public function delete(string $id): bool
    {
        $permission = $this->findById($id);
        assert($permission instanceof Permission);

        $em = $this->getEntityManager();
        $em->remove($permission);
        $em->flush();

        return true;
    }

    public function nameExists(string $name, ?string $excludePermissionId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.name = :name')
            ->setParameter('name', $name);

        if ($excludePermissionId !== null) {
            $qb->andWhere('p.id != :excludeId')->setParameter('excludeId', $excludePermissionId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return PermissionInterface[]
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.resource', 'ASC')
            ->addOrderBy('p.action', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PermissionInterface[]
     */
    public function findByResource(string $resource): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.resource = :resource')
            ->setParameter('resource', $resource)
            ->orderBy('p.action', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PermissionInterface[]
     */
    public function findMatching(string $permissionName): array
    {
        // Handle wildcard matching: "users.*" matches "users.create", "users.read", etc.
        if (str_ends_with($permissionName, '.*')) {
            $resource = substr($permissionName, 0, -2);
            return $this->findByResource($resource);
        }

        // Exact match
        $permission = $this->findByNameOrNull($permissionName);
        return $permission !== null ? [$permission] : [];
    }
}
