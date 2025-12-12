<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * Vendor profile data DTO.
 *
 * Comprehensive vendor profile information for display and management.
 */
final readonly class VendorProfileData
{
    /**
     * @param string $vendorId Unique vendor identifier
     * @param string $vendorName Legal vendor name
     * @param string $tradingName Trading/DBA name
     * @param string $registrationNumber Business registration number
     * @param string $taxId Tax identification number
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @param VendorPortalTier $portalTier Current portal tier
     * @param string $status Current status
     * @param VendorAddressData $primaryAddress Primary business address
     * @param VendorContactData $primaryContact Primary contact
     * @param array<VendorAddressData> $additionalAddresses Additional addresses
     * @param array<VendorContactData> $additionalContacts Additional contacts
     * @param array<string, mixed> $industryClassification Industry classification data
     * @param array<string, mixed> $paymentTerms Default payment terms
     * @param array<string, mixed> $bankingDetails Banking information
     * @param array<VendorCertificationData> $certifications Active certifications
     * @param VendorRatingData|null $rating Performance rating
     * @param \DateTimeImmutable $onboardedAt When vendor was onboarded
     * @param \DateTimeImmutable|null $lastActivityAt Last activity timestamp
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $vendorId,
        public string $vendorName,
        public string $tradingName,
        public string $registrationNumber,
        public string $taxId,
        public string $countryCode,
        public VendorPortalTier $portalTier,
        public string $status,
        public VendorAddressData $primaryAddress,
        public VendorContactData $primaryContact,
        public array $additionalAddresses = [],
        public array $additionalContacts = [],
        public array $industryClassification = [],
        public array $paymentTerms = [],
        public array $bankingDetails = [],
        public array $certifications = [],
        public ?VendorRatingData $rating = null,
        public \DateTimeImmutable $onboardedAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $lastActivityAt = null,
        public array $metadata = [],
    ) {}

    /**
     * Create minimal vendor profile.
     */
    public static function minimal(
        string $vendorId,
        string $vendorName,
        string $registrationNumber,
        string $taxId,
        string $countryCode,
        VendorAddressData $primaryAddress,
        VendorContactData $primaryContact,
    ): self {
        return new self(
            vendorId: $vendorId,
            vendorName: $vendorName,
            tradingName: $vendorName,
            registrationNumber: $registrationNumber,
            taxId: $taxId,
            countryCode: $countryCode,
            portalTier: VendorPortalTier::STANDARD,
            status: 'active',
            primaryAddress: $primaryAddress,
            primaryContact: $primaryContact,
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended' || $this->portalTier === VendorPortalTier::SUSPENDED;
    }

    public function isPremiumTier(): bool
    {
        return in_array($this->portalTier, [VendorPortalTier::PREMIUM, VendorPortalTier::ENTERPRISE], true);
    }

    public function isForeignVendor(string $domesticCountry = 'MY'): bool
    {
        return $this->countryCode !== $domesticCountry;
    }

    public function hasValidCertification(string $certificationCode): bool
    {
        foreach ($this->certifications as $cert) {
            if ($cert->code === $certificationCode && $cert->isValid()) {
                return true;
            }
        }

        return false;
    }

    public function hasRating(): bool
    {
        return $this->rating !== null;
    }

    public function getRatingScore(): ?float
    {
        return $this->rating?->overallScore;
    }

    /**
     * @return array<VendorCertificationData>
     */
    public function getExpiringCertifications(int $daysThreshold = 30): array
    {
        $threshold = new \DateTimeImmutable("+{$daysThreshold} days");

        return array_filter(
            $this->certifications,
            fn (VendorCertificationData $cert) => $cert->isValid() && $cert->expiresAt < $threshold,
        );
    }

    public function getContactByType(string $contactType): ?VendorContactData
    {
        foreach ($this->additionalContacts as $contact) {
            if ($contact->contactType === $contactType) {
                return $contact;
            }
        }

        return $this->primaryContact->contactType === $contactType ? $this->primaryContact : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'vendor_name' => $this->vendorName,
            'trading_name' => $this->tradingName,
            'registration_number' => $this->registrationNumber,
            'tax_id' => $this->taxId,
            'country_code' => $this->countryCode,
            'portal_tier' => $this->portalTier->value,
            'status' => $this->status,
            'primary_address' => $this->primaryAddress->toArray(),
            'primary_contact' => $this->primaryContact->toArray(),
            'additional_addresses' => array_map(fn (VendorAddressData $a) => $a->toArray(), $this->additionalAddresses),
            'additional_contacts' => array_map(fn (VendorContactData $c) => $c->toArray(), $this->additionalContacts),
            'industry_classification' => $this->industryClassification,
            'payment_terms' => $this->paymentTerms,
            'banking_details' => $this->bankingDetails,
            'certifications' => array_map(fn (VendorCertificationData $c) => $c->toArray(), $this->certifications),
            'rating' => $this->rating?->toArray(),
            'onboarded_at' => $this->onboardedAt->format('Y-m-d H:i:s'),
            'last_activity_at' => $this->lastActivityAt?->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
