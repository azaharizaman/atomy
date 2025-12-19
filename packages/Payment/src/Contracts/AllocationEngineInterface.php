<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\ValueObjects\AllocationResult;

/**
 * Contract for allocation engine service.
 *
 * Orchestrates payment allocation across multiple documents
 * using different allocation strategies.
 */
interface AllocationEngineInterface
{
    /**
     * Allocate a payment amount across documents using the specified method.
     *
     * @param Money $paymentAmount Payment amount to allocate
     * @param array<AllocatableDocumentInterface> $documents Documents to allocate to
     * @param AllocationMethod $method Allocation method to use
     * @param array<string, Money>|null $manualAllocations Manual allocations (for MANUAL method)
     * @return AllocationResult Allocation result
     */
    public function allocate(
        Money $paymentAmount,
        array $documents,
        AllocationMethod $method,
        ?array $manualAllocations = null,
    ): AllocationResult;

    /**
     * Get the default allocation method.
     */
    public function getDefaultMethod(): AllocationMethod;

    /**
     * Set the default allocation method.
     */
    public function setDefaultMethod(AllocationMethod $method): void;

    /**
     * Register an allocation strategy.
     */
    public function registerStrategy(AllocationStrategyInterface $strategy): void;

    /**
     * Check if a strategy is registered for the given method.
     */
    public function hasStrategy(AllocationMethod $method): bool;

    /**
     * Calculate allocation preview without actually allocating.
     *
     * @param Money $paymentAmount Payment amount to allocate
     * @param array<AllocatableDocumentInterface> $documents Documents to allocate to
     * @param AllocationMethod $method Allocation method to use
     * @return AllocationResult Preview result
     */
    public function preview(
        Money $paymentAmount,
        array $documents,
        AllocationMethod $method,
    ): AllocationResult;
}
