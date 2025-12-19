<?php

declare(strict_types=1);

namespace Nexus\Payment\Strategies;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\AllocatableDocumentInterface;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Exceptions\AllocationException;
use Nexus\Payment\ValueObjects\AllocationResult;

/**
 * Manual allocation strategy.
 *
 * Allows the user to specify exact allocation amounts for each document.
 */
final class ManualAllocationStrategy extends AbstractAllocationStrategy
{
    public function getMethod(): AllocationMethod
    {
        return AllocationMethod::MANUAL;
    }

    public function requiresUserInput(): bool
    {
        return true;
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
        if ($manualAllocations === null || empty($manualAllocations)) {
            throw AllocationException::manualAllocationRequired();
        }

        $payableDocuments = $this->filterPayableDocuments($documents);

        if (empty($payableDocuments)) {
            return AllocationResult::empty($paymentAmount);
        }

        // Create document map for quick lookup
        $documentMap = [];
        foreach ($payableDocuments as $document) {
            $documentMap[$document->getId()] = $document;
        }

        $allocations = [];
        $totalAllocated = Money::zero($paymentAmount->getCurrency());

        foreach ($manualAllocations as $documentId => $allocationAmount) {
            // Validate document exists
            if (!isset($documentMap[$documentId])) {
                throw new AllocationException(
                    "Document with ID '{$documentId}' not found in provided documents"
                );
            }

            $document = $documentMap[$documentId];

            // Validate currency matches
            if ($allocationAmount->getCurrency() !== $paymentAmount->getCurrency()) {
                throw AllocationException::currencyMismatch(
                    $paymentAmount->getCurrency(),
                    $allocationAmount->getCurrency()
                );
            }

            // Validate amount doesn't exceed outstanding balance
            if ($allocationAmount->greaterThan($document->getOutstandingBalance())) {
                throw new AllocationException(
                    "Allocation amount {$allocationAmount->format()} exceeds " .
                    "outstanding balance {$document->getOutstandingBalance()->format()} " .
                    "for document '{$documentId}'"
                );
            }

            // Validate amount is positive
            if (!$allocationAmount->isPositive()) {
                throw new AllocationException(
                    "Allocation amount must be positive for document '{$documentId}'"
                );
            }

            $allocations[$documentId] = $allocationAmount;
            $totalAllocated = $totalAllocated->add($allocationAmount);
        }

        // Validate total allocation doesn't exceed payment amount
        if ($totalAllocated->greaterThan($paymentAmount)) {
            throw new AllocationException(
                "Total allocation {$totalAllocated->format()} exceeds " .
                "payment amount {$paymentAmount->format()}"
            );
        }

        return $this->buildResult($paymentAmount, $allocations);
    }

    /**
     * @param array<AllocatableDocumentInterface> $documents
     * @return array<string>
     */
    public function validate(array $documents): array
    {
        $errors = parent::validate($documents);

        // Additional validation: warn that manual allocations are required
        if (empty($errors)) {
            // Note: This is informational, not an error
        }

        return $errors;
    }
}
