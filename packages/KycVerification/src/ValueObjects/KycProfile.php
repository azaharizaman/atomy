<?php

declare(strict_types=1);

namespace Nexus\KycVerification\ValueObjects;

use Nexus\KycVerification\Enums\DueDiligenceLevel;
use Nexus\KycVerification\Enums\PartyType;
use Nexus\KycVerification\Enums\RiskLevel;
use Nexus\KycVerification\Enums\VerificationStatus;

/**
 * Represents the complete KYC profile of a party.
 * 
 * @immutable
 */
final readonly class KycProfile
{
    /**
     * @param array<DocumentVerification> $documents
     * @param array<BeneficialOwner> $beneficialOwners
     * @param array<string, mixed> $additionalData
     */
    public function __construct(
        public string $partyId,
        public PartyType $partyType,
        public VerificationStatus $status,
        public DueDiligenceLevel $dueDiligenceLevel,
        public RiskAssessment $riskAssessment,
        public ?AddressVerification $addressVerification = null,
        public array $documents = [],
        public array $beneficialOwners = [],
        public ?\DateTimeImmutable $verifiedAt = null,
        public ?\DateTimeImmutable $expiresAt = null,
        public ?\DateTimeImmutable $nextReviewDate = null,
        public ?string $verifiedBy = null,
        public int $verificationScore = 0,
        public array $additionalData = [],
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * Check if KYC is verified
     */
    public function isVerified(): bool
    {
        return $this->status === VerificationStatus::VERIFIED;
    }

    /**
     * Check if KYC is active (allows transactions)
     */
    public function isActive(): bool
    {
        return $this->status->allowsTransactions();
    }

    /**
     * Check if KYC is expired
     */
    public function isExpired(): bool
    {
        return $this->status === VerificationStatus::EXPIRED
            || ($this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable());
    }

    /**
     * Check if review is due
     */
    public function isReviewDue(): bool
    {
        if ($this->nextReviewDate === null) {
            return false;
        }

        return $this->nextReviewDate <= new \DateTimeImmutable();
    }

    /**
     * Get expiry date warning (days until expiry, or negative if expired)
     */
    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->expiresAt);

        return $diff->invert === 1 ? -$diff->days : $diff->days;
    }

    /**
     * Check if Enhanced Due Diligence is required
     */
    public function requiresEdd(): bool
    {
        return $this->dueDiligenceLevel === DueDiligenceLevel::ENHANCED;
    }

    /**
     * Get risk level
     */
    public function getRiskLevel(): RiskLevel
    {
        return $this->riskAssessment->riskLevel;
    }

    /**
     * Check if this is a corporate party requiring UBO tracking
     */
    public function requiresUboTracking(): bool
    {
        return $this->partyType->requiresUboTracking();
    }

    /**
     * Get total ownership percentage of identified beneficial owners
     */
    public function getTotalIdentifiedOwnership(): float
    {
        return array_sum(array_map(
            fn(BeneficialOwner $ubo) => $ubo->ownershipPercentage,
            $this->beneficialOwners
        ));
    }

    /**
     * Check if all beneficial owners are verified
     */
    public function areAllUbosVerified(): bool
    {
        if (empty($this->beneficialOwners)) {
            return !$this->requiresUboTracking();
        }

        foreach ($this->beneficialOwners as $ubo) {
            if (!$ubo->isVerified()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get count of verified documents
     */
    public function getVerifiedDocumentCount(): int
    {
        return count(array_filter(
            $this->documents,
            fn(DocumentVerification $doc) => $doc->isVerified() && !$doc->isExpired()
        ));
    }

    /**
     * Get documents that are expiring soon
     * 
     * @return array<DocumentVerification>
     */
    public function getExpiringDocuments(int $withinDays = 30): array
    {
        return array_filter(
            $this->documents,
            fn(DocumentVerification $doc) => $doc->isExpiringSoon($withinDays)
        );
    }

    /**
     * Calculate verification completeness percentage
     */
    public function getCompletenessScore(): float
    {
        $requiredChecks = [
            'identity_verified' => $this->isVerified(),
            'address_verified' => $this->addressVerification?->isVerified ?? false,
            'documents_complete' => $this->getVerifiedDocumentCount() >= $this->dueDiligenceLevel->minimumVerificationScore() / 25,
            'risk_assessed' => $this->riskAssessment->riskScore > 0,
        ];

        if ($this->requiresUboTracking()) {
            $requiredChecks['ubo_verified'] = $this->areAllUbosVerified();
            $requiredChecks['ubo_complete'] = $this->getTotalIdentifiedOwnership() >= 100;
        }

        $completed = count(array_filter($requiredChecks));
        $total = count($requiredChecks);

        return ($completed / $total) * 100;
    }

    /**
     * Get missing requirements for complete verification
     * 
     * @return array<string>
     */
    public function getMissingRequirements(): array
    {
        $missing = [];

        if (!$this->isVerified()) {
            $missing[] = 'Identity verification incomplete';
        }

        if ($this->addressVerification === null || !$this->addressVerification->isVerified) {
            $missing[] = 'Address verification required';
        }

        $requiredDocs = $this->dueDiligenceLevel->requiredDocumentTypes();
        $verifiedDocTypes = array_map(
            fn(DocumentVerification $doc) => $doc->documentType,
            array_filter($this->documents, fn(DocumentVerification $doc) => $doc->isVerified())
        );

        foreach ($requiredDocs as $docType) {
            if (!in_array($docType, $verifiedDocTypes, true)) {
                $missing[] = "Missing document: {$docType->label()}";
            }
        }

        if ($this->requiresUboTracking()) {
            if (empty($this->beneficialOwners)) {
                $missing[] = 'Beneficial ownership declaration required';
            } elseif ($this->getTotalIdentifiedOwnership() < 100) {
                $missing[] = sprintf(
                    'Incomplete ownership chain (%.1f%% identified)',
                    $this->getTotalIdentifiedOwnership()
                );
            }

            foreach ($this->beneficialOwners as $ubo) {
                if (!$ubo->isVerified()) {
                    $missing[] = "Unverified beneficial owner: {$ubo->fullName}";
                }
            }
        }

        if ($this->dueDiligenceLevel->requiresSourceOfFunds()) {
            $hasSourceOfFunds = $this->additionalData['source_of_funds'] ?? null;
            if ($hasSourceOfFunds === null) {
                $missing[] = 'Source of funds declaration required';
            }
        }

        return $missing;
    }

    /**
     * Check if profile meets minimum requirements for the required DD level
     */
    public function meetsMinimumRequirements(): bool
    {
        return empty($this->getMissingRequirements())
            && $this->verificationScore >= $this->dueDiligenceLevel->minimumVerificationScore();
    }

    /**
     * Convert to array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'party_id' => $this->partyId,
            'party_type' => $this->partyType->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'due_diligence_level' => $this->dueDiligenceLevel->value,
            'risk_assessment' => $this->riskAssessment->toArray(),
            'address_verification' => $this->addressVerification?->toArray(),
            'documents' => array_map(fn(DocumentVerification $d) => $d->toArray(), $this->documents),
            'beneficial_owners' => array_map(fn(BeneficialOwner $u) => $u->toArray(), $this->beneficialOwners),
            'verified_at' => $this->verifiedAt?->format('c'),
            'expires_at' => $this->expiresAt?->format('Y-m-d'),
            'next_review_date' => $this->nextReviewDate?->format('Y-m-d'),
            'verified_by' => $this->verifiedBy,
            'verification_score' => $this->verificationScore,
            'is_verified' => $this->isVerified(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'requires_edd' => $this->requiresEdd(),
            'completeness_score' => $this->getCompletenessScore(),
            'missing_requirements' => $this->getMissingRequirements(),
            'additional_data' => $this->additionalData,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
        ];
    }
}
