<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Interface for payment allocation and GL posting.
 *
 * Handles payment processing, GL journal entry creation,
 * and payment reconciliation.
 */
interface PaymentAllocationInterface
{
    /**
     * Process payment and post to GL.
     *
     * Creates GL journal entry:
     * - Debit: AP Liability account
     * - Credit: Bank/Cash account
     *
     * @param PaymentScheduleInterface $schedule Payment schedule
     * @param array $paymentData Payment details
     * @return PaymentInterface
     * @throws \Nexus\Payable\Exceptions\PaymentScheduleException
     */
    public function processPayment(
        PaymentScheduleInterface $schedule,
        array $paymentData
    ): PaymentInterface;

    /**
     * Allocate payment to multiple bills.
     *
     * @param array $paymentData Payment details
     * @param array $allocations Array of bill allocations
     * @return PaymentInterface
     */
    public function allocatePayment(array $paymentData, array $allocations): PaymentInterface;

    /**
     * Reconcile payment with GL posting.
     *
     * @param string $paymentId Payment ULID
     * @param string $glJournalId GL journal entry ID
     * @return bool
     */
    public function reconcilePayment(string $paymentId, string $glJournalId): bool;

    /**
     * Calculate payment amount with currency conversion.
     *
     * @param PaymentScheduleInterface $schedule Payment schedule
     * @param \DateTimeInterface $paymentDate Payment date
     * @return array ['amount' => float, 'currency' => string, 'exchange_rate' => float]
     */
    public function calculatePaymentAmount(
        PaymentScheduleInterface $schedule,
        \DateTimeInterface $paymentDate
    ): array;

    /**
     * Void payment and reverse GL posting.
     *
     * @param string $paymentId Payment ULID
     * @param string $reason Void reason
     * @return bool
     */
    public function voidPayment(string $paymentId, string $reason): bool;

    /**
     * Get payment history for a bill.
     *
     * @param string $billId Bill ULID
     * @return array<PaymentInterface>
     */
    public function getPaymentHistory(string $billId): array;
}
