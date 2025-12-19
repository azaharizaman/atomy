<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

use Nexus\Common\ValueObjects\Money;

/**
 * Represents the result of a payment allocation operation.
 *
 * Contains the details of how a payment was allocated across multiple documents
 * (invoices, bills, etc.) and any remaining unallocated amount.
 */
final readonly class AllocationResult
{
    /**
     * @param Money $totalAmount Total payment amount
     * @param Money $allocatedAmount Total amount successfully allocated
     * @param Money $unallocatedAmount Remaining unallocated amount
     * @param array<string, Money> $allocations Map of document ID to allocated amount
     * @param bool $isFullyAllocated Whether the entire payment was allocated
     * @param bool $hasOverpayment Whether allocation exceeds document totals
     */
    public function __construct(
        public Money $totalAmount,
        public Money $allocatedAmount,
        public Money $unallocatedAmount,
        public array $allocations,
        public bool $isFullyAllocated,
        public bool $hasOverpayment = false,
    ) {
    }

    /**
     * Create an empty allocation result.
     */
    public static function empty(Money $totalAmount): self
    {
        return new self(
            totalAmount: $totalAmount,
            allocatedAmount: Money::zero($totalAmount->getCurrency()),
            unallocatedAmount: $totalAmount,
            allocations: [],
            isFullyAllocated: false,
        );
    }

    /**
     * Create a fully allocated result.
     *
     * @param Money $totalAmount Total payment amount
     * @param array<string, Money> $allocations Map of document ID to allocated amount
     */
    public static function fullyAllocated(Money $totalAmount, array $allocations): self
    {
        return new self(
            totalAmount: $totalAmount,
            allocatedAmount: $totalAmount,
            unallocatedAmount: Money::zero($totalAmount->getCurrency()),
            allocations: $allocations,
            isFullyAllocated: true,
        );
    }

    /**
     * Create a partial allocation result.
     *
     * @param Money $totalAmount Total payment amount
     * @param Money $allocatedAmount Amount that was allocated
     * @param array<string, Money> $allocations Map of document ID to allocated amount
     */
    public static function partial(Money $totalAmount, Money $allocatedAmount, array $allocations): self
    {
        return new self(
            totalAmount: $totalAmount,
            allocatedAmount: $allocatedAmount,
            unallocatedAmount: $totalAmount->subtract($allocatedAmount),
            allocations: $allocations,
            isFullyAllocated: false,
        );
    }

    /**
     * Get the number of documents allocated to.
     */
    public function getAllocationCount(): int
    {
        return count($this->allocations);
    }

    /**
     * Get allocation for a specific document.
     */
    public function getAllocationFor(string $documentId): ?Money
    {
        return $this->allocations[$documentId] ?? null;
    }

    /**
     * Check if a specific document was allocated to.
     */
    public function hasAllocationFor(string $documentId): bool
    {
        return isset($this->allocations[$documentId]);
    }

    /**
     * Get the allocation percentage.
     */
    public function getAllocationPercentage(): float
    {
        if ($this->totalAmount->isZero()) {
            return 0.0;
        }

        return ($this->allocatedAmount->getAmount() / $this->totalAmount->getAmount()) * 100;
    }

    /**
     * Convert allocations to array format.
     *
     * @return array<string, array{document_id: string, amount: float, currency: string}>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->allocations as $documentId => $amount) {
            $result[$documentId] = [
                'document_id' => $documentId,
                'amount' => $amount->getAmount(),
                'currency' => $amount->getCurrency(),
            ];
        }

        return $result;
    }

    /**
     * Get all allocated document IDs.
     *
     * @return array<string>
     */
    public function getDocumentIds(): array
    {
        return array_keys($this->allocations);
    }

    /**
     * Get all allocations.
     *
     * @return array<string, Money>
     */
    public function getAllocations(): array
    {
        return $this->allocations;
    }

    /**
     * Get the total allocated amount.
     *
     * Alias for allocatedAmount property for method chaining consistency.
     */
    public function getTotalAllocated(): Money
    {
        return $this->allocatedAmount;
    }

    /**
     * Check if fully allocated.
     */
    public function isFullyAllocated(): bool
    {
        return $this->isFullyAllocated;
    }

    /**
     * Get the unallocated amount.
     */
    public function getUnallocatedAmount(): Money
    {
        return $this->unallocatedAmount;
    }

    /**
     * Check if there is any unallocated amount remaining.
     */
    public function hasUnallocatedAmount(): bool
    {
        return $this->unallocatedAmount->isPositive();
    }
}
