<?php

declare(strict_types=1);

namespace Nexus\Payable\Services;

use Nexus\Payable\Contracts\PaymentSchedulerInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\Payable\Contracts\PaymentScheduleInterface;
use Nexus\Payable\Contracts\PaymentScheduleRepositoryInterface;
use Nexus\Payable\Enums\PaymentTerm;
use Nexus\Payable\Enums\PaymentStatus;
use Nexus\Payable\Exceptions\BillNotFoundException;
use Nexus\Payable\Exceptions\VendorNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Payment scheduling service.
 */
final class PaymentScheduler implements PaymentSchedulerInterface
{
    public function __construct(
        private readonly VendorBillRepositoryInterface $billRepository,
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly PaymentScheduleRepositoryInterface $paymentScheduleRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function schedule(string $billId): PaymentScheduleInterface
    {
        $bill = $this->billRepository->findById($billId);
        if (!$bill) {
            throw BillNotFoundException::forId($billId);
        }

        $vendor = $this->vendorRepository->findById($bill->getVendorId());
        if (!$vendor) {
            throw VendorNotFoundException::forId($bill->getVendorId());
        }

        // Get payment terms
        $paymentTerm = PaymentTerm::from($vendor->getPaymentTerms());

        // Calculate due date
        $dueDate = $this->calculateDueDate($bill->getBillDate(), $paymentTerm);

        // Calculate early payment discount
        $earlyPaymentDiscount = $paymentTerm->getEarlyPaymentDiscount();
        $discountDate = null;
        $discountPercent = 0.0;

        if ($earlyPaymentDiscount) {
            $discountPercent = $earlyPaymentDiscount['percent'];
            $discountDate = (clone $bill->getBillDate())->modify("+{$earlyPaymentDiscount['days']} days");
        }

        // Create payment schedule
        $schedule = $this->paymentScheduleRepository->create($bill->getTenantId(), [
            'bill_id' => $billId,
            'vendor_id' => $vendor->getId(),
            'scheduled_amount' => $bill->getTotalAmount(),
            'due_date' => $dueDate->format('Y-m-d'),
            'early_payment_discount_percent' => $discountPercent,
            'early_payment_discount_date' => $discountDate?->format('Y-m-d'),
            'status' => PaymentStatus::SCHEDULED->value,
            'currency' => $bill->getCurrency(),
        ]);

        $this->logger->info("Payment scheduled for bill {$billId}: Due {$dueDate->format('Y-m-d')}");

        return $schedule;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateDueDate(\DateTimeInterface $billDate, PaymentTerm $paymentTerm, ?int $customDays = null): \DateTimeInterface
    {
        if ($paymentTerm === PaymentTerm::CUSTOM && $customDays !== null) {
            return (clone $billDate)->modify("+{$customDays} days");
        }

        return $paymentTerm->calculateDueDate($billDate);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateEarlyPaymentDiscount(PaymentScheduleInterface $schedule, \DateTimeInterface $paymentDate): float
    {
        if ($schedule->getEarlyPaymentDiscountPercent() <= 0.0) {
            return 0.0;
        }

        $discountDate = $schedule->getEarlyPaymentDiscountDate();
        if (!$discountDate || $paymentDate > $discountDate) {
            return 0.0;
        }

        return $schedule->getScheduledAmount() * ($schedule->getEarlyPaymentDiscountPercent() / 100.0);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentsDue(string $tenantId, \DateTimeInterface $asOfDate): array
    {
        return $this->paymentScheduleRepository->getDueByDate($tenantId, $asOfDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getOverduePayments(string $tenantId): array
    {
        $today = new \DateTime();
        return $this->paymentScheduleRepository->getOverdue($tenantId, $today);
    }

    /**
     * {@inheritdoc}
     */
    public function reschedule(string $scheduleId, \DateTimeInterface $newDueDate, string $reason): PaymentScheduleInterface
    {
        $schedule = $this->paymentScheduleRepository->findById($scheduleId);
        if (!$schedule) {
            throw new \RuntimeException("Payment schedule '{$scheduleId}' not found");
        }

        $updated = $this->paymentScheduleRepository->update($scheduleId, [
            'due_date' => $newDueDate->format('Y-m-d'),
        ]);

        $this->logger->warning("Payment rescheduled: {$scheduleId} to {$newDueDate->format('Y-m-d')} - {$reason}");

        return $updated;
    }
}
