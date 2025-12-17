<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\SOX;

/**
 * Event dispatched when a SOX exemption is approved.
 */
final readonly class SOXExemptionApprovedEvent
{
    /**
     * @param array<string> $affectedControlPoints
     * @param array<string, mixed> $context
     */
    public function __construct(
        public string $tenantId,
        public string $exemptionId,
        public string $approvedByUserId,
        public string $requestedByUserId,
        public string $entityType,
        public string $entityId,
        public array $affectedControlPoints,
        public ?string $approverComments,
        public \DateTimeImmutable $approvedAt,
        public ?\DateTimeImmutable $expiresAt,
        public array $context,
    ) {}

    /**
     * Create approval event.
     *
     * @param array<string> $affectedControlPoints
     * @param array<string, mixed> $context
     */
    public static function create(
        string $tenantId,
        string $exemptionId,
        string $approvedByUserId,
        string $requestedByUserId,
        string $entityType,
        string $entityId,
        array $affectedControlPoints,
        ?string $approverComments = null,
        ?int $hoursUntilExpiry = null,
        array $context = [],
    ): self {
        $expiresAt = $hoursUntilExpiry
            ? (new \DateTimeImmutable())->modify("+{$hoursUntilExpiry} hours")
            : null;

        return new self(
            tenantId: $tenantId,
            exemptionId: $exemptionId,
            approvedByUserId: $approvedByUserId,
            requestedByUserId: $requestedByUserId,
            entityType: $entityType,
            entityId: $entityId,
            affectedControlPoints: $affectedControlPoints,
            approverComments: $approverComments,
            approvedAt: new \DateTimeImmutable(),
            expiresAt: $expiresAt,
            context: $context,
        );
    }

    /**
     * Get event name for dispatch.
     */
    public static function eventName(): string
    {
        return 'procurement.sox.exemption_approved';
    }

    /**
     * Check if exemption has expiry.
     */
    public function hasExpiry(): bool
    {
        return $this->expiresAt !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'exemption_id' => $this->exemptionId,
            'approved_by_user_id' => $this->approvedByUserId,
            'requested_by_user_id' => $this->requestedByUserId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'affected_control_points' => $this->affectedControlPoints,
            'approver_comments' => $this->approverComments,
            'approved_at' => $this->approvedAt->format(\DateTimeInterface::RFC3339),
            'expires_at' => $this->expiresAt?->format(\DateTimeInterface::RFC3339),
            'context' => $this->context,
        ];
    }
}
