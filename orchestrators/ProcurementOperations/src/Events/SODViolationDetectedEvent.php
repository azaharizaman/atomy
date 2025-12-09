<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\ProcurementOperations\Enums\SODConflictType;

/**
 * Event dispatched when an SOD violation is detected.
 */
final readonly class SODViolationDetectedEvent
{
    /**
     * @param string $violationId Unique ID for this violation
     * @param string $userId User who triggered the violation
     * @param string $conflictingUserId User who performed the conflicting action
     * @param SODConflictType $conflictType Type of SOD conflict
     * @param string $entityType Type of entity (requisition, po, invoice)
     * @param string $entityId ID of the entity
     * @param string $action Action that was attempted
     * @param string $riskLevel HIGH, MEDIUM, or LOW
     * @param bool $blocked Whether the action was blocked
     * @param \DateTimeImmutable $detectedAt When violation was detected
     * @param array<string, mixed> $metadata Additional context
     */
    public function __construct(
        public string $violationId,
        public string $userId,
        public string $conflictingUserId,
        public SODConflictType $conflictType,
        public string $entityType,
        public string $entityId,
        public string $action,
        public string $riskLevel,
        public bool $blocked,
        public \DateTimeImmutable $detectedAt,
        public array $metadata = [],
    ) {}
}
