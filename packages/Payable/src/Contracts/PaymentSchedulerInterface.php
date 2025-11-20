<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Interface for payment scheduling service.
 *
 * Handles payment due date calculation, early payment discounts,
 * and payment reminders.
 */
interface PaymentSchedulerInterface
{
    /**
     * Schedule payment for a posted bill.
     *
     * @param VendorBillInterface $bill Posted bill
     * @param array $options Optional scheduling options
     * @return PaymentScheduleInterface
     */
    public function schedule(VendorBillInterface $bill, array $options = []): PaymentScheduleInterface;

    /**
     * Calculate payment due date based on payment terms.
     *
     * @param \DateTimeInterface $billDate Bill date
     * @param string $paymentTerms Payment terms (e.g., 'net_30', '2/10_net_30')
     * @return \DateTimeInterface Due date
     */
    public function calculateDueDate(\DateTimeInterface $billDate, string $paymentTerms): \DateTimeInterface;

    /**
     * Calculate early payment discount if applicable.
     *
     * @param PaymentScheduleInterface $schedule Payment schedule
     * @param \DateTimeInterface $paymentDate Actual payment date
     * @return float Discount amount (0 if not applicable)
     */
    public function calculateEarlyPaymentDiscount(
        PaymentScheduleInterface $schedule,
        \DateTimeInterface $paymentDate
    ): float;

    /**
     * Get payments due within date range.
     *
     * @param string $tenantId Tenant ULID
     * @param \DateTimeInterface $startDate Start date
     * @param \DateTimeInterface $endDate End date
     * @return array<PaymentScheduleInterface>
     */
    public function getPaymentsDue(
        string $tenantId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array;

    /**
     * Get overdue payments.
     *
     * @param string $tenantId Tenant ULID
     * @param \DateTimeInterface $asOfDate As-of date
     * @return array<PaymentScheduleInterface>
     */
    public function getOverduePayments(string $tenantId, \DateTimeInterface $asOfDate): array;

    /**
     * Update payment schedule.
     *
     * @param string $scheduleId Schedule ULID
     * @param array $data Updated data
     * @return PaymentScheduleInterface
     */
    public function updateSchedule(string $scheduleId, array $data): PaymentScheduleInterface;
}
