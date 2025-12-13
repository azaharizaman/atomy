<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event raised when a spend policy violation is detected.
 */
final readonly class SpendPolicyViolatedEvent
{
    /**
     * @param string $eventId Event identifier
     * @param string $tenantId Tenant context
     * @param string $violationType Type of violation (e.g., OVER_BUDGET, UNAUTHORIZED, POLICY_BREACH)
     * @param string $policyId Policy that was violated
     * @param string $policyName Name of violated policy
     * @param string $documentType Document type (REQUISITION, PURCHASE_ORDER, INVOICE)
     * @param string $documentId Document that violated policy
     * @param string $documentNumber Document number for reference
     * @param Money $transactionAmount Amount of the transaction
     * @param Money|null $policyLimit Policy limit if applicable
     * @param string $violatedBy User who created violating document
     * @param string $description Detailed violation description
     * @param string $severity Violation severity (LOW, MEDIUM, HIGH, CRITICAL)
     * @param bool $requiresApproval Whether override requires approval
     * @param string|null $costCenter Cost center if applicable
     * @param string|null $department Department if applicable
     * @param string|null $vendorId Vendor if applicable
     * @param \DateTimeImmutable $occurredAt When violation occurred
     * @param array $metadata Additional violation metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $violationType,
        public string $policyId,
        public string $policyName,
        public string $documentType,
        public string $documentId,
        public string $documentNumber,
        public Money $transactionAmount,
        public ?Money $policyLimit,
        public string $violatedBy,
        public string $description,
        public string $severity,
        public bool $requiresApproval,
        public ?string $costCenter = null,
        public ?string $department = null,
        public ?string $vendorId = null,
        public ?\DateTimeImmutable $occurredAt = null,
        public array $metadata = [],
    ) {
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();
    }

    /**
     * Get variance from policy limit.
     */
    public function getVarianceAmount(): ?Money
    {
        if ($this->policyLimit === null) {
            return null;
        }

        return $this->transactionAmount->subtract($this->policyLimit);
    }

    /**
     * Get variance percentage from policy limit.
     */
    public function getVariancePercentage(): ?float
    {
        if ($this->policyLimit === null || $this->policyLimit->isZero()) {
            return null;
        }

        $variance = $this->getVarianceAmount();
        if ($variance === null) {
            return null;
        }

        return round(
            ($variance->getAmount() / $this->policyLimit->getAmount()) * 100,
            2,
        );
    }

    /**
     * Check if this is a critical violation.
     */
    public function isCritical(): bool
    {
        return $this->severity === 'CRITICAL';
    }

    /**
     * Check if this is a high severity violation.
     */
    public function isHighSeverity(): bool
    {
        return in_array($this->severity, ['HIGH', 'CRITICAL'], true);
    }

    /**
     * Get event name for dispatcher.
     */
    public static function getEventName(): string
    {
        return 'procurement.spend_policy_violated';
    }

    /**
     * Convert to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => self::getEventName(),
            'tenant_id' => $this->tenantId,
            'violation_type' => $this->violationType,
            'policy_id' => $this->policyId,
            'policy_name' => $this->policyName,
            'document_type' => $this->documentType,
            'document_id' => $this->documentId,
            'document_number' => $this->documentNumber,
            'transaction_amount' => $this->transactionAmount->getAmount(),
            'transaction_currency' => $this->transactionAmount->getCurrency(),
            'policy_limit' => $this->policyLimit?->getAmount(),
            'variance_amount' => $this->getVarianceAmount()?->getAmount(),
            'variance_percentage' => $this->getVariancePercentage(),
            'violated_by' => $this->violatedBy,
            'description' => $this->description,
            'severity' => $this->severity,
            'requires_approval' => $this->requiresApproval,
            'cost_center' => $this->costCenter,
            'department' => $this->department,
            'vendor_id' => $this->vendorId,
            'occurred_at' => $this->occurredAt->format('c'),
            'metadata' => $this->metadata,
        ];
    }
}
