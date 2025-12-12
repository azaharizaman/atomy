<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * Vendor onboarding request DTO.
 *
 * Captures all information needed to onboard a new vendor into the
 * procurement system with compliance validation requirements.
 */
final readonly class VendorOnboardingRequest
{
    /**
     * @param string $tenantId Tenant initiating onboarding
     * @param string $vendorName Legal vendor name
     * @param string $registrationNumber Business registration number
     * @param string $taxId Tax identification number
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @param string $primaryContactEmail Primary contact email
     * @param string $primaryContactName Primary contact name
     * @param string $primaryContactPhone Primary contact phone
     * @param VendorPortalTier $requestedTier Requested portal tier
     * @param array<string, mixed> $companyProfile Company profile data
     * @param array<string, mixed> $bankingDetails Banking information
     * @param array<string, string> $certifications Industry certifications
     * @param array<string, mixed> $complianceDocuments Compliance documents
     * @param string|null $parentVendorId Parent vendor for subsidiaries
     * @param string|null $referredBy Referral source
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $vendorName,
        public string $registrationNumber,
        public string $taxId,
        public string $countryCode,
        public string $primaryContactEmail,
        public string $primaryContactName,
        public string $primaryContactPhone,
        public VendorPortalTier $requestedTier = VendorPortalTier::STANDARD,
        public array $companyProfile = [],
        public array $bankingDetails = [],
        public array $certifications = [],
        public array $complianceDocuments = [],
        public ?string $parentVendorId = null,
        public ?string $referredBy = null,
        public array $metadata = [],
    ) {}

    /**
     * Create request for domestic vendor onboarding.
     */
    public static function forDomesticVendor(
        string $tenantId,
        string $vendorName,
        string $registrationNumber,
        string $taxId,
        string $primaryContactEmail,
        string $primaryContactName,
        string $primaryContactPhone,
        string $countryCode = 'MY',
    ): self {
        return new self(
            tenantId: $tenantId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            taxId: $taxId,
            countryCode: $countryCode,
            primaryContactEmail: $primaryContactEmail,
            primaryContactName: $primaryContactName,
            primaryContactPhone: $primaryContactPhone,
            requestedTier: VendorPortalTier::STANDARD,
        );
    }

    /**
     * Create request for foreign vendor onboarding.
     */
    public static function forForeignVendor(
        string $tenantId,
        string $vendorName,
        string $registrationNumber,
        string $taxId,
        string $countryCode,
        string $primaryContactEmail,
        string $primaryContactName,
        string $primaryContactPhone,
        array $complianceDocuments,
    ): self {
        return new self(
            tenantId: $tenantId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            taxId: $taxId,
            countryCode: $countryCode,
            primaryContactEmail: $primaryContactEmail,
            primaryContactName: $primaryContactName,
            primaryContactPhone: $primaryContactPhone,
            requestedTier: VendorPortalTier::STANDARD,
            complianceDocuments: $complianceDocuments,
        );
    }

    /**
     * Create request for enterprise vendor onboarding.
     */
    public static function forEnterpriseVendor(
        string $tenantId,
        string $vendorName,
        string $registrationNumber,
        string $taxId,
        string $countryCode,
        string $primaryContactEmail,
        string $primaryContactName,
        string $primaryContactPhone,
        array $companyProfile,
        array $bankingDetails,
        array $certifications,
    ): self {
        return new self(
            tenantId: $tenantId,
            vendorName: $vendorName,
            registrationNumber: $registrationNumber,
            taxId: $taxId,
            countryCode: $countryCode,
            primaryContactEmail: $primaryContactEmail,
            primaryContactName: $primaryContactName,
            primaryContactPhone: $primaryContactPhone,
            requestedTier: VendorPortalTier::ENTERPRISE,
            companyProfile: $companyProfile,
            bankingDetails: $bankingDetails,
            certifications: $certifications,
        );
    }

    public function isForeignVendor(string $domesticCountry = 'MY'): bool
    {
        return $this->countryCode !== $domesticCountry;
    }

    public function hasComplianceDocuments(): bool
    {
        return ! empty($this->complianceDocuments);
    }

    public function hasCertifications(): bool
    {
        return ! empty($this->certifications);
    }

    public function hasBankingDetails(): bool
    {
        return ! empty($this->bankingDetails);
    }

    public function isSubsidiary(): bool
    {
        return $this->parentVendorId !== null;
    }

    public function withBankingDetails(array $bankingDetails): self
    {
        return new self(
            tenantId: $this->tenantId,
            vendorName: $this->vendorName,
            registrationNumber: $this->registrationNumber,
            taxId: $this->taxId,
            countryCode: $this->countryCode,
            primaryContactEmail: $this->primaryContactEmail,
            primaryContactName: $this->primaryContactName,
            primaryContactPhone: $this->primaryContactPhone,
            requestedTier: $this->requestedTier,
            companyProfile: $this->companyProfile,
            bankingDetails: $bankingDetails,
            certifications: $this->certifications,
            complianceDocuments: $this->complianceDocuments,
            parentVendorId: $this->parentVendorId,
            referredBy: $this->referredBy,
            metadata: $this->metadata,
        );
    }

    public function withCertifications(array $certifications): self
    {
        return new self(
            tenantId: $this->tenantId,
            vendorName: $this->vendorName,
            registrationNumber: $this->registrationNumber,
            taxId: $this->taxId,
            countryCode: $this->countryCode,
            primaryContactEmail: $this->primaryContactEmail,
            primaryContactName: $this->primaryContactName,
            primaryContactPhone: $this->primaryContactPhone,
            requestedTier: $this->requestedTier,
            companyProfile: $this->companyProfile,
            bankingDetails: $this->bankingDetails,
            certifications: $certifications,
            complianceDocuments: $this->complianceDocuments,
            parentVendorId: $this->parentVendorId,
            referredBy: $this->referredBy,
            metadata: $this->metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'vendor_name' => $this->vendorName,
            'registration_number' => $this->registrationNumber,
            'tax_id' => $this->taxId,
            'country_code' => $this->countryCode,
            'primary_contact_email' => $this->primaryContactEmail,
            'primary_contact_name' => $this->primaryContactName,
            'primary_contact_phone' => $this->primaryContactPhone,
            'requested_tier' => $this->requestedTier->value,
            'company_profile' => $this->companyProfile,
            'banking_details' => $this->bankingDetails,
            'certifications' => $this->certifications,
            'compliance_documents' => $this->complianceDocuments,
            'parent_vendor_id' => $this->parentVendorId,
            'referred_by' => $this->referredBy,
            'metadata' => $this->metadata,
        ];
    }
}
