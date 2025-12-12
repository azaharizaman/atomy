<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Payment;

use Nexus\Common\ValueObjects\Money;

/**
 * Early Payment Discount Captured Event
 * 
 * Fired when an early payment discount is captured.
 */
final readonly class EarlyPaymentDiscountCapturedEvent
{
    public function __construct(
        public string $invoiceId,
        public string $vendorId,
        public Money $invoiceAmount,
        public Money $discountAmount,
        public Money $paidAmount,
        public float $discountPercentage,
        public int $daysPaidEarly,
        public float $annualizedReturnRate,
        public string $discountTerms,
        public string $capturedBy,
        public \DateTimeImmutable $capturedAt,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * Create discount captured event
     */
    public static function create(
        string $invoiceId,
        string $vendorId,
        Money $invoiceAmount,
        Money $discountAmount,
        float $discountPercentage,
        int $daysPaidEarly,
        float $annualizedReturnRate,
        string $discountTerms,
        string $capturedBy,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            invoiceId: $invoiceId,
            vendorId: $vendorId,
            invoiceAmount: $invoiceAmount,
            discountAmount: $discountAmount,
            paidAmount: $invoiceAmount->subtract($discountAmount),
            discountPercentage: $discountPercentage,
            daysPaidEarly: $daysPaidEarly,
            annualizedReturnRate: $annualizedReturnRate,
            discountTerms: $discountTerms,
            capturedBy: $capturedBy,
            capturedAt: $now,
            occurredAt: $now,
        );
    }

    /**
     * Get savings description
     */
    public function getSavingsDescription(): string
    {
        return sprintf(
            'Captured %s (%.1f%%) early payment discount by paying %d days early - %.1f%% annualized return',
            $this->discountAmount->format(),
            $this->discountPercentage,
            $this->daysPaidEarly,
            $this->annualizedReturnRate
        );
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return 'procurement.payment.discount_captured';
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'event_name' => $this->getEventName(),
            'invoice_id' => $this->invoiceId,
            'vendor_id' => $this->vendorId,
            'invoice_amount' => $this->invoiceAmount->toArray(),
            'discount_amount' => $this->discountAmount->toArray(),
            'paid_amount' => $this->paidAmount->toArray(),
            'discount_percentage' => $this->discountPercentage,
            'days_paid_early' => $this->daysPaidEarly,
            'annualized_return_rate' => $this->annualizedReturnRate,
            'discount_terms' => $this->discountTerms,
            'captured_by' => $this->capturedBy,
            'captured_at' => $this->capturedAt->format('c'),
            'savings_description' => $this->getSavingsDescription(),
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
