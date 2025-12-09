<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\Enums\SODConflictType;

/**
 * Result of SOD validation containing any detected violations.
 */
final readonly class SODValidationResult
{
    /**
     * @param bool $passed True if no SOD violations detected
     * @param array<SODViolation> $violations List of detected violations
     * @param string $userId User that was validated
     * @param string $action Action that was validated
     * @param \DateTimeImmutable $validatedAt When validation occurred
     */
    public function __construct(
        public bool $passed,
        public array $violations,
        public string $userId,
        public string $action,
        public \DateTimeImmutable $validatedAt,
    ) {}

    /**
     * Create a passing result.
     */
    public static function pass(string $userId, string $action): self
    {
        return new self(
            passed: true,
            violations: [],
            userId: $userId,
            action: $action,
            validatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a failing result with violations.
     *
     * @param array<SODViolation> $violations
     */
    public static function fail(string $userId, string $action, array $violations): self
    {
        return new self(
            passed: false,
            violations: $violations,
            userId: $userId,
            action: $action,
            validatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Check if there are any HIGH risk violations.
     */
    public function hasHighRiskViolations(): bool
    {
        foreach ($this->violations as $violation) {
            if ($violation->conflictType->getRiskLevel() === 'HIGH') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get violations by risk level.
     *
     * @return array<SODViolation>
     */
    public function getViolationsByRisk(string $riskLevel): array
    {
        return array_filter(
            $this->violations,
            fn(SODViolation $v) => $v->conflictType->getRiskLevel() === $riskLevel
        );
    }

    /**
     * Get all conflict types that were violated.
     *
     * @return array<SODConflictType>
     */
    public function getViolatedConflictTypes(): array
    {
        return array_map(
            fn(SODViolation $v) => $v->conflictType,
            $this->violations
        );
    }

    /**
     * Get summary of violations for reporting.
     *
     * @return array<string, mixed>
     */
    public function toSummary(): array
    {
        return [
            'passed' => $this->passed,
            'user_id' => $this->userId,
            'action' => $this->action,
            'violation_count' => count($this->violations),
            'high_risk_count' => count($this->getViolationsByRisk('HIGH')),
            'medium_risk_count' => count($this->getViolationsByRisk('MEDIUM')),
            'low_risk_count' => count($this->getViolationsByRisk('LOW')),
            'validated_at' => $this->validatedAt->format('c'),
        ];
    }
}

/**
 * Represents a single SOD violation.
 */
final readonly class SODViolation
{
    public function __construct(
        public SODConflictType $conflictType,
        public string $userId,
        public string $conflictingUserId,
        public string $role1,
        public string $role2,
        public string $entityType,
        public string $entityId,
        public string $message,
    ) {}

    /**
     * Create violation for same user conflict.
     */
    public static function sameUser(
        SODConflictType $conflictType,
        string $userId,
        string $entityType,
        string $entityId,
    ): self {
        $roles = $conflictType->getConflictingRoles();

        return new self(
            conflictType: $conflictType,
            userId: $userId,
            conflictingUserId: $userId,
            role1: $roles[0],
            role2: $roles[1],
            entityType: $entityType,
            entityId: $entityId,
            message: sprintf(
                'User %s has conflicting roles: %s',
                $userId,
                $conflictType->getDescription()
            ),
        );
    }

    /**
     * Create violation for action conflict (user performed both actions).
     */
    public static function actionConflict(
        SODConflictType $conflictType,
        string $userId,
        string $previousActorId,
        string $entityType,
        string $entityId,
    ): self {
        $roles = $conflictType->getConflictingRoles();

        return new self(
            conflictType: $conflictType,
            userId: $userId,
            conflictingUserId: $previousActorId,
            role1: $roles[0],
            role2: $roles[1],
            entityType: $entityType,
            entityId: $entityId,
            message: sprintf(
                '%s: User %s cannot perform this action (previous actor: %s)',
                $conflictType->getDescription(),
                $userId,
                $previousActorId
            ),
        );
    }
}
