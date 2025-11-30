<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\Entities\ConsolidatedStatement;
use Nexus\Accounting\Domain\ValueObjects\ConsolidationConfig;
use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;

/**
 * Contract for managing financial consolidation operations.
 *
 * Implementations must handle consolidation of financial statements
 * across multiple entities, including elimination of intercompany
 * transactions and proper handling of minority interests.
 */
interface ConsolidationManagerInterface
{
    /**
     * Perform consolidation for the specified entities and period.
     *
     * @param array<string> $entityIds The entity IDs to consolidate
     * @param ReportingPeriod $period The period for consolidation
     * @param ConsolidationConfig $config Consolidation configuration
     *
     * @return ConsolidatedStatement The consolidated financial statement
     *
     * @throws \RuntimeException If consolidation fails
     */
    public function consolidate(
        array $entityIds,
        ReportingPeriod $period,
        ConsolidationConfig $config
    ): ConsolidatedStatement;

    /**
     * Eliminate intercompany transactions.
     *
     * @param array<string> $entityIds The entity IDs to process
     * @param ReportingPeriod $period The period for elimination
     *
     * @return array<string, mixed> Elimination journal entries
     *
     * @throws \RuntimeException If elimination fails
     */
    public function eliminateIntercompanyTransactions(
        array $entityIds,
        ReportingPeriod $period
    ): array;

    /**
     * Calculate currency translation adjustments.
     *
     * @param string $entityId The entity ID
     * @param string $targetCurrency The target currency code
     * @param ReportingPeriod $period The period for translation
     *
     * @return array<string, mixed> Translation adjustments
     */
    public function calculateCurrencyTranslation(
        string $entityId,
        string $targetCurrency,
        ReportingPeriod $period
    ): array;
}
