<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserFlagOverride;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Symfony\Component\Uid\Ulid;

/**
 * Repository for user-level flag overrides.
 *
 * Manages user-specific feature flag settings that take precedence
 * over application-level flags.
 */
final class UserFlagOverrideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFlagOverride::class);
    }

    /**
     * Find a user override for a specific flag.
     *
     * @param string $userId
     * @param string $flagName
     * @return UserFlagOverride|null
     */
    public function findUserOverride(string $userId, string $flagName): ?UserFlagOverride
    {
        return $this->findOneBy([
            'userId' => $userId,
            'flagName' => $flagName,
        ]);
    }

    /**
     * Find all active (non-expired) overrides for a user.
     *
     * @param string $userId
     * @return array<UserFlagOverride>
     */
    public function findActiveByUser(string $userId): array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.userId = :userId')
            ->andWhere('(o.expiresAt IS NULL OR o.expiresAt > :now)')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('o.flagName', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all overrides for a user (including expired).
     *
     * @param string $userId
     * @return array<UserFlagOverride>
     */
    public function findByUser(string $userId): array
    {
        return $this->findBy(['userId' => $userId], ['flagName' => 'ASC']);
    }

    /**
     * Find overrides for a specific tenant.
     *
     * @param string $tenantId
     * @return array<UserFlagOverride>
     */
    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId], ['userId' => 'ASC', 'flagName' => 'ASC']);
    }

    /**
     * Find a specific override by ID.
     *
     * @param string $id
     * @return UserFlagOverride|null
     */
    public function findById(string $id): ?UserFlagOverride
    {
        return $this->find($id);
    }

    /**
     * Create a new user override.
     *
     * @param array<string, mixed> $data
     * @return UserFlagOverride
     */
    public function create(array $data): UserFlagOverride
    {
        $em = $this->getEntityManager();

        $id = $data['id'] ?? (string) new Ulid();
        $override = is_string($data['override'])
            ? FlagOverride::from($data['override'])
            : $data['override'];

        $expiresAt = null;
        if (isset($data['expires_at'])) {
            $expiresAt = $data['expires_at'] instanceof \DateTimeImmutable
                ? $data['expires_at']
                : new \DateTimeImmutable($data['expires_at']);
        }

        $entity = new UserFlagOverride(
            id: $id,
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            flagName: $data['flag_name'],
            override: $override,
            reason: $data['reason'] ?? null,
            expiresAt: $expiresAt,
            createdBy: $data['created_by'] ?? null
        );

        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    /**
     * Update an existing user override.
     *
     * @param string $id
     * @param array<string, mixed> $data
     * @return UserFlagOverride
     * @throws \RuntimeException If override not found
     */
    public function update(string $id, array $data): UserFlagOverride
    {
        $entity = $this->findById($id);
        if ($entity === null) {
            throw new \RuntimeException("User flag override not found: {$id}");
        }

        if (isset($data['override'])) {
            $override = is_string($data['override'])
                ? FlagOverride::from($data['override'])
                : $data['override'];
            $entity->setOverride($override);
        }

        if (array_key_exists('reason', $data)) {
            $entity->setReason($data['reason']);
        }

        if (array_key_exists('expires_at', $data)) {
            $expiresAt = null;
            if ($data['expires_at'] !== null) {
                $expiresAt = $data['expires_at'] instanceof \DateTimeImmutable
                    ? $data['expires_at']
                    : new \DateTimeImmutable($data['expires_at']);
            }
            $entity->setExpiresAt($expiresAt);
        }

        $this->getEntityManager()->flush();

        return $entity;
    }

    /**
     * Delete a user override by ID.
     *
     * @param string $id
     * @return void
     */
    public function deleteById(string $id): void
    {
        $entity = $this->findById($id);
        if ($entity !== null) {
            $em = $this->getEntityManager();
            $em->remove($entity);
            $em->flush();
        }
    }

    /**
     * Delete all overrides for a specific user.
     *
     * @param string $userId
     * @return int Number of deleted overrides
     */
    public function deleteByUser(string $userId): int
    {
        return $this->createQueryBuilder('o')
            ->delete()
            ->where('o.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete all expired overrides.
     *
     * @return int Number of deleted overrides
     */
    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('o')
            ->delete()
            ->where('o.expiresAt IS NOT NULL')
            ->andWhere('o.expiresAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Check if an override exists for a user and flag.
     *
     * @param string $userId
     * @param string $flagName
     * @return bool
     */
    public function overrideExists(string $userId, string $flagName): bool
    {
        return $this->count([
            'userId' => $userId,
            'flagName' => $flagName,
        ]) > 0;
    }

    /**
     * Get user override count by tenant.
     *
     * @param string $tenantId
     * @return int
     */
    public function countByTenant(string $tenantId): int
    {
        return $this->count(['tenantId' => $tenantId]);
    }

    /**
     * Get a map of flag overrides for a user.
     *
     * Returns a map of flag_name => FlagOverride for quick lookups.
     * Only includes active (non-expired) overrides.
     *
     * @param string $userId
     * @return array<string, FlagOverride>
     */
    public function getOverrideMapForUser(string $userId): array
    {
        $overrides = $this->findActiveByUser($userId);
        $map = [];

        foreach ($overrides as $override) {
            $map[$override->getFlagName()] = $override->getOverride();
        }

        return $map;
    }
}
