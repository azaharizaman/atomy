<?php

declare(strict_types=1);

namespace App\Services\Receivable;

use Nexus\Receivable\Contracts\PaymentAllocationStrategyInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceInterface;
use Nexus\Receivable\Exceptions\PaymentAllocationException;

/**
 * FIFO Payment Allocation Strategy
 *
 * Applies payment to oldest invoice first (First In, First Out).
 */
final readonly class FifoAllocationStrategy implements PaymentAllocationStrategyInterface
{
    /**
     * @param CustomerInvoiceInterface[] $openInvoices
     * @return array<string, float>
     */
    public function allocate(float $paymentAmount, array $openInvoices): array
    {
        if ($paymentAmount <= 0) {
            throw PaymentAllocationException::invalidAllocation('Payment amount must be greater than zero');
        }

        if (empty($openInvoices)) {
            throw PaymentAllocationException::invalidAllocation('No open invoices provided');
        }

        // Sort by invoice date (oldest first)
        usort($openInvoices, function (CustomerInvoiceInterface $a, CustomerInvoiceInterface $b) {
            return $a->getInvoiceDate() <=> $b->getInvoiceDate();
        });

        $allocations = [];
        $remainingAmount = $paymentAmount;

        foreach ($openInvoices as $invoice) {
            if ($remainingAmount <= 0) {
                break;
            }

            $outstandingBalance = $invoice->getOutstandingBalance();
            $allocationAmount = min($remainingAmount, $outstandingBalance);

            $allocations[$invoice->getId()] = $allocationAmount;
            $remainingAmount -= $allocationAmount;
        }

        return $allocations;
    }

    public function getName(): string
    {
        return 'fifo';
    }
}
