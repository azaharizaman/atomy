<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\VendorPortal;

/**
 * Event: Vendor reactivated.
 *
 * Triggered when a suspended vendor is reactivated.
 */
final readonly class VendorReactivatedEvent
{
    /**
     * @param string $eventId Unique event identifier
     * @param string $tenantId Tenant ID
     * @param string $vendorId Reactivated vendor ID
     * @param string $vendorName Vendor name
     * @param string $previousSuspensionCategory Category of previous suspension
     * @param string $reactivatedBy User ID who reactivated
     * @param string $reactivationReason Reason for reactivation
     * @param array<string> $restoredCapabilities What vendor can now do again
     * @param array<string, mixed> $conditions Any conditions on reactivation
     * @param \DateTimeImmutable $suspendedAt When vendor was suspended
     * @param \DateTimeImmutable $occurredAt When reactivation occurred
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $vendorId,
        public string $vendorName,
        public string $previousSuspensionCategory,
        public string $reactivatedBy,
        public string $reactivationReason,
        public array $restoredCapabilities = [],
        public array $conditions = [],
        public \DateTimeImmutable $suspendedAt = new \DateTimeImmutable(),
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
        public array $metadata = [],
    ) {}

    /**
     * Create reactivation after suspension period expired.
     */
    public static function suspensionExpired(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $previousSuspensionCategory,
        \DateTimeImmutable $suspendedAt,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            previousSuspensionCategory: $previousSuspensionCategory,
            reactivatedBy: 'SYSTEM',
            reactivationReason: 'Suspension period expired',
            restoredCapabilities: ['submit_quotes', 'receive_orders', 'submit_invoices'],
            conditions: [],
            suspendedAt: $suspendedAt,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create reactivation after compliance remediation.
     */
    public static function complianceRemediated(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $reactivatedBy,
        string $remediationDetails,
        \DateTimeImmutable $suspendedAt,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            previousSuspensionCategory: 'compliance_violation',
            reactivatedBy: $reactivatedBy,
            reactivationReason: "Compliance remediation completed: {$remediationDetails}",
            restoredCapabilities: ['submit_quotes', 'receive_orders', 'submit_invoices'],
            conditions: ['enhanced_monitoring' => true, 'monitoring_period_days' => 90],
            suspendedAt: $suspendedAt,
            occurredAt: new \DateTimeImmutable(),
            metadata: ['remediation_details' => $remediationDetails],
        );
    }

    /**
     * Create reactivation after quality improvement.
     */
    public static function qualityImproved(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $reactivatedBy,
        float $newQualityScore,
        \DateTimeImmutable $suspendedAt,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            previousSuspensionCategory: 'quality_issues',
            reactivatedBy: $reactivatedBy,
            reactivationReason: "Quality improvement demonstrated. New score: {$newQualityScore}",
            restoredCapabilities: ['receive_orders', 'submit_quotes'],
            conditions: [
                'quality_monitoring' => true,
                'review_period_days' => 60,
                'minimum_quality_score' => 75.0,
            ],
            suspendedAt: $suspendedAt,
            occurredAt: new \DateTimeImmutable(),
            metadata: ['new_quality_score' => $newQualityScore],
        );
    }

    /**
     * Create manual administrative reactivation.
     */
    public static function administrative(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $previousSuspensionCategory,
        string $reactivatedBy,
        string $reason,
        \DateTimeImmutable $suspendedAt,
        array $conditions = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            previousSuspensionCategory: $previousSuspensionCategory,
            reactivatedBy: $reactivatedBy,
            reactivationReason: $reason,
            restoredCapabilities: ['submit_quotes', 'receive_orders', 'submit_invoices'],
            conditions: $conditions,
            suspendedAt: $suspendedAt,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    private static function generateEventId(): string
    {
        return sprintf('evt_vnd_react_%s_%s', date('YmdHis'), bin2hex(random_bytes(8)));
    }

    public function getEventName(): string
    {
        return 'vendor_portal.vendor_reactivated';
    }

    public function hasConditions(): bool
    {
        return ! empty($this->conditions);
    }

    public function isSystemReactivation(): bool
    {
        return $this->reactivatedBy === 'SYSTEM';
    }

    public function getSuspensionDurationDays(): int
    {
        $interval = $this->suspendedAt->diff($this->occurredAt);
        return is_int($interval->days) ? $interval->days : 0;
    }

    public function requiresEnhancedMonitoring(): bool
    {
        return isset($this->conditions['enhanced_monitoring']) &&
               $this->conditions['enhanced_monitoring'] === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'tenant_id' => $this->tenantId,
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'previous_suspension_category' => $this->previousSuspensionCategory,
            'reactivated_by' => $this->reactivatedBy,
            'reactivation_reason' => $this->reactivationReason,
            'restored_capabilities' => $this->restoredCapabilities,
            'conditions' => $this->conditions,
            'has_conditions' => $this->hasConditions(),
            'suspended_at' => $this->suspendedAt->format('Y-m-d H:i:s'),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'suspension_duration_days' => $this->getSuspensionDurationDays(),
            'metadata' => $this->metadata,
        ];
    }
}
