<?php

declare(strict_types=1);

namespace App\Services\Receivable;

use Nexus\Receivable\Contracts\PaymentAllocationStrategyInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceInterface;
use Nexus\Receivable\Exceptions\PaymentAllocationException;

/**
 * Manual Payment Allocation Strategy
 *
 * Uses user-specified allocation amounts.
 */
final readonly class ManualAllocationStrategy implements PaymentAllocationStrategyInterface
{
    /**
     * @param array<string, float> $manualAllocations Map of invoice_id => amount
     */
    public function __construct(
        private array $manualAllocations
    ) {}

    /**
     * @param CustomerInvoiceInterface[] $openInvoices
     * @return array<string, float>
     */
    public function allocate(float $paymentAmount, array $openInvoices): array
    {
        if ($paymentAmount <= 0) {
            throw PaymentAllocationException::invalidAllocation('Payment amount must be greater than zero');
        }

        if (empty($this->manualAllocations)) {
            throw PaymentAllocationException::invalidAllocation('Manual allocations must be provided');
        }

        // Validate manual allocations don't exceed payment amount
        $totalAllocated = array_sum($this->manualAllocations);
        
        if ($totalAllocated > $paymentAmount + 0.01) {
            throw PaymentAllocationException::insufficientAmount($paymentAmount, $totalAllocated);
        }

        // Create invoice ID map for validation
        $invoiceMap = [];
        foreach ($openInvoices as $invoice) {
            $invoiceMap[$invoice->getId()] = $invoice;
        }

        // Validate all allocated invoices exist and amounts don't exceed outstanding balance
        foreach ($this->manualAllocations as $invoiceId => $amount) {
            if (!isset($invoiceMap[$invoiceId])) {
                throw PaymentAllocationException::invalidAllocation("Invoice {$invoiceId} not found in open invoices");
            }

            $invoice = $invoiceMap[$invoiceId];
            if ($amount > $invoice->getOutstandingBalance() + 0.01) {
                throw PaymentAllocationException::invalidAllocation(
                    "Allocation amount {$amount} exceeds outstanding balance " .
                    "{$invoice->getOutstandingBalance()} for invoice {$invoiceId}"
                );
            }
        }

        return $this->manualAllocations;
    }

    public function getName(): string
    {
        return 'manual';
    }
}
