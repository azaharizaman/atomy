<?php

declare(strict_types=1);

namespace Nexus\Payment\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\AllocatableDocumentInterface;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\ValueObjects\AllocationResult;

/**
 * Proportional allocation strategy.
 *
 * Distributes the payment proportionally across all documents based on
 * their outstanding balances.
 */
final class ProportionalAllocationStrategy extends AbstractAllocationStrategy
{
    public function getMethod(): AllocationMethod
    {
        return AllocationMethod::PROPORTIONAL;
    }

    /**
     * @param array<AllocatableDocumentInterface> $documents
     * @param array<string, Money>|null $manualAllocations
     */
    public function allocate(
        Money $paymentAmount,
        array $documents,
        ?array $manualAllocations = null,
    ): AllocationResult {
        $payableDocuments = $this->filterPayableDocuments($documents);

        if (empty($payableDocuments)) {
            return AllocationResult::empty($paymentAmount);
        }

        $totalOutstanding = $this->calculateTotalOutstanding($payableDocuments);

        // If payment covers all outstanding, fully allocate each document's balance
        if ($paymentAmount->greaterThanOrEqual($totalOutstanding)) {
            $allocations = [];
            foreach ($payableDocuments as $document) {
                $allocations[$document->getId()] = $document->getOutstandingBalance();
            }

            // Use buildResult to properly calculate isFullyAllocated based on unallocated amount
            return $this->buildResult($paymentAmount, $allocations);
        }

        $allocations = [];
        $remainingAmount = $paymentAmount;
        $currency = $paymentAmount->getCurrency();

        // Calculate proportional allocation for each document
        $documentCount = count($payableDocuments);
        $currentIndex = 0;

        foreach ($payableDocuments as $document) {
            $currentIndex++;
            $isLastDocument = $currentIndex === $documentCount;

            if ($isLastDocument) {
                // Assign remaining to avoid rounding issues
                if ($remainingAmount->isPositive()) {
                    $allocations[$document->getId()] = $remainingAmount;
                }
            } else {
                $outstandingBalance = $document->getOutstandingBalance();

                // Calculate proportion as a ratio
                $proportion = $this->calculateProportion(
                    $outstandingBalance,
                    $totalOutstanding
                );

                // Calculate allocation amount
                $allocationAmount = Money::of(
                    (string) round(
                        (float) $paymentAmount->getAmount() * $proportion,
                        2
                    ),
                    $currency
                );

                // Don't allocate more than outstanding or remaining
                if ($allocationAmount->greaterThan($outstandingBalance)) {
                    $allocationAmount = $outstandingBalance;
                }

                if ($allocationAmount->greaterThan($remainingAmount)) {
                    $allocationAmount = $remainingAmount;
                }

                if ($allocationAmount->isPositive()) {
                    $allocations[$document->getId()] = $allocationAmount;
                    $remainingAmount = $remainingAmount->subtract($allocationAmount);
                }
            }
        }

        return $this->buildResult($paymentAmount, $allocations);
    }

    /**
     * Calculate the proportion of one amount relative to total.
     */
    private function calculateProportion(Money $amount, Money $total): float
    {
        if ($total->isZero()) {
            return 0.0;
        }

        return (float) $amount->getAmount() / (float) $total->getAmount();
    }
}
