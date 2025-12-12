<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Payment;

use Nexus\Common\ValueObjects\Money;

/**
 * Early Payment Discount Missed Event
 * 
 * Fired when an early payment discount opportunity expires without capture.
 */
final readonly class EarlyPaymentDiscountMissedEvent
{
    public function __construct(
        public string $invoiceId,
        public string $vendorId,
        public Money $invoiceAmount,
        public Money $missedDiscountAmount,
        public float $discountPercentage,
        public string $discountTerms,
        public \DateTimeImmutable $discountDeadline,
        public int $daysOverdue,
        public string $missedReason,
        public \DateTimeImmutable $occurredAt,
        public array $metadata = [],
    ) {}

    /**
     * Create discount missed event due to late payment
     */
    public static function latePayment(
        string $invoiceId,
        string $vendorId,
        Money $invoiceAmount,
        Money $missedDiscountAmount,
        float $discountPercentage,
        string $discountTerms,
        \DateTimeImmutable $discountDeadline,
    ): self {
        $now = new \DateTimeImmutable();
        $interval = $discountDeadline->diff($now);
        $daysOverdue = is_int($interval->days) ? $interval->days : 0;

        return new self(
            invoiceId: $invoiceId,
            vendorId: $vendorId,
            invoiceAmount: $invoiceAmount,
            missedDiscountAmount: $missedDiscountAmount,
            discountPercentage: $discountPercentage,
            discountTerms: $discountTerms,
            discountDeadline: $discountDeadline,
            daysOverdue: $daysOverdue,
            missedReason: "Payment made {$daysOverdue} days after discount deadline",
            occurredAt: $now,
        );
    }

    /**
     * Create discount missed event due to approval delay
     */
    public static function approvalDelay(
        string $invoiceId,
        string $vendorId,
        Money $invoiceAmount,
        Money $missedDiscountAmount,
        float $discountPercentage,
        string $discountTerms,
        \DateTimeImmutable $discountDeadline,
    ): self {
        $now = new \DateTimeImmutable();
        $daysOverdue = (int) $discountDeadline->diff($now)->days;

        return new self(
            invoiceId: $invoiceId,
            vendorId: $vendorId,
            invoiceAmount: $invoiceAmount,
            missedDiscountAmount: $missedDiscountAmount,
            discountPercentage: $discountPercentage,
            discountTerms: $discountTerms,
            discountDeadline: $discountDeadline,
            daysOverdue: $daysOverdue,
            missedReason: 'Payment approval delayed beyond discount deadline',
            occurredAt: $now,
        );
    }

    /**
     * Create discount missed event due to batch processing delay
     */
    public static function batchProcessingDelay(
        string $invoiceId,
        string $vendorId,
        Money $invoiceAmount,
        Money $missedDiscountAmount,
        float $discountPercentage,
        string $discountTerms,
        \DateTimeImmutable $discountDeadline,
    ): self {
        $now = new \DateTimeImmutable();
        $daysOverdue = (int) $discountDeadline->diff($now)->days;

        return new self(
            invoiceId: $invoiceId,
            vendorId: $vendorId,
            invoiceAmount: $invoiceAmount,
            missedDiscountAmount: $missedDiscountAmount,
            discountPercentage: $discountPercentage,
            discountTerms: $discountTerms,
            discountDeadline: $discountDeadline,
            daysOverdue: $daysOverdue,
            missedReason: 'Batch payment processing missed discount deadline',
            occurredAt: $now,
        );
    }

    /**
     * Get opportunity cost description
     */
    public function getOpportunityCostDescription(): string
    {
        return sprintf(
            'Missed %s (%.1f%%) early payment discount on invoice - Reason: %s',
            $this->missedDiscountAmount->format(),
            $this->discountPercentage,
            $this->missedReason
        );
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return 'procurement.payment.discount_missed';
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
            'missed_discount_amount' => $this->missedDiscountAmount->toArray(),
            'discount_percentage' => $this->discountPercentage,
            'discount_terms' => $this->discountTerms,
            'discount_deadline' => $this->discountDeadline->format('Y-m-d'),
            'days_overdue' => $this->daysOverdue,
            'missed_reason' => $this->missedReason,
            'opportunity_cost_description' => $this->getOpportunityCostDescription(),
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
