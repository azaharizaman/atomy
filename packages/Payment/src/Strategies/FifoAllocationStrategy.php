<?php

declare(strict_types=1);

namespace Nexus\Payment\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\AllocatableDocumentInterface;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\ValueObjects\AllocationResult;

/**
 * FIFO (First-In-First-Out) allocation strategy.
 *
 * Allocates payment to documents in order of their creation/document date,
 * with oldest documents receiving payment first.
 */
final class FifoAllocationStrategy extends AbstractAllocationStrategy
{
    public function getMethod(): AllocationMethod
    {
        return AllocationMethod::FIFO;
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
     * Sort documents by document date (oldest first).
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
            return $a->getDocumentDate() <=> $b->getDocumentDate();
        });

        return $sorted;
    }
}
