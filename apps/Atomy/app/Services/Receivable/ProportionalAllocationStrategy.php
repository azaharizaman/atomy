<?php

declare(strict_types=1);

namespace App\Services\Receivable;

use Nexus\Receivable\Contracts\PaymentAllocationStrategyInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceInterface;
use Nexus\Receivable\Exceptions\PaymentAllocationException;

/**
 * Proportional Payment Allocation Strategy
 *
 * Distributes payment proportionally across all open invoices.
 */
final readonly class ProportionalAllocationStrategy implements PaymentAllocationStrategyInterface
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

        // Calculate total outstanding balance
        $totalOutstanding = array_reduce(
            $openInvoices,
            fn($carry, CustomerInvoiceInterface $invoice) => $carry + $invoice->getOutstandingBalance(),
            0.0
        );

        if ($totalOutstanding <= 0) {
            throw PaymentAllocationException::invalidAllocation('Total outstanding balance must be greater than zero');
        }

        $allocations = [];

        // Distribute proportionally
        foreach ($openInvoices as $invoice) {
            $proportion = $invoice->getOutstandingBalance() / $totalOutstanding;
            $allocationAmount = round($paymentAmount * $proportion, 2);

            if ($allocationAmount > 0) {
                $allocations[$invoice->getId()] = $allocationAmount;
            }
        }

        // Handle rounding discrepancy by adjusting largest allocation
        $totalAllocated = array_sum($allocations);
        $difference = round($paymentAmount - $totalAllocated, 2);

        if (abs($difference) > 0.01) {
            // Find invoice with largest allocation
            $largestInvoiceId = array_keys($allocations, max($allocations))[0];
            $allocations[$largestInvoiceId] += $difference;
        }

        return $allocations;
    }

    public function getName(): string
    {
        return 'proportional';
    }
}
