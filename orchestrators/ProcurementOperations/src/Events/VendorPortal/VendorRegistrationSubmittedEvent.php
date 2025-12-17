<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\VendorPortal;

use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * Event: Vendor registration submitted.
 *
 * Triggered when a vendor submits their initial registration request.
 */
final readonly class VendorRegistrationSubmittedEvent
{
    /**
     * @param string $eventId Unique event identifier
     * @param string $tenantId Tenant receiving the registration
     * @param string $registrationId Temporary registration ID
     * @param string $vendorName Submitted vendor name
     * @param string $registrationNumber Business registration number
     * @param string $taxId Tax identification number
     * @param string $countryCode ISO country code
     * @param string $primaryContactEmail Contact email
     * @param VendorPortalTier $requestedTier Requested portal tier
     * @param \DateTimeImmutable $occurredAt When the event occurred
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $registrationId,
        public string $vendorName,
        public string $registrationNumber,
        public string $taxId,
        public string $countryCode,
        public string $primaryContactEmail,
        public VendorPortalTier $requestedTier,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    public static function create(
        string $tenantId,
        string $registrationId,
        string $vendorName,
        string $registrationNumber,
        string $taxId,
        string $countryCode,
        string $primaryContactEmail,
        VendorPortalTier $requestedTier,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            registrationId: $registrationId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            taxId: $taxId,
            countryCode: $countryCode,
            primaryContactEmail: $primaryContactEmail,
            requestedTier: $requestedTier,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    private static function generateEventId(): string
    {
        return sprintf('evt_vnd_reg_%s_%s', date('YmdHis'), bin2hex(random_bytes(8)));
    }

    public function getEventName(): string
    {
        return 'vendor_portal.registration_submitted';
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
            'registration_id' => $this->registrationId,
            'vendor_name' => $this->vendorName,
            'registration_number' => $this->registrationNumber,
            'tax_id' => $this->taxId,
            'country_code' => $this->countryCode,
            'primary_contact_email' => $this->primaryContactEmail,
            'requested_tier' => $this->requestedTier->value,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
