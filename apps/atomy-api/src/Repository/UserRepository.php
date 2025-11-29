<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Identity\Contracts\UserInterface as NexusUserInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;

final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findById(string $id): NexusUserInterface
    {
        $user = $this->find($id);
        if (!$user instanceof User) {
            throw new UserNotFoundException("User not found: {$id}");
        }

        return $user;
    }

    public function findByEmail(string $email): NexusUserInterface
    {
        $user = $this->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            throw new UserNotFoundException("User not found: {$email}");
        }

        return $user;
    }

    public function findByEmailOrNull(string $email): ?NexusUserInterface
    {
        $user = $this->findOneBy(['email' => $email]);
        return $user instanceof User ? $user : null;
    }

    public function create(array $data): NexusUserInterface
    {
        $em = $this->getEntityManager();

        // Expect $data has id/email/password_hash/roles
        $id = $data['id'] ?? (string) new \Symfony\Component\Uid\Ulid();

        $user = new User(
            $id,
            $data['email'],
            $data['password_hash'],
            $data['roles'] ?? ['ROLE_USER'],
            $data['status'] ?? 'active',
            $data['name'] ?? null,
            $data['tenant_id'] ?? null
        );

        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function update(string $id, array $data): NexusUserInterface
    {
        $em = $this->getEntityManager();
        $user = $this->findById($id);

        if (isset($data['email'])) {
            $rc = new \ReflectionClass($user);
            $prop = $rc->getProperty('email');
            $prop->setAccessible(true);
            $prop->setValue($user, $data['email']);
        }

        if (isset($data['password_hash'])) {
            $user->setPasswordHash($data['password_hash']);
        }

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function delete(string $id): bool
    {
        $em = $this->getEntityManager();
        try {
            $user = $this->findById($id);
        } catch (UserNotFoundException $e) {
            return false;
        }

        $em->remove($user);
        $em->flush();
        return true;
    }

    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        // Use the repository query builder so the EntityManager is resolved properly
        $qb = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.email = :email')
            ->setParameter('email', $email);

        if ($excludeUserId !== null) {
            $qb->andWhere('u.id <> :exclude')->setParameter('exclude', $excludeUserId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getUserRoles(string $userId): array
    {
        $user = $this->findById($userId);
        return $user->getRoles();
    }

    public function getUserPermissions(string $userId): array
    {
        // Permissions are not implemented in this minimal repository
        return [];
    }

    public function assignRole(string $userId, string $roleId): void
    {
        $em = $this->getEntityManager();
        $user = $this->findById($userId);
        $roles = $user->getRoles();
        if (!in_array($roleId, $roles, true)) {
            $roles[] = $roleId;
            $user->setRoles($roles);
            $em->persist($user);
            $em->flush();
        }
    }

    public function revokeRole(string $userId, string $roleId): void
    {
        $em = $this->getEntityManager();
        $user = $this->findById($userId);
        $roles = array_filter($user->getRoles(), fn($r) => $r !== $roleId);
        $user->setRoles(array_values($roles));
        $em->persist($user);
        $em->flush();
    }

    public function assignPermission(string $userId, string $permissionId): void
    {
        // Not implemented in this minimal version
    }

    public function revokePermission(string $userId, string $permissionId): void
    {
        // Not implemented in this minimal version
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findByRole(string $roleId): array
    {
        return $this->createQueryBuilder('u')
            ->where('JSON_CONTAINS(u.roles, :role) = 1 OR u.roles LIKE :like')
            ->setParameter('role', json_encode($roleId))
            ->setParameter('like', '%"' . $roleId . '"%')
            ->getQuery()
            ->getResult();
    }

    public function search(array $criteria): array
    {
        $qb = $this->createQueryBuilder('u');

        if (!empty($criteria['email'])) {
            $qb->andWhere('u.email LIKE :email')->setParameter('email', '%' . $criteria['email'] . '%');
        }

        if (!empty($criteria['name'])) {
            $qb->andWhere('u.name LIKE :name')->setParameter('name', '%' . $criteria['name'] . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function updateLastLogin(string $userId): void
    {
        $em = $this->getEntityManager();
        $user = $this->findById($userId);
        $rc = new \ReflectionClass($user);
        if ($rc->hasProperty('updatedAt')) {
            $prop = $rc->getProperty('updatedAt');
            $prop->setAccessible(true);
            $prop->setValue($user, new \DateTimeImmutable());
            $em->persist($user);
            $em->flush();
        }
    }

    public function incrementFailedLoginAttempts(string $userId): int
    {
        $em = $this->getEntityManager();
        $user = $this->findById($userId);

        if (method_exists($user, 'incrementFailedLoginAttempts')) {
            $count = $user->incrementFailedLoginAttempts();
            $em->persist($user);
            $em->flush();
            return $count;
        }

        return 0;
    }

    public function resetFailedLoginAttempts(string $userId): void
    {
        $em = $this->getEntityManager();
        $user = $this->findById($userId);

        if (method_exists($user, 'resetFailedLoginAttempts')) {
            $user->resetFailedLoginAttempts();
            $em->persist($user);
            $em->flush();
        }
    }

    public function lockAccount(string $userId, string $reason): void
    {
        $em = $this->getEntityManager();
        $user = $this->findById($userId);

        if (method_exists($user, 'lock')) {
            $user->lock($reason);
            $em->persist($user);
            $em->flush();
        }
    }

    public function unlockAccount(string $userId): void
    {
        $em = $this->getEntityManager();
        $user = $this->findById($userId);

        if (method_exists($user, 'unlock')) {
            $user->unlock();
            $em->persist($user);
            $em->flush();
        }
    }
}
