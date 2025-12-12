<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\VendorPortal;

/**
 * Event: Vendor suspended.
 *
 * Triggered when a vendor's account is suspended.
 */
final readonly class VendorSuspendedEvent
{
    /**
     * @param string $eventId Unique event identifier
     * @param string $tenantId Tenant ID
     * @param string $vendorId Suspended vendor ID
     * @param string $vendorName Vendor name
     * @param string $suspensionReason Reason for suspension
     * @param string $suspensionCategory Category of suspension
     * @param string $suspendedBy User ID who suspended
     * @param bool $isAutoSuspension Whether system auto-suspended
     * @param \DateTimeImmutable|null $suspendedUntil Suspension end date (null = indefinite)
     * @param array<string> $blockedCapabilities What vendor can no longer do
     * @param \DateTimeImmutable $occurredAt When the event occurred
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $vendorId,
        public string $vendorName,
        public string $suspensionReason,
        public string $suspensionCategory,
        public string $suspendedBy,
        public bool $isAutoSuspension = false,
        public ?\DateTimeImmutable $suspendedUntil = null,
        public array $blockedCapabilities = [],
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
        public array $metadata = [],
    ) {}

    /**
     * Create suspension for compliance violation.
     */
    public static function complianceViolation(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $suspendedBy,
        string $violationDetails,
        ?\DateTimeImmutable $suspendedUntil = null,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            suspensionReason: "Compliance violation: {$violationDetails}",
            suspensionCategory: 'compliance_violation',
            suspendedBy: $suspendedBy,
            isAutoSuspension: false,
            suspendedUntil: $suspendedUntil,
            blockedCapabilities: ['submit_quotes', 'receive_orders', 'submit_invoices'],
            occurredAt: new \DateTimeImmutable(),
            metadata: ['violation_details' => $violationDetails],
        );
    }

    /**
     * Create suspension for quality issues.
     */
    public static function qualityIssues(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $suspendedBy,
        float $defectRate,
        int $consecutiveFailures,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            suspensionReason: "Quality threshold exceeded: {$defectRate}% defect rate, {$consecutiveFailures} consecutive failures",
            suspensionCategory: 'quality_issues',
            suspendedBy: $suspendedBy,
            isAutoSuspension: true,
            suspendedUntil: new \DateTimeImmutable('+30 days'),
            blockedCapabilities: ['receive_orders', 'submit_quotes'],
            occurredAt: new \DateTimeImmutable(),
            metadata: [
                'defect_rate' => $defectRate,
                'consecutive_failures' => $consecutiveFailures,
            ],
        );
    }

    /**
     * Create suspension for payment issues.
     */
    public static function paymentIssues(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $suspendedBy,
        string $paymentIssueType,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            suspensionReason: "Payment issue: {$paymentIssueType}",
            suspensionCategory: 'payment_issues',
            suspendedBy: $suspendedBy,
            isAutoSuspension: false,
            suspendedUntil: null,
            blockedCapabilities: ['receive_payments'],
            occurredAt: new \DateTimeImmutable(),
            metadata: ['payment_issue_type' => $paymentIssueType],
        );
    }

    /**
     * Create suspension due to sanctions list match.
     */
    public static function sanctionsMatch(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $sanctionList,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            suspensionReason: "Matched entry on {$sanctionList} sanctions list",
            suspensionCategory: 'sanctions_match',
            suspendedBy: 'SYSTEM',
            isAutoSuspension: true,
            suspendedUntil: null, // Indefinite
            blockedCapabilities: ['all'],
            occurredAt: new \DateTimeImmutable(),
            metadata: ['sanction_list' => $sanctionList],
        );
    }

    /**
     * Create manual administrative suspension.
     */
    public static function administrative(
        string $tenantId,
        string $vendorId,
        string $vendorName,
        string $suspendedBy,
        string $reason,
        ?\DateTimeImmutable $suspendedUntil = null,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            vendorName: $vendorName,
            suspensionReason: $reason,
            suspensionCategory: 'administrative',
            suspendedBy: $suspendedBy,
            isAutoSuspension: false,
            suspendedUntil: $suspendedUntil,
            blockedCapabilities: ['submit_quotes', 'receive_orders'],
            occurredAt: new \DateTimeImmutable(),
        );
    }

    private static function generateEventId(): string
    {
        return sprintf('evt_vnd_susp_%s_%s', date('YmdHis'), bin2hex(random_bytes(8)));
    }

    public function getEventName(): string
    {
        return 'vendor_portal.vendor_suspended';
    }

    public function isIndefinite(): bool
    {
        return $this->suspendedUntil === null;
    }

    public function isFullBlock(): bool
    {
        return in_array('all', $this->blockedCapabilities, true);
    }

    public function isSanctionsRelated(): bool
    {
        return $this->suspensionCategory === 'sanctions_match';
    }

    public function getDaysRemaining(): ?int
    {
        if ($this->suspendedUntil === null) {
            return null;
        }

        $now = new \DateTimeImmutable();
        if ($this->suspendedUntil <= $now) {
            return 0;
        }

        return (int) $now->diff($this->suspendedUntil)->days;
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
            'suspension_reason' => $this->suspensionReason,
            'suspension_category' => $this->suspensionCategory,
            'suspended_by' => $this->suspendedBy,
            'is_auto_suspension' => $this->isAutoSuspension,
            'suspended_until' => $this->suspendedUntil?->format('Y-m-d'),
            'is_indefinite' => $this->isIndefinite(),
            'blocked_capabilities' => $this->blockedCapabilities,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
