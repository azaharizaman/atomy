<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Identity\Contracts\UserInterface as NexusUserInterface;
use Nexus\Identity\Contracts\UserPersistInterface;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Contracts\UserRepositoryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;
use Nexus\Identity\ValueObjects\UserStatus;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

/**
 * User Repository.
 * 
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface, UserQueryInterface, UserPersistInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    private function getOrFail(string $id): User
    {
        $user = $this->find($id);
        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('User with ID "%s" not found', $id));
        }
        return $user;
    }

    // UserQueryInterface
    public function findById(string $id): NexusUserInterface { return $this->getOrFail($id); }
    
    public function findByEmail(string $email): NexusUserInterface 
    {
        $user = $this->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('User with email "%s" not found', $email));
        }
        return $user;
    }

    public function findByEmailOrNull(string $email): ?NexusUserInterface { return $this->findOneBy(['email' => $email]); }

    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.email = :email')
            ->setParameter('email', $email);
        
        if ($excludeUserId) {
            $qb->andWhere('u.id != :id')
               ->setParameter('id', $excludeUserId);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getUserRoles(string $userId): array { return []; } // Simplified for now
    public function getUserPermissions(string $userId): array { return []; } // Simplified for now
    public function findByStatus(string $status): array { return $this->findBy(['status' => UserStatus::from($status)]); }
    public function findByRole(string $roleId): array { return []; } // Simplified for now
    public function search(array $criteria): array { return $this->findBy($criteria); }

    // UserPersistInterface
    public function create(array $data): NexusUserInterface
    {
        $user = new User($data['email']);
        if (isset($data['name'])) $user->setName($data['name']);
        if (isset($data['roles'])) $user->setRoles($data['roles']);
        if (isset($data['status'])) $user->setStatus(UserStatus::from($data['status']));
        if (isset($data['tenantId'])) $user->setTenantId($data['tenantId']);
        if (isset($data['password'])) $user->setPassword($data['password']);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    public function update(string $id, array $data): NexusUserInterface
    {
        $user = $this->getOrFail($id);
        if (isset($data['name'])) $user->setName($data['name']);
        if (isset($data['roles'])) $user->setRoles($data['roles']);
        if (isset($data['status'])) $user->setStatus(UserStatus::from($data['status']));
        if (isset($data['tenantId'])) $user->setTenantId($data['tenantId']);
        if (isset($data['password'])) $user->setPassword($data['password']);

        $this->getEntityManager()->flush();

        return $user;
    }

    public function delete(string $id): bool
    {
        $user = $this->getOrFail($id);
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
        return true;
    }

    public function assignRole(string $userId, string $roleId, ?string $tenantId = null): void {}
    public function revokeRole(string $userId, string $roleId, ?string $tenantId = null): void {}
    public function assignPermission(string $userId, string $permissionId, ?string $tenantId = null): void {}
    public function revokePermission(string $userId, string $permissionId, ?string $tenantId = null): void {}
    public function updateLastLogin(string $userId): void {}
    public function incrementFailedLoginAttempts(string $userId): int { return 0; }
    public function resetFailedLoginAttempts(string $userId): void {}
    public function lockAccount(string $userId, string $reason): void { $this->update($userId, ['status' => UserStatus::LOCKED->value]); }
    public function unlockAccount(string $userId): void { $this->update($userId, ['status' => UserStatus::ACTIVE->value]); }

    // Symfony UserLoaderInterface
    public function loadUserByIdentifier(string $identifier): ?SymfonyUserInterface
    {
        return $this->findOneBy(['email' => $identifier]);
    }
}
