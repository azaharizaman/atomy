<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event raised when a spend policy override is requested.
 */
final readonly class SpendPolicyOverrideRequestedEvent
{
    /**
     * @param string $eventId Event identifier
     * @param string $tenantId Tenant context
     * @param string $overrideRequestId Override request identifier
     * @param string $violationEventId Original violation event ID
     * @param string $policyId Policy for which override is requested
     * @param string $policyName Name of the policy
     * @param string $documentType Document type
     * @param string $documentId Document requiring override
     * @param string $documentNumber Document number
     * @param Money $requestedAmount Amount requiring override
     * @param Money|null $policyLimit Original policy limit
     * @param string $requestedBy User requesting override
     * @param string $justification Business justification
     * @param string $urgencyLevel Override urgency (NORMAL, URGENT, CRITICAL)
     * @param string|null $approverUserId Designated approver
     * @param string|null $alternateApproverUserId Alternate approver
     * @param \DateTimeImmutable|null $expiresAt When request expires
     * @param array $supportingDocuments Supporting document references
     * @param \DateTimeImmutable $requestedAt When override was requested
     * @param array $metadata Additional request metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $overrideRequestId,
        public string $violationEventId,
        public string $policyId,
        public string $policyName,
        public string $documentType,
        public string $documentId,
        public string $documentNumber,
        public Money $requestedAmount,
        public ?Money $policyLimit,
        public string $requestedBy,
        public string $justification,
        public string $urgencyLevel,
        public ?string $approverUserId = null,
        public ?string $alternateApproverUserId = null,
        public ?\DateTimeImmutable $expiresAt = null,
        public array $supportingDocuments = [],
        public ?\DateTimeImmutable $requestedAt = null,
        public array $metadata = [],
    ) {
        $this->requestedAt = $requestedAt ?? new \DateTimeImmutable();
    }

    /**
     * Check if override request is urgent.
     */
    public function isUrgent(): bool
    {
        return in_array($this->urgencyLevel, ['URGENT', 'CRITICAL'], true);
    }

    /**
     * Check if override request is critical.
     */
    public function isCritical(): bool
    {
        return $this->urgencyLevel === 'CRITICAL';
    }

    /**
     * Check if request has expired.
     */
    public function isExpired(?\DateTimeImmutable $asOfDate = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $asOfDate ??= new \DateTimeImmutable();
        return $asOfDate > $this->expiresAt;
    }

    /**
     * Get hours until expiration.
     */
    public function getHoursUntilExpiration(?\DateTimeImmutable $asOfDate = null): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $asOfDate ??= new \DateTimeImmutable();
        $diff = $asOfDate->diff($this->expiresAt);

        if ($diff->invert) {
            return 0; // Already expired
        }

        return ($diff->days * 24) + $diff->h;
    }

    /**
     * Get override amount (difference from limit).
     */
    public function getOverrideAmount(): ?Money
    {
        if ($this->policyLimit === null) {
            return null;
        }

        return $this->requestedAmount->subtract($this->policyLimit);
    }

    /**
     * Get override percentage above limit.
     */
    public function getOverridePercentage(): ?float
    {
        if ($this->policyLimit === null || $this->policyLimit->isZero()) {
            return null;
        }

        $override = $this->getOverrideAmount();
        if ($override === null) {
            return null;
        }

        return round(
            ($override->getAmount() / $this->policyLimit->getAmount()) * 100,
            2,
        );
    }

    /**
     * Check if has supporting documents.
     */
    public function hasSupportingDocuments(): bool
    {
        return !empty($this->supportingDocuments);
    }

    /**
     * Get event name for dispatcher.
     */
    public static function getEventName(): string
    {
        return 'procurement.spend_policy_override_requested';
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
            'override_request_id' => $this->overrideRequestId,
            'violation_event_id' => $this->violationEventId,
            'policy_id' => $this->policyId,
            'policy_name' => $this->policyName,
            'document_type' => $this->documentType,
            'document_id' => $this->documentId,
            'document_number' => $this->documentNumber,
            'requested_amount' => $this->requestedAmount->getAmount(),
            'requested_currency' => $this->requestedAmount->getCurrency(),
            'policy_limit' => $this->policyLimit?->getAmount(),
            'override_amount' => $this->getOverrideAmount()?->getAmount(),
            'override_percentage' => $this->getOverridePercentage(),
            'requested_by' => $this->requestedBy,
            'justification' => $this->justification,
            'urgency_level' => $this->urgencyLevel,
            'approver_user_id' => $this->approverUserId,
            'alternate_approver_user_id' => $this->alternateApproverUserId,
            'expires_at' => $this->expiresAt?->format('c'),
            'supporting_documents' => $this->supportingDocuments,
            'requested_at' => $this->requestedAt->format('c'),
            'metadata' => $this->metadata,
        ];
    }
}
