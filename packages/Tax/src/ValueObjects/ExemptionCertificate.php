<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Tax\Enums\TaxExemptionReason;
use Nexus\Tax\Exceptions\InvalidExemptionPercentageException;

/**
 * Exemption Certificate: Tax exemption certificate
 * 
 * Represents a certificate that grants partial or full tax exemption.
 * Supports expiration dates and validation.
 * 
 * Immutable and validated on construction.
 */
final readonly class ExemptionCertificate
{
    /**
     * @param string $certificateId Unique certificate identifier
     * @param string $customerId Customer this certificate belongs to
     * @param TaxExemptionReason $reason Reason for exemption
     * @param string $exemptionPercentage Percentage exemption (0.0000 to 100.0000)
     * @param \DateTimeImmutable $issueDate When certificate was issued
     * @param \DateTimeImmutable|null $expirationDate When certificate expires (null = never)
     * @param string|null $issuingAuthority Authority that issued certificate
     * @param string|null $jurisdictionCode Jurisdiction where certificate is valid
     * @param string|null $pdfStoragePath Path to PDF certificate file
     * @param array<string, mixed> $metadata Optional custom metadata
     * 
     * @throws InvalidExemptionPercentageException
     */
    public function __construct(
        public string $certificateId,
        public string $customerId,
        public TaxExemptionReason $reason,
        public string $exemptionPercentage,
        public \DateTimeImmutable $issueDate,
        public ?\DateTimeImmutable $expirationDate = null,
        public ?string $issuingAuthority = null,
        public ?string $jurisdictionCode = null,
        public ?string $pdfStoragePath = null,
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->certificateId)) {
            throw new \InvalidArgumentException('Certificate ID cannot be empty');
        }

        if (empty($this->customerId)) {
            throw new \InvalidArgumentException('Customer ID cannot be empty');
        }

        // Validate exemption percentage
        if (!is_numeric($this->exemptionPercentage)) {
            throw new InvalidExemptionPercentageException($this->exemptionPercentage, 'Exemption percentage must be numeric');
        }

        $percentage = $this->exemptionPercentage;
        if (bccomp($percentage, '0.0000', 4) < 0 || bccomp($percentage, '100.0000', 4) > 0) {
            throw new InvalidExemptionPercentageException(
                $percentage,
                'Exemption percentage must be between 0.0000 and 100.0000'
            );
        }

        // Validate expiration is after issue
        if ($this->expirationDate !== null && $this->expirationDate <= $this->issueDate) {
            throw new \InvalidArgumentException(
                "Expiration date ({$this->expirationDate->format('Y-m-d')}) must be after issue date ({$this->issueDate->format('Y-m-d')})"
            );
        }
    }

    /**
     * Check if certificate is valid on a given date
     */
    public function isValidOn(\DateTimeInterface $date): bool
    {
        $checkDate = $date instanceof \DateTimeImmutable
            ? $date
            : \DateTimeImmutable::createFromInterface($date);

        // Must be issued
        if ($checkDate < $this->issueDate) {
            return false;
        }

        // Must not be expired
        if ($this->expirationDate !== null && $checkDate >= $this->expirationDate) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is a full exemption (100%)
     */
    public function isFullExemption(): bool
    {
        return bccomp($this->exemptionPercentage, '100.0000', 4) === 0;
    }

    /**
     * Check if this is a partial exemption (0% < x < 100%)
     */
    public function isPartialExemption(): bool
    {
        return bccomp($this->exemptionPercentage, '0.0000', 4) > 0
            && bccomp($this->exemptionPercentage, '100.0000', 4) < 0;
    }

    /**
     * Get multiplier to apply to taxable base (1.0 - exemption%)
     * 
     * Example:
     * - 0% exemption: 1.0 (no reduction)
     * - 50% exemption: 0.5 (half taxable)
     * - 100% exemption: 0.0 (not taxable)
     */
    public function getTaxableMultiplier(): string
    {
        // Convert percentage to decimal and subtract from 1
        $percentageDecimal = bcdiv($this->exemptionPercentage, '100', 4);
        return bcsub('1.0000', $percentageDecimal, 4);
    }

    /**
     * Calculate reduced taxable base after exemption
     */
    public function applyToAmount(string $originalAmount): string
    {
        return bcmul($originalAmount, $this->getTaxableMultiplier(), 4);
    }

    /**
     * Get days until expiration (null if never expires or already expired)
     */
    public function getDaysUntilExpiration(\DateTimeInterface $fromDate = null): ?int
    {
        if ($this->expirationDate === null) {
            return null;
        }

        $from = $fromDate ?? new \DateTimeImmutable();
        $from = $from instanceof \DateTimeImmutable
            ? $from
            : \DateTimeImmutable::createFromInterface($from);

        if ($from >= $this->expirationDate) {
            return 0; // Expired
        }

        $interval = $from->diff($this->expirationDate);
        return (int) $interval->days;
    }

    public function toArray(): array
    {
        return [
            'certificate_id' => $this->certificateId,
            'customer_id' => $this->customerId,
            'reason' => $this->reason->value,
            'exemption_percentage' => $this->exemptionPercentage,
            'is_full_exemption' => $this->isFullExemption(),
            'is_partial_exemption' => $this->isPartialExemption(),
            'issue_date' => $this->issueDate->format('Y-m-d'),
            'expiration_date' => $this->expirationDate?->format('Y-m-d'),
            'issuing_authority' => $this->issuingAuthority,
            'jurisdiction_code' => $this->jurisdictionCode,
            'pdf_storage_path' => $this->pdfStoragePath,
            'metadata' => $this->metadata,
        ];
    }
}
