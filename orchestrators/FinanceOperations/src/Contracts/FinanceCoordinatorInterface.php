<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Base contract for finance operation coordinators.
 *
 * This interface follows the Interface Segregation Principle (ISP) by defining
 * only the core methods that all finance coordinators must implement.
 * Specialized coordinator interfaces extend this base interface.
 *
 * Orchestrators define their own interfaces to maintain independence from
 * atomic package interfaces, enabling loose coupling and testability.
 *
 * @see ARCHITECTURE.md Section 5: Orchestrator Interface Segregation
 */
interface FinanceCoordinatorInterface
{
    /**
     * Get the coordinator name for identification and logging.
     */
    public function getName(): string;

    /**
     * Check if required data is available for the coordinator's operations.
     *
     * @param string $tenantId The tenant identifier
     * @param string $periodId The accounting period identifier
     * @return bool True if all required data is available
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool;

    /**
     * Get the list of operations this coordinator supports.
     *
     * @return array<string> List of supported operation names
     */
    public function getSupportedOperations(): array;
}
