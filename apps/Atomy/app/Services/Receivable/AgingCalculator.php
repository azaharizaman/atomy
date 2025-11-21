<?php

declare(strict_types=1);

namespace App\Services\Receivable;

use Nexus\Receivable\Contracts\AgingCalculatorInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\Receivable\ValueObjects\AgingBucket;
use Psr\Log\LoggerInterface;

/**
 * Aging Calculator Service
 *
 * Calculates aging buckets for receivables.
 */
final readonly class AgingCalculator implements AgingCalculatorInterface
{
    public function __construct(
        private CustomerInvoiceRepositoryInterface $invoiceRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * @return array<string, float>
     */
    public function calculateAging(string $customerId, \DateTimeInterface $asOfDate): array
    {
        $openInvoices = $this->invoiceRepository->getOpenInvoices($customerId);

        $aging = [
            'current' => 0.0,
            '1-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '90+' => 0.0,
        ];

        foreach ($openInvoices as $invoice) {
            $daysPastDue = $this->calculateDaysPastDue($invoice->getDueDate(), $asOfDate);
            $outstandingBalance = $invoice->getOutstandingBalance();

            $bucket = $this->getBucket($daysPastDue);
            $aging[$bucket] += $outstandingBalance;
        }

        $this->logger->info('Aging calculated for customer', [
            'customer_id' => $customerId,
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'aging' => $aging,
            'total_outstanding' => array_sum($aging),
        ]);

        return $aging;
    }

    /**
     * @return array<string, array<string, float>>
     */
    public function calculateAgingForAllCustomers(string $tenantId, \DateTimeInterface $asOfDate): array
    {
        // Get all customers with open invoices
        $invoices = $this->invoiceRepository->getByStatus($tenantId, 'posted');

        $customerAging = [];

        foreach ($invoices as $invoice) {
            $customerId = $invoice->getCustomerId();

            if (!isset($customerAging[$customerId])) {
                $customerAging[$customerId] = $this->calculateAging($customerId, $asOfDate);
            }
        }

        return $customerAging;
    }

    public function getAgingBucket(int $daysPastDue): AgingBucket
    {
        return match (true) {
            $daysPastDue < 0 => AgingBucket::CURRENT,
            $daysPastDue <= 30 => AgingBucket::DAYS_1_30,
            $daysPastDue <= 60 => AgingBucket::DAYS_31_60,
            $daysPastDue <= 90 => AgingBucket::DAYS_61_90,
            default => AgingBucket::DAYS_90_PLUS,
        };
    }

    private function calculateDaysPastDue(\DateTimeInterface $dueDate, \DateTimeInterface $asOfDate): int
    {
        $interval = $dueDate->diff($asOfDate);
        
        // If asOfDate is before dueDate, return negative (not yet due)
        if ($asOfDate < $dueDate) {
            return -$interval->days;
        }

        return $interval->days;
    }

    private function getBucket(int $daysPastDue): string
    {
        return match (true) {
            $daysPastDue < 0 => 'current',
            $daysPastDue <= 30 => '1-30',
            $daysPastDue <= 60 => '31-60',
            $daysPastDue <= 90 => '61-90',
            default => '90+',
        };
    }
}
