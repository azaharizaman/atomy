<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\ValueObjects\AllocationResult;

/**
 * Contract for payment allocation strategies.
 *
 * Implementations define how a payment amount is distributed
 * across multiple outstanding documents.
 */
interface AllocationStrategyInterface
{
    /**
     * Get the allocation method this strategy implements.
     */
    public function getMethod(): AllocationMethod;

    /**
     * Allocate the payment amount across the given documents.
     *
     * @param Money $paymentAmount The payment amount to allocate
     * @param array<AllocatableDocumentInterface> $documents Documents to allocate to
     * @param array<string, Money>|null $manualAllocations Manual allocation specifications (for MANUAL method)
     * @return AllocationResult The allocation result
     */
    public function allocate(
        Money $paymentAmount,
        array $documents,
        ?array $manualAllocations = null,
    ): AllocationResult;

    /**
     * Check if this strategy requires user input.
     */
    public function requiresUserInput(): bool;

    /**
     * Validate the documents can be allocated with this strategy.
     *
     * @param array<AllocatableDocumentInterface> $documents
     * @return array<string> Validation errors, empty if valid
     */
    public function validate(array $documents): array;
}
