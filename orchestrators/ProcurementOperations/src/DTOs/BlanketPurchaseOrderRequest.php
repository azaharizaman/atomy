<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for creating a Blanket Purchase Order (long-term agreement).
 *
 * Blanket POs establish framework agreements with vendors for a specified period
 * and maximum spend limit, allowing release orders against them.
 */
final readonly class BlanketPurchaseOrderRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor to create agreement with
     * @param string $description Agreement description
     * @param int $maxAmountCents Maximum spend limit in cents
     * @param string $currency Currency code (ISO 4217)
     * @param \DateTimeImmutable $effectiveFrom Agreement start date
     * @param \DateTimeImmutable $effectiveTo Agreement end date
     * @param string $requesterId User creating the blanket PO
     * @param array<string> $categoryIds Product categories covered by this agreement
     * @param array<string, mixed> $terms Agreement terms and conditions
     * @param int|null $minOrderAmountCents Minimum amount per release order
     * @param int|null $warningThresholdPercent Percentage of spend to trigger warning (e.g., 80)
     * @param string|null $paymentTerms Payment terms code (e.g., NET30, NET60)
     * @param string|null $costCenterId Cost center for charges
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public string $description,
        public int $maxAmountCents,
        public string $currency,
        public \DateTimeImmutable $effectiveFrom,
        public \DateTimeImmutable $effectiveTo,
        public string $requesterId,
        public array $categoryIds = [],
        public array $terms = [],
        public ?int $minOrderAmountCents = null,
        public ?int $warningThresholdPercent = 80,
        public ?string $paymentTerms = null,
        public ?string $costCenterId = null,
        public array $metadata = [],
    ) {}

    /**
     * Validate the request data.
     *
     * @return array<string, string> Validation errors keyed by field
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->maxAmountCents <= 0) {
            $errors['maxAmountCents'] = 'Maximum amount must be positive';
        }

        if ($this->effectiveTo <= $this->effectiveFrom) {
            $errors['effectiveTo'] = 'End date must be after start date';
        }

        if ($this->minOrderAmountCents !== null && $this->minOrderAmountCents > $this->maxAmountCents) {
            $errors['minOrderAmountCents'] = 'Minimum order cannot exceed maximum spend';
        }

        if ($this->warningThresholdPercent !== null) {
            if ($this->warningThresholdPercent < 1 || $this->warningThresholdPercent > 100) {
                $errors['warningThresholdPercent'] = 'Warning threshold must be between 1 and 100';
            }
        }

        if (strlen($this->currency) !== 3) {
            $errors['currency'] = 'Currency must be a valid ISO 4217 code';
        }

        return $errors;
    }

    /**
     * Calculate the warning amount threshold in cents.
     */
    public function getWarningAmountCents(): int
    {
        $threshold = $this->warningThresholdPercent ?? 80;
        return (int) (($this->maxAmountCents * $threshold) / 100);
    }

    /**
     * Get the agreement duration in days.
     */
    public function getDurationDays(): int
    {
        return $this->effectiveFrom->diff($this->effectiveTo)->days;
    }
}
