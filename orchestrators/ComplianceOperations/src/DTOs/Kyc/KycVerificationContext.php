<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Kyc;

/**
 * Context DTO for KYC verification data.
 *
 * Aggregates all KYC-related data for compliance workflows.
 */
final readonly class KycVerificationContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $partyId Party identifier
     * @param array<string, mixed> $profile Profile data
     * @param string $verificationStatus Current verification status
     * @param int $verificationScore Verification score (0-100)
     * @param array<string, mixed>|null $riskAssessment Risk assessment data
     * @param bool $canTransact Whether party can transact
     * @param array<int, array<string, mixed>> $documents Document verifications
     * @param array<string, mixed>|null $reviewSchedule Review schedule data
     * @param array<int, array<string, mixed>> $verificationHistory Verification history
     * @param \DateTimeImmutable|null $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $updatedAt Last update timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $partyId,
        public array $profile,
        public string $verificationStatus,
        public int $verificationScore,
        public ?array $riskAssessment,
        public bool $canTransact,
        public array $documents,
        public ?array $reviewSchedule,
        public array $verificationHistory,
        public ?\DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Check if KYC is verified.
     */
    public function isVerified(): bool
    {
        return $this->verificationStatus === 'verified';
    }

    /**
     * Check if KYC is pending.
     */
    public function isPending(): bool
    {
        return $this->verificationStatus === 'pending';
    }

    /**
     * Check if party is high risk.
     */
    public function isHighRisk(): bool
    {
        return $this->riskAssessment !== null
            && in_array($this->riskAssessment['riskLevel'] ?? '', ['high', 'very_high', 'prohibited'], true);
    }

    /**
     * Check if enhanced due diligence is required.
     */
    public function requiresEnhancedDueDiligence(): bool
    {
        return $this->riskAssessment !== null
            && in_array($this->riskAssessment['riskLevel'] ?? '', ['high', 'very_high', 'prohibited'], true);
    }

    /**
     * Get days until verification expires.
     */
    public function getDaysUntilExpiry(): ?int
    {
        $expiryDate = $this->profile['verificationExpiry'] ?? null;
        
        if ($expiryDate === null) {
            return null;
        }

        $expiry = \DateTimeImmutable::createFromFormat('Y-m-d', $expiryDate);
        
        if ($expiry === false) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->diff($expiry);

        return $diff->invert === 1 ? -$diff->days : $diff->days;
    }

    /**
     * Check if review is overdue.
     */
    public function isReviewOverdue(): bool
    {
        return $this->reviewSchedule['overdue'] ?? false;
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'partyId' => $this->partyId,
            'profile' => $this->profile,
            'verificationStatus' => $this->verificationStatus,
            'verificationScore' => $this->verificationScore,
            'riskAssessment' => $this->riskAssessment,
            'canTransact' => $this->canTransact,
            'documents' => $this->documents,
            'reviewSchedule' => $this->reviewSchedule,
            'verificationHistory' => $this->verificationHistory,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
