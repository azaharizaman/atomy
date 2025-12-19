<?php

declare(strict_types=1);

namespace Nexus\Payment\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\AllocatableDocumentInterface;
use Nexus\Payment\Contracts\AllocationStrategyInterface;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\ValueObjects\AllocationResult;

/**
 * Abstract base class for allocation strategies.
 *
 * Provides common functionality for all allocation strategy implementations.
 */
abstract class AbstractAllocationStrategy implements AllocationStrategyInterface
{
    /**
     * Get the allocation method this strategy implements.
     */
    abstract public function getMethod(): AllocationMethod;

    /**
     * Check if this strategy requires user input.
     */
    public function requiresUserInput(): bool
    {
        return false;
    }

    /**
     * Validate the documents can be allocated with this strategy.
     *
     * @param array<AllocatableDocumentInterface> $documents
     * @return array<string> Validation errors, empty if valid
     */
    public function validate(array $documents): array
    {
        $errors = [];

        if (empty($documents)) {
            $errors[] = 'No documents provided for allocation';

            return $errors;
        }

        // Check all documents have the same currency
        $currencies = array_unique(
            array_map(fn ($doc) => $doc->getCurrency(), $documents)
        );

        if (count($currencies) > 1) {
            $errors[] = 'All documents must have the same currency';
        }

        // Check for documents that are already fully paid
        $allFullyPaid = true;
        foreach ($documents as $document) {
            if (!$document->isFullyPaid()) {
                $allFullyPaid = false;

                break;
            }
        }

        if ($allFullyPaid) {
            $errors[] = 'All documents are already fully paid';
        }

        return $errors;
    }

    /**
     * Sort documents for allocation.
     *
     * Override this method in subclasses to implement different sort orders.
     *
     * @param array<AllocatableDocumentInterface> $documents
     * @return array<AllocatableDocumentInterface>
     */
    protected function sortDocuments(array $documents): array
    {
        return $documents;
    }

    /**
     * Filter out fully paid documents.
     *
     * @param array<AllocatableDocumentInterface> $documents
     * @return array<AllocatableDocumentInterface>
     */
    protected function filterPayableDocuments(array $documents): array
    {
        return array_filter(
            $documents,
            fn (AllocatableDocumentInterface $doc) => !$doc->isFullyPaid()
        );
    }

    /**
     * Calculate total outstanding balance across all documents.
     *
     * @param array<AllocatableDocumentInterface> $documents
     */
    protected function calculateTotalOutstanding(array $documents): Money
    {
        if (empty($documents)) {
            return Money::zero('USD');
        }

        $currency = reset($documents)->getCurrency();
        $total = Money::zero($currency);

        foreach ($documents as $document) {
            $total = $total->add($document->getOutstandingBalance());
        }

        return $total;
    }

    /**
     * Build an allocation result from allocations map.
     *
     * @param Money $paymentAmount
     * @param array<string, Money> $allocations
     */
    protected function buildResult(Money $paymentAmount, array $allocations): AllocationResult
    {
        $currency = $paymentAmount->getCurrency();
        $allocatedTotal = Money::zero($currency);

        foreach ($allocations as $amount) {
            $allocatedTotal = $allocatedTotal->add($amount);
        }

        $unallocated = $paymentAmount->subtract($allocatedTotal);

        if ($allocatedTotal->isZero() && $paymentAmount->isPositive()) {
            return AllocationResult::empty($paymentAmount);
        }

        if ($unallocated->isZero()) {
            return AllocationResult::fullyAllocated($paymentAmount, $allocations);
        }

        return AllocationResult::partial($paymentAmount, $allocatedTotal, $allocations);
    }
}
