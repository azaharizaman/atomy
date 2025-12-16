<?php

declare(strict_types=1);

namespace Nexus\KycVerification\ValueObjects;

use Nexus\KycVerification\Enums\VerificationStatus;

/**
 * Represents a beneficial owner in a corporate structure.
 * 
 * @immutable
 */
final readonly class BeneficialOwner
{
    public const float CONTROL_THRESHOLD = 25.0;

    /**
     * @param array<string> $nationalities
     * @param array<DocumentVerification> $documents
     * @param array<string> $controlRights
     */
    public function __construct(
        public string $id,
        public string $fullName,
        public ?string $dateOfBirth,
        public float $ownershipPercentage,
        public bool $hasControlRights,
        public VerificationStatus $verificationStatus,
        public array $nationalities = [],
        public ?string $residenceCountry = null,
        public ?string $taxResidency = null,
        public ?string $taxId = null,
        public bool $isPep = false,
        public ?string $pepDetails = null,
        public array $documents = [],
        public array $controlRights = [],
        public ?string $sourceOfWealth = null,
        public ?\DateTimeImmutable $verifiedAt = null,
        public ?string $parentEntityId = null,
        public ?float $effectiveOwnership = null,
    ) {}

    /**
     * Check if beneficial owner is verified
     */
    public function isVerified(): bool
    {
        return $this->verificationStatus === VerificationStatus::VERIFIED;
    }

    /**
     * Check if ownership exceeds beneficial ownership threshold
     */
    public function exceedsOwnershipThreshold(float $threshold = self::CONTROL_THRESHOLD): bool
    {
        return $this->ownershipPercentage >= $threshold;
    }

    /**
     * Check if this is a significant controller (ownership or control rights)
     */
    public function isSignificantController(): bool
    {
        return $this->exceedsOwnershipThreshold() || $this->hasControlRights;
    }

    /**
     * Check if UBO is a Politically Exposed Person
     */
    public function isPoliticallyExposed(): bool
    {
        return $this->isPep;
    }

    /**
     * Check if UBO is from high-risk country
     * 
     * @param array<string> $highRiskCountries
     */
    public function isFromHighRiskCountry(array $highRiskCountries): bool
    {
        $countries = array_map('strtoupper', $highRiskCountries);

        // Check residence country
        if ($this->residenceCountry !== null && in_array(strtoupper($this->residenceCountry), $countries, true)) {
            return true;
        }

        // Check nationalities
        foreach ($this->nationalities as $nationality) {
            if (in_array(strtoupper($nationality), $countries, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get effective ownership percentage (direct + indirect)
     */
    public function getEffectiveOwnership(): float
    {
        return $this->effectiveOwnership ?? $this->ownershipPercentage;
    }

    /**
     * Check if all required documents are verified
     */
    public function hasAllDocumentsVerified(): bool
    {
        if (empty($this->documents)) {
            return false;
        }

        foreach ($this->documents as $doc) {
            if (!$doc->isVerified() || $doc->isExpired()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get count of verified documents
     * 
     * @return array{verified: int, total: int}
     */
    public function getDocumentVerificationStats(): array
    {
        $verified = 0;
        $total = count($this->documents);

        foreach ($this->documents as $doc) {
            if ($doc->isVerified() && !$doc->isExpired()) {
                $verified++;
            }
        }

        return ['verified' => $verified, 'total' => $total];
    }

    /**
     * Check if source of wealth is declared
     */
    public function hasSourceOfWealthDeclared(): bool
    {
        return $this->sourceOfWealth !== null && trim($this->sourceOfWealth) !== '';
    }

    /**
     * Create with verified status
     * 
     * @param array<string> $nationalities
     */
    public static function verified(
        string $id,
        string $fullName,
        float $ownershipPercentage,
        array $nationalities = [],
        ?string $residenceCountry = null,
        bool $isPep = false,
    ): self {
        return new self(
            id: $id,
            fullName: $fullName,
            dateOfBirth: null,
            ownershipPercentage: $ownershipPercentage,
            hasControlRights: $ownershipPercentage >= self::CONTROL_THRESHOLD,
            verificationStatus: VerificationStatus::VERIFIED,
            nationalities: $nationalities,
            residenceCountry: $residenceCountry,
            isPep: $isPep,
            verifiedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'date_of_birth' => $this->dateOfBirth,
            'ownership_percentage' => $this->ownershipPercentage,
            'effective_ownership' => $this->getEffectiveOwnership(),
            'has_control_rights' => $this->hasControlRights,
            'control_rights' => $this->controlRights,
            'verification_status' => $this->verificationStatus->value,
            'nationalities' => $this->nationalities,
            'residence_country' => $this->residenceCountry,
            'tax_residency' => $this->taxResidency,
            'tax_id' => $this->taxId,
            'is_pep' => $this->isPep,
            'pep_details' => $this->pepDetails,
            'source_of_wealth' => $this->sourceOfWealth,
            'is_verified' => $this->isVerified(),
            'is_significant_controller' => $this->isSignificantController(),
            'verified_at' => $this->verifiedAt?->format('c'),
            'parent_entity_id' => $this->parentEntityId,
            'documents_count' => count($this->documents),
        ];
    }
}
