<?php

declare(strict_types=1);

namespace Nexus\Payment\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\AllocatableDocumentInterface;
use Nexus\Payment\Contracts\AllocationEngineInterface;
use Nexus\Payment\Contracts\AllocationStrategyInterface;
use Nexus\Payment\Enums\AllocationMethod;
use Nexus\Payment\Exceptions\AllocationException;
use Nexus\Payment\Strategies\FifoAllocationStrategy;
use Nexus\Payment\Strategies\LargestFirstAllocationStrategy;
use Nexus\Payment\Strategies\LifoAllocationStrategy;
use Nexus\Payment\Strategies\ManualAllocationStrategy;
use Nexus\Payment\Strategies\OldestFirstAllocationStrategy;
use Nexus\Payment\Strategies\ProportionalAllocationStrategy;
use Nexus\Payment\Strategies\SmallestFirstAllocationStrategy;
use Nexus\Payment\ValueObjects\AllocationResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Allocation Engine - Orchestrates payment allocation across documents.
 *
 * This service provides a registry of allocation strategies and orchestrates
 * the allocation process.
 */
final class AllocationEngine implements AllocationEngineInterface
{
    /**
     * @var array<string, AllocationStrategyInterface>
     */
    private array $strategies = [];

    private AllocationMethod $defaultMethod = AllocationMethod::FIFO;

    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->registerDefaultStrategies();
    }

    /**
     * Allocate payment amount across documents using the specified strategy.
     *
     * @param array<AllocatableDocumentInterface> $documents
     * @param array<string, Money>|null $manualAllocations
     */
    public function allocate(
        Money $paymentAmount,
        array $documents,
        AllocationMethod $method,
        ?array $manualAllocations = null,
    ): AllocationResult {
        $this->logger->debug('Starting allocation', [
            'amount' => $paymentAmount->format(),
            'method' => $method->value,
            'document_count' => count($documents),
        ]);

        $strategy = $this->getStrategy($method);

        // Validate documents
        $errors = $strategy->validate($documents);
        if (!empty($errors)) {
            throw new AllocationException(
                'Allocation validation failed: ' . implode(', ', $errors)
            );
        }

        // Perform allocation
        $result = $strategy->allocate($paymentAmount, $documents, $manualAllocations);

        $this->logger->info('Allocation completed', [
            'method' => $method->value,
            'total_allocated' => $result->getTotalAllocated()->format(),
            'is_fully_allocated' => $result->isFullyAllocated(),
            'allocation_count' => count($result->getAllocations()),
        ]);

        return $result;
    }

    /**
     * Validate allocation can be performed.
     *
     * @param array<AllocatableDocumentInterface> $documents
     * @return array<string>
     */
    public function validateAllocation(
        Money $paymentAmount,
        array $documents,
        AllocationMethod $method,
    ): array {
        $errors = [];

        if (!$paymentAmount->isPositive()) {
            $errors[] = 'Payment amount must be positive';
        }

        if (empty($documents)) {
            $errors[] = 'No documents provided for allocation';

            return $errors;
        }

        $strategy = $this->getStrategy($method);
        $strategyErrors = $strategy->validate($documents);

        return array_merge($errors, $strategyErrors);
    }

    /**
     * Get available allocation strategies.
     *
     * @return array<AllocationMethod>
     */
    public function getAvailableStrategies(): array
    {
        return array_map(
            fn (AllocationStrategyInterface $strategy) => $strategy->getMethod(),
            array_values($this->strategies)
        );
    }

    /**
     * Get the default allocation method.
     */
    public function getDefaultMethod(): AllocationMethod
    {
        return $this->defaultMethod;
    }

    /**
     * Set the default allocation method.
     */
    public function setDefaultMethod(AllocationMethod $method): void
    {
        $this->defaultMethod = $method;
    }

    /**
     * Check if a strategy is registered for the given method.
     */
    public function hasStrategy(AllocationMethod $method): bool
    {
        return isset($this->strategies[$method->value]);
    }

    /**
     * Register a custom allocation strategy.
     */
    public function registerStrategy(AllocationStrategyInterface $strategy): void
    {
        $method = $strategy->getMethod();
        $this->strategies[$method->value] = $strategy;

        $this->logger->debug('Registered allocation strategy', [
            'method' => $method->value,
            'class' => $strategy::class,
        ]);
    }

    /**
     * Get the strategy for a given method.
     */
    public function getStrategy(AllocationMethod $method): AllocationStrategyInterface
    {
        if (!isset($this->strategies[$method->value])) {
            throw new AllocationException(
                "No strategy registered for allocation method: {$method->value}"
            );
        }

        return $this->strategies[$method->value];
    }

    /**
     * Check if a strategy requires user input.
     */
    public function requiresUserInput(AllocationMethod $method): bool
    {
        return $this->getStrategy($method)->requiresUserInput();
    }

    /**
     * Calculate preview of allocation without applying.
     *
     * @param array<AllocatableDocumentInterface> $documents
     */
    public function preview(
        Money $paymentAmount,
        array $documents,
        AllocationMethod $method,
    ): AllocationResult {
        // Preview is the same as allocate, but we don't persist anything
        return $this->allocate($paymentAmount, $documents, $method, null);
    }

    /**
     * Register default allocation strategies.
     */
    private function registerDefaultStrategies(): void
    {
        $this->registerStrategy(new FifoAllocationStrategy());
        $this->registerStrategy(new LifoAllocationStrategy());
        $this->registerStrategy(new ProportionalAllocationStrategy());
        $this->registerStrategy(new ManualAllocationStrategy());
        $this->registerStrategy(new OldestFirstAllocationStrategy());
        $this->registerStrategy(new SmallestFirstAllocationStrategy());
        $this->registerStrategy(new LargestFirstAllocationStrategy());
    }
}
