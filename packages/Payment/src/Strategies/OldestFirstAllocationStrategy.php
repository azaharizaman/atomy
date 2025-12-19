<?php

declare(strict_types=1);

namespace Nexus\Payment\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\AllocatableDocumentInterface;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\ValueObjects\AllocationResult;

/**
 * Oldest first allocation strategy.
 *
 * Prioritizes documents by due date, paying the oldest due first.
 * This helps avoid late fees and maintains good vendor relationships.
 */
final class OldestFirstAllocationStrategy extends AbstractAllocationStrategy
{
    public function getMethod(): AllocationMethod
    {
        return AllocationMethod::OLDEST_FIRST;
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

        $sortedDocuments = $this->sortDocuments($payableDocuments);
        $remainingAmount = $paymentAmount;
        $allocations = [];

        foreach ($sortedDocuments as $document) {
            if ($remainingAmount->isZero()) {
                break;
            }

            $outstandingBalance = $document->getOutstandingBalance();

            // Allocate the minimum of remaining amount and outstanding balance
            $allocationAmount = $remainingAmount->greaterThan($outstandingBalance)
                ? $outstandingBalance
                : $remainingAmount;

            $allocations[$document->getId()] = $allocationAmount;
            $remainingAmount = $remainingAmount->subtract($allocationAmount);
        }

        return $this->buildResult($paymentAmount, $allocations);
    }

    /**
     * Sort documents by due date (oldest due date first).
     * Documents without due dates are placed at the end.
     *
     * @param array<AllocatableDocumentInterface> $documents
     * @return array<AllocatableDocumentInterface>
     */
    protected function sortDocuments(array $documents): array
    {
        $sorted = $documents;
        usort($sorted, function (
            AllocatableDocumentInterface $a,
            AllocatableDocumentInterface $b
        ): int {
            $dueDateA = $a->getDueDate();
            $dueDateB = $b->getDueDate();

            // Documents without due dates go to the end
            if ($dueDateA === null && $dueDateB === null) {
                return $a->getDocumentDate() <=> $b->getDocumentDate();
            }

            if ($dueDateA === null) {
                return 1;
            }

            if ($dueDateB === null) {
                return -1;
            }

            return $dueDateA <=> $dueDateB;
        });

        return $sorted;
    }
}
