<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Contracts;

use Nexus\AccountConsolidation\ValueObjects\IntercompanyBalance;

/**
 * Contract for providing consolidation data.
 *
 * This interface is implemented by the orchestrator to fetch
 * data from Nexus\Finance and related packages.
 */
interface ConsolidationDataProviderInterface
{
    /**
     * Get financial data for an entity.
     *
     * @param string $entityId
     * @param \DateTimeImmutable $asOfDate
     * @return array<string, mixed>
     */
    public function getEntityFinancialData(string $entityId, \DateTimeImmutable $asOfDate): array;

    /**
     * Get intercompany balances between entities.
     *
     * @param array<string> $entityIds
     * @param \DateTimeImmutable $asOfDate
     * @return array<IntercompanyBalance>
     */
    public function getIntercompanyBalances(array $entityIds, \DateTimeImmutable $asOfDate): array;

    /**
     * Get ownership data for entities.
     *
     * @param array<string> $entityIds
     * @return array<string, array<string, float>>
     */
    public function getOwnershipData(array $entityIds): array;

    /**
     * Get exchange rates for currency translation.
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param \DateTimeImmutable $asOfDate
     * @return float
     */
    public function getExchangeRate(
        string $fromCurrency,
        string $toCurrency,
        \DateTimeImmutable $asOfDate
    ): float;
}
