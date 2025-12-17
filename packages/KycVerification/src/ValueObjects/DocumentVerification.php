<?php

declare(strict_types=1);

namespace Nexus\KycVerification\ValueObjects;

use Nexus\KycVerification\Enums\DocumentType;
use Nexus\KycVerification\Enums\VerificationStatus;

/**
 * Represents the result of a document verification.
 * 
 * @immutable
 */
final readonly class DocumentVerification
{
    /**
     * @param array<string, mixed> $extractedData
     * @param array<string> $validationErrors
     */
    public function __construct(
        public string $documentId,
        public DocumentType $documentType,
        public VerificationStatus $status,
        public float $confidenceScore,
        public \DateTimeImmutable $verifiedAt,
        public ?string $documentNumber = null,
        public ?\DateTimeImmutable $issueDate = null,
        public ?\DateTimeImmutable $expiryDate = null,
        public ?string $issuingAuthority = null,
        public ?string $issuingCountry = null,
        public array $extractedData = [],
        public array $validationErrors = [],
        public ?string $verifierId = null,
        public ?string $verificationMethod = null,
    ) {}

    /**
     * Check if document is verified
     */
    public function isVerified(): bool
    {
        return $this->status === VerificationStatus::VERIFIED;
    }

    /**
     * Check if document has expired
     */
    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate < new \DateTimeImmutable();
    }

    /**
     * Check if document is expiring soon (within days)
     */
    public function isExpiringSoon(int $withinDays = 30): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        $threshold = (new \DateTimeImmutable())->modify("+{$withinDays} days");
        return $this->expiryDate <= $threshold && !$this->isExpired();
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry(): ?int
    {
        if ($this->expiryDate === null) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->expiryDate);

        return $diff->invert === 1 ? -$diff->days : $diff->days;
    }

    /**
     * Check if confidence score meets threshold
     */
    public function meetsConfidenceThreshold(float $threshold = 0.85): bool
    {
        return $this->confidenceScore >= $threshold;
    }

    /**
     * Check if document has validation errors
     */
    public function hasErrors(): bool
    {
        return count($this->validationErrors) > 0;
    }

    /**
     * Get document age in days from issue date
     */
    public function getDocumentAgeInDays(): ?int
    {
        if ($this->issueDate === null) {
            return null;
        }

        $now = new \DateTimeImmutable();
        return (int) $now->diff($this->issueDate)->days;
    }

    /**
     * Check if document meets age requirements
     */
    public function meetsAgeRequirement(): bool
    {
        $maxAge = $this->documentType->maxAgeDays();
        if ($maxAge === null) {
            return true;
        }

        $age = $this->getDocumentAgeInDays();
        if ($age === null) {
            return false; // Cannot verify age without issue date
        }

        return $age <= $maxAge;
    }

    /**
     * Get the verification weight for this document
     */
    public function getVerificationWeight(): int
    {
        if (!$this->isVerified() || $this->isExpired()) {
            return 0;
        }

        return (int) ($this->documentType->verificationWeight() * $this->confidenceScore);
    }

    /**
     * Create a verified document result
     * 
     * @param array<string, mixed> $extractedData
     */
    public static function verified(
        string $documentId,
        DocumentType $documentType,
        float $confidenceScore,
        array $extractedData = [],
        ?string $documentNumber = null,
        ?\DateTimeImmutable $expiryDate = null,
    ): self {
        return new self(
            documentId: $documentId,
            documentType: $documentType,
            status: VerificationStatus::VERIFIED,
            confidenceScore: $confidenceScore,
            verifiedAt: new \DateTimeImmutable(),
            documentNumber: $documentNumber,
            expiryDate: $expiryDate,
            extractedData: $extractedData,
        );
    }

    /**
     * Create a failed document verification result
     * 
     * @param array<string> $errors
     */
    public static function failed(
        string $documentId,
        DocumentType $documentType,
        array $errors,
    ): self {
        return new self(
            documentId: $documentId,
            documentType: $documentType,
            status: VerificationStatus::REJECTED,
            confidenceScore: 0.0,
            verifiedAt: new \DateTimeImmutable(),
            validationErrors: $errors,
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
            'document_id' => $this->documentId,
            'document_type' => $this->documentType->value,
            'status' => $this->status->value,
            'confidence_score' => $this->confidenceScore,
            'verified_at' => $this->verifiedAt->format('c'),
            'document_number' => $this->documentNumber,
            'issue_date' => $this->issueDate?->format('Y-m-d'),
            'expiry_date' => $this->expiryDate?->format('Y-m-d'),
            'issuing_authority' => $this->issuingAuthority,
            'issuing_country' => $this->issuingCountry,
            'extracted_data' => $this->extractedData,
            'validation_errors' => $this->validationErrors,
            'is_expired' => $this->isExpired(),
            'is_verified' => $this->isVerified(),
            'verification_weight' => $this->getVerificationWeight(),
        ];
    }
}
