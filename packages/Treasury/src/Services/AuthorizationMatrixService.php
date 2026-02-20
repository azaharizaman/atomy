<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Treasury\Contracts\AuthorizationMatrixInterface;
use Nexus\Treasury\Contracts\AuthorizationMatrixQueryInterface;
use Nexus\Treasury\Contracts\AuthorizationMatrixPersistInterface;
use Nexus\Treasury\Contracts\Integration\TenantContextInterface;
use Nexus\Treasury\Entities\AuthorizationLimit;
use Nexus\Treasury\Exceptions\AuthorizationLimitExceededException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class AuthorizationMatrixService
{
    public function __construct(
        private AuthorizationMatrixQueryInterface $query,
        private AuthorizationMatrixPersistInterface $persist,
        private ?TenantContextInterface $tenantContext = null,
        private ?UserQueryInterface $userQuery = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function setLimit(
        string $tenantId,
        string $transactionType,
        Money $approvalLimit,
        ?string $userId = null,
        ?string $roleId = null,
        ?Money $dailyLimit = null,
        ?Money $weeklyLimit = null,
        ?Money $monthlyLimit = null,
        bool $requiresDualApproval = false
    ): AuthorizationMatrixInterface {
        $existing = $this->findExistingLimit($tenantId, $userId, $roleId, $transactionType);

        if ($existing !== null) {
            return $this->updateExistingLimit(
                $existing,
                $approvalLimit,
                $dailyLimit,
                $weeklyLimit,
                $monthlyLimit,
                $requiresDualApproval
            );
        }

        $now = new DateTimeImmutable();
        $limit = new AuthorizationLimit(
            id: $this->generateId(),
            tenantId: $tenantId,
            userId: $userId,
            roleId: $roleId,
            transactionType: $transactionType,
            approvalLimit: $approvalLimit,
            dailyLimit: $dailyLimit,
            weeklyLimit: $weeklyLimit,
            monthlyLimit: $monthlyLimit,
            requiresDualApproval: $requiresDualApproval,
            effectiveFrom: $now,
            effectiveTo: null,
            createdAt: $now,
            updatedAt: $now
        );

        $this->persist->save($limit);

        $this->logger->info('Authorization limit set', [
            'limit_id' => $limit->getId(),
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'transaction_type' => $transactionType,
            'approval_limit' => $approvalLimit->format(),
        ]);

        return $limit;
    }

    public function canAuthorize(
        string $tenantId,
        string $userId,
        string $transactionType,
        Money $amount
    ): bool {
        $userLimit = $this->query->findEffectiveForUser(
            $tenantId,
            $userId,
            $transactionType,
            new DateTimeImmutable()
        );

        if ($userLimit !== null && $userLimit->canAuthorize($amount)) {
            return true;
        }

        if ($this->tenantContext !== null) {
            $userRoles = $this->getUserRoles($userId);
            foreach ($userRoles as $roleId) {
                $roleLimit = $this->query->findEffectiveForRole(
                    $tenantId,
                    $roleId,
                    $transactionType,
                    new DateTimeImmutable()
                );
                if ($roleLimit !== null && $roleLimit->canAuthorize($amount)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function validateAuthorization(
        string $tenantId,
        string $userId,
        string $transactionType,
        Money $amount
    ): void {
        if (!$this->canAuthorize($tenantId, $userId, $transactionType, $amount)) {
            $highestLimit = $this->query->findHighestLimitForUser(
                $tenantId,
                $userId,
                $transactionType
            );

            if ($highestLimit !== null) {
                throw AuthorizationLimitExceededException::forAmount(
                    $amount,
                    $highestLimit->getApprovalLimit(),
                    $userId
                );
            }

            throw AuthorizationLimitExceededException::forTransaction(
                $transactionType,
                $amount,
                Money::of(0, $amount->getCurrency())
            );
        }
    }

    public function getLimit(string $limitId): AuthorizationMatrixInterface
    {
        return $this->query->findOrFail($limitId);
    }

    public function getLimitsForUser(string $tenantId, string $userId): array
    {
        return $this->query->findByUserId($tenantId, $userId);
    }

    public function getLimitsForRole(string $tenantId, string $roleId): array
    {
        return $this->query->findByRoleId($tenantId, $roleId);
    }

    public function getHighestLimitForUser(
        string $tenantId,
        string $userId,
        string $transactionType
    ): ?AuthorizationMatrixInterface {
        return $this->query->findHighestLimitForUser($tenantId, $userId, $transactionType);
    }

    public function requiresDualApproval(
        string $tenantId,
        string $userId,
        string $transactionType
    ): bool {
        $limit = $this->query->findEffectiveForUser(
            $tenantId,
            $userId,
            $transactionType,
            new DateTimeImmutable()
        );

        return $limit?->getRequiresDualApproval() ?? false;
    }

    public function deleteLimit(string $limitId): void
    {
        $limit = $this->query->find($limitId);
        if ($limit !== null) {
            $this->persist->delete($limitId);
            $this->logger->info('Authorization limit deleted', ['limit_id' => $limitId]);
        }
    }

    public function deleteLimitsForUser(string $tenantId, string $userId): int
    {
        return $this->persist->deleteByUserId($tenantId, $userId);
    }

    public function deleteLimitsForRole(string $tenantId, string $roleId): int
    {
        return $this->persist->deleteByRoleId($tenantId, $roleId);
    }

    private function findExistingLimit(
        string $tenantId,
        ?string $userId,
        ?string $roleId,
        string $transactionType
    ): ?AuthorizationMatrixInterface {
        if ($userId !== null) {
            $limits = $this->query->findByUserId($tenantId, $userId);
            foreach ($limits as $limit) {
                if ($limit->getTransactionType() === $transactionType && $limit->isActive()) {
                    return $limit;
                }
            }
        }

        if ($roleId !== null) {
            $limits = $this->query->findByRoleId($tenantId, $roleId);
            foreach ($limits as $limit) {
                if ($limit->getTransactionType() === $transactionType && $limit->isActive()) {
                    return $limit;
                }
            }
        }

        return null;
    }

    private function updateExistingLimit(
        AuthorizationMatrixInterface $existing,
        Money $approvalLimit,
        ?Money $dailyLimit,
        ?Money $weeklyLimit,
        ?Money $monthlyLimit,
        bool $requiresDualApproval
    ): AuthorizationMatrixInterface {
        $updated = new AuthorizationLimit(
            id: $existing->getId(),
            tenantId: $existing->getTenantId(),
            userId: $existing->getUserId(),
            roleId: $existing->getRoleId(),
            transactionType: $existing->getTransactionType(),
            approvalLimit: $approvalLimit,
            dailyLimit: $dailyLimit,
            weeklyLimit: $weeklyLimit,
            monthlyLimit: $monthlyLimit,
            requiresDualApproval: $requiresDualApproval,
            effectiveFrom: $existing->getEffectiveFrom(),
            effectiveTo: $existing->getEffectiveTo(),
            createdAt: $existing->getCreatedAt(),
            updatedAt: new DateTimeImmutable()
        );

        $this->persist->save($updated);

        $this->logger->info('Authorization limit updated', [
            'limit_id' => $updated->getId(),
            'approval_limit' => $approvalLimit->format(),
        ]);

        return $updated;
    }

    private function getUserRoles(string $userId): array
    {
        if ($this->userQuery === null) {
            return [];
        }

        $roles = $this->userQuery->getUserRoles($userId);
        
        return array_map(
            fn($role) => $role->getId(),
            $roles
        );
    }

    private function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-AUTH-' . $uuid;
    }
}
