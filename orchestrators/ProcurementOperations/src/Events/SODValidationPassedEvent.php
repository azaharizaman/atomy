<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when SOD validation passes successfully.
 */
final readonly class SODValidationPassedEvent
{
    /**
     * @param string $userId User who passed validation
     * @param string $action Action that was validated
     * @param string $entityType Type of entity
     * @param string $entityId ID of the entity
     * @param int $checksPerformed Number of SOD checks performed
     * @param \DateTimeImmutable $validatedAt When validation occurred
     */
    public function __construct(
        public string $userId,
        public string $action,
        public string $entityType,
        public string $entityId,
        public int $checksPerformed,
        public \DateTimeImmutable $validatedAt,
    ) {}
}
