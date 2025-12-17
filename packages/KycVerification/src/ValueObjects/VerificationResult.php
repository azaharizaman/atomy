<?php

declare(strict_types=1);

namespace Nexus\KycVerification\ValueObjects;

use Nexus\KycVerification\Enums\DueDiligenceLevel;
use Nexus\KycVerification\Enums\VerificationStatus;

/**
 * Represents the result of a verification operation.
 * 
 * @immutable
 */
final readonly class VerificationResult
{
    /**
     * @param array<string> $errors
     * @param array<string> $warnings
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $partyId,
        public string $verificationId,
        public VerificationStatus $status,
        public DueDiligenceLevel $appliedDueDiligence,
        public int $score,
        public \DateTimeImmutable $verifiedAt,
        public ?RiskAssessment $riskAssessment = null,
        public array $errors = [],
        public array $warnings = [],
        public array $metadata = [],
        public ?string $verifiedBy = null,
        public ?\DateTimeImmutable $expiresAt = null,
        public ?string $verificationMethod = null,
    ) {}

    /**
     * Check if verification was successful
     */
    public function isSuccess(): bool
    {
        return $this->status === VerificationStatus::VERIFIED
            || $this->status === VerificationStatus::CONDITIONALLY_VERIFIED;
    }

    /**
     * Check if verification failed
     */
    public function isFailed(): bool
    {
        return $this->status === VerificationStatus::REJECTED;
    }

    /**
     * Check if verification is pending
     */
    public function isPending(): bool
    {
        return $this->status === VerificationStatus::PENDING
            || $this->status === VerificationStatus::IN_PROGRESS
            || $this->status === VerificationStatus::UNDER_REVIEW;
    }

    /**
     * Check if there are errors
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Check if there are warnings
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Check if conditionally verified (requires additional action)
     */
    public function isConditional(): bool
    {
        return $this->status === VerificationStatus::CONDITIONALLY_VERIFIED;
    }

    /**
     * Get the primary error if any
     */
    public function getPrimaryError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Create a successful verification result
     */
    public static function success(
        string $partyId,
        string $verificationId,
        int $score,
        ?RiskAssessment $riskAssessment = null,
        DueDiligenceLevel $appliedDueDiligence = DueDiligenceLevel::STANDARD,
    ): self {
        return new self(
            partyId: $partyId,
            verificationId: $verificationId,
            status: VerificationStatus::VERIFIED,
            appliedDueDiligence: $appliedDueDiligence,
            score: $score,
            verifiedAt: new \DateTimeImmutable(),
            riskAssessment: $riskAssessment,
        );
    }

    /**
     * Create a conditional verification result
     * 
     * @param array<string> $conditions
     */
    public static function conditional(
        string $partyId,
        string $verificationId,
        int $score,
        array $conditions,
        ?RiskAssessment $riskAssessment = null,
    ): self {
        return new self(
            partyId: $partyId,
            verificationId: $verificationId,
            status: VerificationStatus::CONDITIONALLY_VERIFIED,
            appliedDueDiligence: DueDiligenceLevel::STANDARD,
            score: $score,
            verifiedAt: new \DateTimeImmutable(),
            riskAssessment: $riskAssessment,
            warnings: $conditions,
        );
    }

    /**
     * Create a failed verification result
     * 
     * @param array<string> $errors
     */
    public static function failed(
        string $partyId,
        string $verificationId,
        array $errors,
    ): self {
        return new self(
            partyId: $partyId,
            verificationId: $verificationId,
            status: VerificationStatus::REJECTED,
            appliedDueDiligence: DueDiligenceLevel::STANDARD,
            score: 0,
            verifiedAt: new \DateTimeImmutable(),
            errors: $errors,
        );
    }

    /**
     * Create a pending verification result
     */
    public static function pending(
        string $partyId,
        string $verificationId,
    ): self {
        return new self(
            partyId: $partyId,
            verificationId: $verificationId,
            status: VerificationStatus::PENDING,
            appliedDueDiligence: DueDiligenceLevel::STANDARD,
            score: 0,
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
            'party_id' => $this->partyId,
            'verification_id' => $this->verificationId,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'applied_due_diligence' => $this->appliedDueDiligence->value,
            'score' => $this->score,
            'verified_at' => $this->verifiedAt->format('c'),
            'verified_by' => $this->verifiedBy,
            'expires_at' => $this->expiresAt?->format('c'),
            'verification_method' => $this->verificationMethod,
            'risk_assessment' => $this->riskAssessment?->toArray(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'is_success' => $this->isSuccess(),
            'is_failed' => $this->isFailed(),
            'is_pending' => $this->isPending(),
            'is_conditional' => $this->isConditional(),
            'metadata' => $this->metadata,
        ];
    }
}
