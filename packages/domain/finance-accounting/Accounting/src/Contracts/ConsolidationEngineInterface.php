<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

use Nexus\Accounting\Core\ValueObjects\{ReportingPeriod, ConsolidationRule};
use Nexus\Accounting\Core\Enums\ConsolidationMethod;

/**
 * Multi-entity consolidation engine contract.
 *
 * Handles aggregation and elimination for consolidated statements.
 */
interface ConsolidationEngineInterface
{
    /**
     * Consolidate statements from multiple entities.
     *
     * @param string[] $entityIds
     * @param array<string, mixed> $options
     */
    public function consolidateStatements(
        array $entityIds,
        ReportingPeriod $period,
        ConsolidationMethod $method,
        array $options = []
    ): FinancialStatementInterface;

    /**
     * Apply intercompany elimination rules.
     *
     * @param ConsolidationRule[] $rules
     * @param FinancialStatementInterface[] $statements
     * @return array<string, mixed> Elimination entries
     */
    public function applyEliminationRules(array $rules, array $statements): array;

    /**
     * Calculate non-controlling interests.
     *
     * @param array<string, mixed> $ownershipData
     */
    public function calculateNonControllingInterest(
        string $parentEntityId,
        array $subsidiaryEntityIds,
        array $ownershipData
    ): float;

    /**
     * Generate consolidated trial balance.
     *
     * @param string[] $entityIds
     * @return array<string, mixed>
     */
    public function generateConsolidatedTrialBalance(
        array $entityIds,
        ReportingPeriod $period
    ): array;

    /**
     * Validate consolidation requirements.
     *
     * @param string[] $entityIds
     * @return array<string, mixed> Validation results
     */
    public function validateConsolidation(array $entityIds, ReportingPeriod $period): array;
}
