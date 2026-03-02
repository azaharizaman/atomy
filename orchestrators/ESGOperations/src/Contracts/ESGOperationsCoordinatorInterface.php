<?php

declare(strict_types=1);

namespace Nexus\ESGOperations\Contracts;

/**
 * Interface for the ESG Operations Coordinator.
 * 
 * Orchestrates the flow of sustainability data from raw capture to audited ESG scores
 * and regulatory reporting.
 */
interface ESGOperationsCoordinatorInterface
{
    /**
     * Process a raw sustainability event and promote it to an ESG metric.
     */
    public function processEvent(string $tenantId, string $eventId): void;

    /**
     * Run a carbon price simulation for a tenant.
     * 
     * @param array<string, mixed> $parameters Simulation parameters
     */
    public function simulateCarbonTax(string $tenantId, array $parameters): array;

    /**
     * Generate an automated regulatory disclosure draft.
     * 
     * @param string $framework e.g., 'CSRD', 'NSRF'
     */
    public function generateDisclosureDraft(string $tenantId, string $framework, \DateTimeInterface $periodStart, \DateTimeInterface $periodEnd): array;
}
