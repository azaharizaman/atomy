<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Payable\Contracts\PaymentSchedulerInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for dynamic discounting negotiations.
 */
final readonly class DynamicDiscountCoordinator
{
    public function __construct(
        private PaymentSchedulerInterface $scheduler,
        private VendorBillRepositoryInterface $billRepository,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Propose an early payment with an additional discount based on time-value of money.
     *
     * @param string $tenantId
     * @param string $billId
     * @param \DateTimeImmutable $paymentDate Proposed earlier payment date
     * @param float $targetApr Target annual percentage rate for discount calculation
     * @return array{eligible: bool, reason?: string, proposedDiscountCents?: int, daysEarly?: int}
     */
    public function proposeEarlyPayment(
        string $tenantId, 
        string $billId, 
        \DateTimeImmutable $paymentDate,
        float $targetApr = 18.0
    ): array {
        $bill = $this->billRepository->findById($billId);
        
        if (!$bill) {
            throw new \RuntimeException("Bill not found: {$billId}");
        }

        $dueDate = $bill->getDueDate();
        
        // Ensure its a Carbon or DateTime for comparison
        $diff = $paymentDate->diff($dueDate);
        $daysEarly = $diff->invert ? -$diff->days : $diff->days;

        if ($daysEarly <= 0) {
            return [
                'eligible' => false,
                'reason' => 'Proposed payment date is not earlier than the due date.'
            ];
        }

        // Calculation: (APR / 365) * days_early * amount
        $dailyRate = $targetApr / 100 / 365;
        $amount = $bill->getTotalAmountCents() / 100.0;
        $discountValue = $dailyRate * $daysEarly * $amount;

        return [
            'eligible' => true,
            'billId' => $billId,
            'daysEarly' => $daysEarly,
            'proposedDiscountCents' => (int) round($discountValue * 100),
            'currency' => $bill->getCurrency(),
            'targetPaymentDate' => $paymentDate->format('Y-m-d')
        ];
    }
}
