<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\VendorPortal;

use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * Event: Vendor onboarding approved.
 *
 * Triggered when a vendor registration is approved and the vendor
 * becomes active in the system.
 */
final readonly class VendorOnboardingApprovedEvent
{
    /**
     * @param string $eventId Unique event identifier
     * @param string $tenantId Tenant that approved the vendor
     * @param string $vendorId Newly created vendor ID
     * @param string $portalUserId Portal user ID for vendor admin
     * @param string $vendorName Vendor name
     * @param string $registrationNumber Business registration
     * @param string $taxId Tax ID
     * @param string $countryCode Country code
     * @param VendorPortalTier $assignedTier Assigned portal tier
     * @param string $approvedBy User ID who approved
     * @param \DateTimeImmutable $effectiveDate When vendor becomes active
     * @param \DateTimeImmutable $occurredAt When the event occurred
     * @param array<string, mixed> $complianceResults Compliance check results
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $vendorId,
        public string $portalUserId,
        public string $vendorName,
        public string $registrationNumber,
        public string $taxId,
        public string $countryCode,
        public VendorPortalTier $assignedTier,
        public string $approvedBy,
        public \DateTimeImmutable $effectiveDate,
        public \DateTimeImmutable $occurredAt,
        public array $complianceResults = [],
        public array $metadata = [],
    ) {}

    public static function create(
        string $tenantId,
        string $vendorId,
        string $portalUserId,
        string $vendorName,
        string $registrationNumber,
        string $taxId,
        string $countryCode,
        VendorPortalTier $assignedTier,
        string $approvedBy,
        \DateTimeImmutable $effectiveDate,
        array $complianceResults = [],
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            portalUserId: $portalUserId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            taxId: $taxId,
            countryCode: $countryCode,
            assignedTier: $assignedTier,
            approvedBy: $approvedBy,
            effectiveDate: $effectiveDate,
            occurredAt: new \DateTimeImmutable(),
            complianceResults: $complianceResults,
        );
    }

    private static function generateEventId(): string
    {
        return sprintf('evt_vnd_appr_%s_%s', date('YmdHis'), bin2hex(random_bytes(8)));
    }

    public function getEventName(): string
    {
        return 'vendor_portal.onboarding_approved';
    }

    public function isAutoApproved(): bool
    {
        return isset($this->metadata['auto_approved']) && $this->metadata['auto_approved'] === true;
    }

    public function isForeignVendor(string $domesticCountry = 'MY'): bool
    {
        return $this->countryCode !== $domesticCountry;
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
            'portal_user_id' => $this->portalUserId,
            'vendor_name' => $this->vendorName,
            'registration_number' => $this->registrationNumber,
            'tax_id' => $this->taxId,
            'country_code' => $this->countryCode,
            'assigned_tier' => $this->assignedTier->value,
            'approved_by' => $this->approvedBy,
            'effective_date' => $this->effectiveDate->format('Y-m-d'),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'compliance_results' => $this->complianceResults,
            'metadata' => $this->metadata,
        ];
    }
}
