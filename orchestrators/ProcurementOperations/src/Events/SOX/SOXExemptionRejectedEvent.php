<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\SOX;

/**
 * Event dispatched when a SOX exemption is rejected.
 */
final readonly class SOXExemptionRejectedEvent
{
    /**
     * @param array<string> $affectedControlPoints
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $tenantId,
        public string $exemptionId,
        public string $rejectedByUserId,
        public string $requestedByUserId,
        public string $entityType,
        public string $entityId,
        public array $affectedControlPoints,
        public string $rejectionReason,
        public \DateTimeImmutable $rejectedAt,
        public array $context,
    ) {}

    /**
     * Create rejection event.
     *
     * @param array<string> $affectedControlPoints
     * @param array<string, mixed> $context
     */
    public static function create(
        string $tenantId,
        string $exemptionId,
        string $rejectedByUserId,
        string $requestedByUserId,
        string $entityType,
        string $entityId,
        array $affectedControlPoints,
        string $rejectionReason,
        array $context = [],
    ): self {
        return new self(
            tenantId: $tenantId,
            exemptionId: $exemptionId,
            rejectedByUserId: $rejectedByUserId,
            requestedByUserId: $requestedByUserId,
            entityType: $entityType,
            entityId: $entityId,
            affectedControlPoints: $affectedControlPoints,
            rejectionReason: $rejectionReason,
            rejectedAt: new \DateTimeImmutable(),
            context: $context,
        );
    }

    /**
     * Get event name for dispatch.
     */
    public static function eventName(): string
    {
        return 'procurement.sox.exemption_rejected';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'exemption_id' => $this->exemptionId,
            'rejected_by_user_id' => $this->rejectedByUserId,
            'requested_by_user_id' => $this->requestedByUserId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'affected_control_points' => $this->affectedControlPoints,
            'rejection_reason' => $this->rejectionReason,
            'rejected_at' => $this->rejectedAt->format(\DateTimeInterface::RFC3339),
            'context' => $this->context,
        ];
    }
}
