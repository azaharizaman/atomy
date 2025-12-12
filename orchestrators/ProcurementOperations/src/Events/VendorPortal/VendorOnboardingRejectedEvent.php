<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\VendorPortal;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorValidationError;

/**
 * Event: Vendor onboarding rejected.
 *
 * Triggered when a vendor registration is rejected.
 */
final readonly class VendorOnboardingRejectedEvent
{
    /**
     * @param string $eventId Unique event identifier
     * @param string $tenantId Tenant that rejected the vendor
     * @param string $registrationId Original registration ID
     * @param string $vendorName Vendor name that was rejected
     * @param string $registrationNumber Business registration number
     * @param string $rejectionReason Primary rejection reason
     * @param string $rejectionCategory Rejection category
     * @param string $rejectedBy User ID who rejected
     * @param array<VendorValidationError> $validationErrors Specific validation errors
     * @param array<string, mixed> $complianceResults Compliance check results
     * @param bool $canReapply Whether vendor can reapply
     * @param \DateTimeImmutable|null $reapplyAfter Earliest reapply date
     * @param \DateTimeImmutable $occurredAt When the event occurred
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $registrationId,
        public string $vendorName,
        public string $registrationNumber,
        public string $rejectionReason,
        public string $rejectionCategory,
        public string $rejectedBy,
        public array $validationErrors = [],
        public array $complianceResults = [],
        public bool $canReapply = true,
        public ?\DateTimeImmutable $reapplyAfter = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
        public array $metadata = [],
    ) {}

    public static function complianceFailure(
        string $tenantId,
        string $registrationId,
        string $vendorName,
        string $registrationNumber,
        string $rejectedBy,
        array $complianceResults,
        array $validationErrors = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            registrationId: $registrationId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            rejectionReason: 'Vendor failed compliance checks',
            rejectionCategory: 'compliance_failure',
            rejectedBy: $rejectedBy,
            validationErrors: $validationErrors,
            complianceResults: $complianceResults,
            canReapply: true,
            reapplyAfter: new \DateTimeImmutable('+30 days'),
            occurredAt: new \DateTimeImmutable(),
        );
    }

    public static function sanctionMatch(
        string $tenantId,
        string $registrationId,
        string $vendorName,
        string $registrationNumber,
        string $rejectedBy,
        string $sanctionList,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            registrationId: $registrationId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            rejectionReason: "Vendor matches entry on {$sanctionList} sanctions list",
            rejectionCategory: 'sanction_match',
            rejectedBy: $rejectedBy,
            validationErrors: [],
            complianceResults: ['sanction_list' => $sanctionList],
            canReapply: false,
            reapplyAfter: null,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    public static function duplicateVendor(
        string $tenantId,
        string $registrationId,
        string $vendorName,
        string $registrationNumber,
        string $rejectedBy,
        string $existingVendorId,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            registrationId: $registrationId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            rejectionReason: 'Vendor already exists in the system',
            rejectionCategory: 'duplicate',
            rejectedBy: $rejectedBy,
            validationErrors: [],
            complianceResults: ['existing_vendor_id' => $existingVendorId],
            canReapply: false,
            reapplyAfter: null,
            occurredAt: new \DateTimeImmutable(),
            metadata: ['existing_vendor_id' => $existingVendorId],
        );
    }

    public static function manualRejection(
        string $tenantId,
        string $registrationId,
        string $vendorName,
        string $registrationNumber,
        string $rejectedBy,
        string $reason,
        bool $canReapply = true,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            registrationId: $registrationId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            rejectionReason: $reason,
            rejectionCategory: 'manual_rejection',
            rejectedBy: $rejectedBy,
            validationErrors: [],
            complianceResults: [],
            canReapply: $canReapply,
            reapplyAfter: $canReapply ? new \DateTimeImmutable('+90 days') : null,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    private static function generateEventId(): string
    {
        return sprintf('evt_vnd_rej_%s_%s', date('YmdHis'), bin2hex(random_bytes(8)));
    }

    public function getEventName(): string
    {
        return 'vendor_portal.onboarding_rejected';
    }

    public function isPermanentRejection(): bool
    {
        return ! $this->canReapply;
    }

    public function isSanctionRelated(): bool
    {
        return $this->rejectionCategory === 'sanction_match';
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
            'registration_id' => $this->registrationId,
            'vendor_name' => $this->vendorName,
            'registration_number' => $this->registrationNumber,
            'rejection_reason' => $this->rejectionReason,
            'rejection_category' => $this->rejectionCategory,
            'rejected_by' => $this->rejectedBy,
            'validation_errors' => array_map(
                fn (VendorValidationError $e) => $e->toArray(),
                $this->validationErrors,
            ),
            'compliance_results' => $this->complianceResults,
            'can_reapply' => $this->canReapply,
            'reapply_after' => $this->reapplyAfter?->format('Y-m-d'),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
