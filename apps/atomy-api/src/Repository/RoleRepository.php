<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Permission;
use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Domain\Identity\Contracts\PermissionInterface;
use Nexus\Domain\Identity\Contracts\RoleInterface;
use Nexus\Domain\Identity\Contracts\RoleRepositoryInterface;
use Nexus\Domain\Identity\Exceptions\RoleNotFoundException;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<Role>
 */
final class RoleRepository extends ServiceEntityRepository implements RoleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function findById(string $id): RoleInterface
    {
        $role = $this->find($id);
        if ($role === null) {
            throw new RoleNotFoundException("Role with ID '$id' not found");
        }
        return $role;
    }

    public function findByName(string $name, ?string $tenantId = null): RoleInterface
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.name = :name')
            ->setParameter('name', $name);

        if ($tenantId !== null) {
            $qb->andWhere('r.tenantId = :tenantId')->setParameter('tenantId', $tenantId);
        } else {
            $qb->andWhere('r.tenantId IS NULL');
        }

        $role = $qb->getQuery()->getOneOrNullResult();
        if ($role === null) {
            throw new RoleNotFoundException("Role with name '$name' not found");
        }
        return $role;
    }

    public function findByNameOrNull(string $name, ?string $tenantId = null): ?RoleInterface
    {
        try {
            return $this->findByName($name, $tenantId);
        } catch (RoleNotFoundException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): RoleInterface
    {
        $role = new Role(
            id: (string) new Ulid(),
            name: $data['name'],
            description: $data['description'] ?? null,
            tenantId: $data['tenant_id'] ?? null,
            systemRole: $data['system_role'] ?? false,
            parentRoleId: $data['parent_role_id'] ?? null,
        );

        $em = $this->getEntityManager();
        $em->persist($role);
        $em->flush();

        return $role;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): RoleInterface
    {
        $role = $this->findById($id);
        assert($role instanceof Role);

        if (isset($data['name'])) {
            $role->setName($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $role->setDescription($data['description']);
        }
        if (array_key_exists('parent_role_id', $data)) {
            $role->setParentRoleId($data['parent_role_id']);
        }
        if (isset($data['requires_mfa'])) {
            $role->setRequiresMfa($data['requires_mfa']);
        }

        $this->getEntityManager()->flush();
        return $role;
    }

    public function delete(string $id): bool
    {
        $role = $this->findById($id);
        assert($role instanceof Role);

        if ($role->isSystemRole()) {
            throw new \RuntimeException('Cannot delete system role');
        }

        if ($this->hasUsers($id)) {
            throw new \RuntimeException('Cannot delete role: still assigned to users');
        }

        $em = $this->getEntityManager();
        $em->remove($role);
        $em->flush();

        return true;
    }

    public function nameExists(string $name, ?string $tenantId = null, ?string $excludeRoleId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.name = :name')
            ->setParameter('name', $name);

        if ($tenantId !== null) {
            $qb->andWhere('r.tenantId = :tenantId')->setParameter('tenantId', $tenantId);
        } else {
            $qb->andWhere('r.tenantId IS NULL');
        }

        if ($excludeRoleId !== null) {
            $qb->andWhere('r.id != :excludeId')->setParameter('excludeId', $excludeRoleId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @return PermissionInterface[]
     */
    public function getRolePermissions(string $roleId): array
    {
        $role = $this->findById($roleId);
        assert($role instanceof Role);
        return $role->getPermissions()->toArray();
    }

    public function assignPermission(string $roleId, string $permissionId): void
    {
        $role = $this->findById($roleId);
        assert($role instanceof Role);

        $permission = $this->getEntityManager()->find(Permission::class, $permissionId);
        if ($permission === null) {
            throw new \RuntimeException("Permission with ID '$permissionId' not found");
        }

        $role->addPermission($permission);
        $this->getEntityManager()->flush();
    }

    public function revokePermission(string $roleId, string $permissionId): void
    {
        $role = $this->findById($roleId);
        assert($role instanceof Role);

        $permission = $this->getEntityManager()->find(Permission::class, $permissionId);
        if ($permission !== null) {
            $role->removePermission($permission);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return RoleInterface[]
     */
    public function getAll(?string $tenantId = null): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($tenantId !== null) {
            $qb->where('r.tenantId = :tenantId OR r.tenantId IS NULL')
               ->setParameter('tenantId', $tenantId);
        }

        $qb->orderBy('r.name', 'ASC');
        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<string, string>
     */
    public function getRoleHierarchy(?string $tenantId = null): array
    {
        $roles = $this->getAll($tenantId);
        $hierarchy = [];
        foreach ($roles as $role) {
            if ($role->getParentRoleId() !== null) {
                $hierarchy[$role->getId()] = $role->getParentRoleId();
            }
        }
        return $hierarchy;
    }

    public function hasUsers(string $roleId): bool
    {
        // Check if any user has this role in their roles array
        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery(
            "SELECT COUNT(*) FROM users WHERE roles LIKE :pattern",
            ['pattern' => '%"ROLE_' . $roleId . '"%']
        );
        return (int) $result->fetchOne() > 0;
    }

    public function countUsers(string $roleId): int
    {
        // Count users with this role in their roles array
        $conn = $this->getEntityManager()->getConnection();
        $result = $conn->executeQuery(
            "SELECT COUNT(*) FROM users WHERE roles LIKE :pattern",
            ['pattern' => '%"ROLE_' . $roleId . '"%']
        );
        return (int) $result->fetchOne();
    }
}
