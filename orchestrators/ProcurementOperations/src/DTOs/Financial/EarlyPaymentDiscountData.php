<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Early Payment Discount Data
 * 
 * Represents early payment discount terms (e.g., 2/10 Net 30).
 * Discount percentage if paid within discount days, otherwise full amount due.
 */
final readonly class EarlyPaymentDiscountData
{
    public function __construct(
        public string $vendorId,
        public string $invoiceId,
        public Money $invoiceAmount,
        public float $discountPercentage,
        public int $discountDays,
        public int $netDays,
        public \DateTimeImmutable $invoiceDate,
        public ?\DateTimeImmutable $discountCapturedAt = null,
        public ?Money $discountAmount = null,
        public ?string $capturedBy = null,
        public ?string $captureNotes = null,
    ) {}

    /**
     * Create standard 2/10 Net 30 discount terms
     */
    public static function twoTenNet30(
        string $vendorId,
        string $invoiceId,
        Money $invoiceAmount,
        \DateTimeImmutable $invoiceDate,
    ): self {
        $discountAmount = $invoiceAmount->multiply(0.02);

        return new self(
            vendorId: $vendorId,
            invoiceId: $invoiceId,
            invoiceAmount: $invoiceAmount,
            discountPercentage: 2.0,
            discountDays: 10,
            netDays: 30,
            invoiceDate: $invoiceDate,
            discountAmount: $discountAmount,
        );
    }

    /**
     * Create standard 1/15 Net 45 discount terms
     */
    public static function oneQuarterNet45(
        string $vendorId,
        string $invoiceId,
        Money $invoiceAmount,
        \DateTimeImmutable $invoiceDate,
    ): self {
        $discountAmount = $invoiceAmount->multiply(0.01);

        return new self(
            vendorId: $vendorId,
            invoiceId: $invoiceId,
            invoiceAmount: $invoiceAmount,
            discountPercentage: 1.0,
            discountDays: 15,
            netDays: 45,
            invoiceDate: $invoiceDate,
            discountAmount: $discountAmount,
        );
    }

    /**
     * Create custom discount terms
     */
    public static function custom(
        string $vendorId,
        string $invoiceId,
        Money $invoiceAmount,
        float $discountPercentage,
        int $discountDays,
        int $netDays,
        \DateTimeImmutable $invoiceDate,
    ): self {
        $discountAmount = $invoiceAmount->multiply($discountPercentage / 100);

        return new self(
            vendorId: $vendorId,
            invoiceId: $invoiceId,
            invoiceAmount: $invoiceAmount,
            discountPercentage: $discountPercentage,
            discountDays: $discountDays,
            netDays: $netDays,
            invoiceDate: $invoiceDate,
            discountAmount: $discountAmount,
        );
    }

    /**
     * Get discount deadline date
     */
    public function getDiscountDeadline(): \DateTimeImmutable
    {
        return $this->invoiceDate->modify("+{$this->discountDays} days");
    }

    /**
     * Get payment due date (net days)
     */
    public function getNetDueDate(): \DateTimeImmutable
    {
        return $this->invoiceDate->modify("+{$this->netDays} days");
    }

    /**
     * Check if discount is still available
     */
    public function isDiscountAvailable(?\DateTimeImmutable $asOf = null): bool
    {
        $checkDate = $asOf ?? new \DateTimeImmutable();
        return $checkDate <= $this->getDiscountDeadline();
    }

    /**
     * Check if discount was captured
     */
    public function isDiscountCaptured(): bool
    {
        return $this->discountCapturedAt !== null;
    }

    /**
     * Get days remaining for discount
     */
    public function getDaysRemainingForDiscount(?\DateTimeImmutable $asOf = null): int
    {
        $checkDate = $asOf ?? new \DateTimeImmutable();
        $deadline = $this->getDiscountDeadline();

        if ($checkDate > $deadline) {
            return 0;
        }

        return (int) $checkDate->diff($deadline)->days;
    }

    /**
     * Calculate net amount after discount
     */
    public function getNetAmountWithDiscount(): Money
    {
        if ($this->discountAmount === null) {
            return $this->invoiceAmount;
        }

        return $this->invoiceAmount->subtract($this->discountAmount);
    }

    /**
     * Calculate annualized return rate of early payment
     * 
     * Formula: (Discount % / (100 - Discount %)) × (365 / (Net Days - Discount Days))
     */
    public function getAnnualizedReturnRate(): float
    {
        $daysDifference = $this->netDays - $this->discountDays;
        if ($daysDifference <= 0) {
            return 0.0;
        }

        $discountFactor = $this->discountPercentage / (100 - $this->discountPercentage);
        $annualizationFactor = 365 / $daysDifference;

        return round($discountFactor * $annualizationFactor * 100, 2);
    }

    /**
     * Get formatted discount terms string (e.g., "2/10 Net 30")
     */
    public function getTermsString(): string
    {
        return sprintf(
            '%.1f/%d Net %d',
            $this->discountPercentage,
            $this->discountDays,
            $this->netDays
        );
    }

    /**
     * Record discount capture
     */
    public function withCapture(
        \DateTimeImmutable $capturedAt,
        string $capturedBy,
        ?string $notes = null,
    ): self {
        return new self(
            vendorId: $this->vendorId,
            invoiceId: $this->invoiceId,
            invoiceAmount: $this->invoiceAmount,
            discountPercentage: $this->discountPercentage,
            discountDays: $this->discountDays,
            netDays: $this->netDays,
            invoiceDate: $this->invoiceDate,
            discountCapturedAt: $capturedAt,
            discountAmount: $this->discountAmount,
            capturedBy: $capturedBy,
            captureNotes: $notes,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'vendor_id' => $this->vendorId,
            'invoice_id' => $this->invoiceId,
            'invoice_amount' => $this->invoiceAmount->toArray(),
            'discount_percentage' => $this->discountPercentage,
            'discount_days' => $this->discountDays,
            'net_days' => $this->netDays,
            'invoice_date' => $this->invoiceDate->format('Y-m-d'),
            'discount_deadline' => $this->getDiscountDeadline()->format('Y-m-d'),
            'net_due_date' => $this->getNetDueDate()->format('Y-m-d'),
            'discount_amount' => $this->discountAmount?->toArray(),
            'net_amount_with_discount' => $this->getNetAmountWithDiscount()->toArray(),
            'terms_string' => $this->getTermsString(),
            'annualized_return_rate' => $this->getAnnualizedReturnRate(),
            'is_captured' => $this->isDiscountCaptured(),
            'captured_at' => $this->discountCapturedAt?->format('c'),
            'captured_by' => $this->capturedBy,
        ];
    }
}
