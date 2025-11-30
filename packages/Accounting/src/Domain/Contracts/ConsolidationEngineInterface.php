<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\ReportingPeriod;
use Nexus\Accounting\Domain\ValueObjects\ConsolidationRule;
use Nexus\Accounting\Domain\Enums\ConsolidationMethod;

/**
 * Consolidation Engine interface.
 *
 * Handles multi-entity consolidation and eliminations.
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
     * Apply elimination rules to statements.
     *
     * @param ConsolidationRule[] $rules
     * @param FinancialStatementInterface[] $statements
     * @return array<string, mixed>
     */
    public function applyEliminationRules(array $rules, array $statements): array;

    /**
     * Calculate non-controlling interest.
     *
     * @param string[] $subsidiaryEntityIds
     * @param array<string, mixed> $ownershipData
     */
    public function calculateNonControllingInterest(
        string $parentEntityId,
        array $subsidiaryEntityIds,
        array $ownershipData
    ): float;

    /**
     * Validate consolidation set.
     *
     * @param string[] $entityIds
     * @return array{valid: bool, issues: string[]}
     */
    public function validateConsolidation(array $entityIds, ReportingPeriod $period): array;

    /**
     * Generate consolidated trial balance.
     *
     * @param string[] $entityIds
     * @return array<string, mixed>
     */
    public function generateConsolidatedTrialBalance(array $entityIds, ReportingPeriod $period): array;
}
