<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Contracts;

/**
 * Contract for accounting operation coordinators.
 */
interface AccountingCoordinatorInterface
{
    /**
     * Get the coordinator name.
     */
    public function getName(): string;

    /**
     * Check if required data is available.
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool;

    /**
     * Get the supported operations.
     *
     * @return array<string>
     */
    public function getSupportedOperations(): array;
}
