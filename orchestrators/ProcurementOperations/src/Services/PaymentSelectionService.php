<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Payable\Contracts\PaymentSchedulerInterface;
use Nexus\Payable\Contracts\PaymentScheduleInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service to optimize payment selection for cash flow and discount capture.
 */
final readonly class PaymentSelectionService
{
    public function __construct(
        private PaymentSchedulerInterface $scheduler,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Select payments for a pay run based on optimization criteria.
     * 
     * @param string $tenantId
     * @param \DateTimeImmutable $runDate
     * @param array{max_amount_cents?: int, capture_discounts_only?: bool} $options
     * @return array<PaymentScheduleInterface>
     */
    public function selectPaymentsForRun(
        string $tenantId, 
        \DateTimeImmutable $runDate, 
        array $options = []
    ): array {
        $this->logger->info('Selecting payments for optimized pay run', [
            'tenant_id' => $tenantId,
            'run_date' => $runDate->format('Y-m-d')
        ]);

        // 1. Get all payments due in the next 14 days (standard window)
        $windowEnd = $runDate->modify('+14 days');
        $dueSchedules = $this->scheduler->getPaymentsDue($tenantId, $runDate, $windowEnd);
        $overdueSchedules = $this->scheduler->getOverduePayments($tenantId, $runDate);

        $candidates = array_merge($overdueSchedules, $dueSchedules);
        $selected = [];
        $totalSelectedCents = 0;

        // 2. Sort candidates: 
        // Priority 1: Overdue
        // Priority 2: Early Payment Discount expiration (imminent)
        // Priority 3: Due Date imminent
        usort($candidates, function(PaymentScheduleInterface $a, PaymentScheduleInterface $b) use ($runDate) {
            $discountA = $this->scheduler->calculateEarlyPaymentDiscount($a, $runDate);
            $discountB = $this->scheduler->calculateEarlyPaymentDiscount($b, $runDate);

            if ($discountA > 0 && $discountB <= 0) return -1;
            if ($discountB > 0 && $discountA <= 0) return 1;
            
            return $a->getDueDate() <=> $b->getDueDate();
        });

        // 3. Filter based on budget and strategy
        foreach ($candidates as $schedule) {
            if (isset($options['capture_discounts_only']) && $options['capture_discounts_only']) {
                if ($this->scheduler->calculateEarlyPaymentDiscount($schedule, $runDate) <= 0) {
                    continue;
                }
            }

            if (isset($options['max_amount_cents']) && ($totalSelectedCents + $schedule->getAmountCents() > $options['max_amount_cents'])) {
                break; // Stop if budget exceeded
            }

            $selected[] = $schedule;
            $totalSelectedCents += $schedule->getAmountCents();
        }

        return $selected;
    }
}
