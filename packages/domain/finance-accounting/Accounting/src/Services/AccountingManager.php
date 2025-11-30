<?php

declare(strict_types=1);

namespace Nexus\Accounting\Services;

use Nexus\Accounting\Contracts\{
    StatementBuilderInterface,
    PeriodCloseServiceInterface,
    ConsolidationEngineInterface,
    StatementRepositoryInterface,
    ReportFormatterInterface,
    BalanceSheetInterface,
    IncomeStatementInterface,
    CashFlowStatementInterface,
    FinancialStatementInterface
};
use Nexus\Accounting\Core\ValueObjects\{
    ReportingPeriod,
    StatementFormat,
    ComplianceStandard,
    SegmentIdentifier,
    VarianceAnalysis
};
use Nexus\Accounting\Core\Enums\{StatementType, PeriodCloseStatus, CashFlowMethod, ConsolidationMethod};
use Nexus\Accounting\Core\Engine\VarianceCalculator;
use Nexus\Accounting\Exceptions\{StatementGenerationException, PeriodNotClosedException};
use Nexus\Setting\Contracts\SettingsManagerInterface;
use Nexus\AuditLogger\Contracts\AuditLoggerInterface;
use Nexus\Period\Contracts\PeriodManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Accounting Manager - Main Service Layer.
 *
 * Orchestrates financial statement generation, period close,
 * consolidation, and variance analysis operations.
 */
final readonly class AccountingManager
{
    public function __construct(
        private StatementBuilderInterface $statementBuilder,
        private PeriodCloseServiceInterface $periodCloseService,
        private ConsolidationEngineInterface $consolidationEngine,
        private StatementRepositoryInterface $statementRepository,
        private ReportFormatterInterface $reportFormatter,
        private VarianceCalculator $varianceCalculator,
        private PeriodManagerInterface $periodManager,
        private SettingsManagerInterface $settings,
        private AuditLoggerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    // ==================== Statement Generation ====================

    /**
     * Generate a Balance Sheet for the specified period.
     *
     * @param array<string, mixed>|null $options
     */
    public function generateBalanceSheet(
        ReportingPeriod $period,
        ?array $options = null
    ): BalanceSheetInterface {
        $options = $options ?? [];
        $entityId = $options['entity_id'] ?? $this->getDefaultEntityId();

        $this->logger->info('Generating balance sheet via AccountingManager', [
            'entity_id' => $entityId,
            'period' => $period->getLabel(),
        ]);

        try {
            $balanceSheet = $this->statementBuilder->buildBalanceSheet(
                $entityId,
                $period,
                $options
            );

            // Save if requested
            if ($options['save'] ?? false) {
                $this->statementRepository->save($balanceSheet);
            }

            // Audit log
            $this->auditLogger->log(
                $entityId,
                'balance_sheet_generated',
                "Balance Sheet generated for {$period->getLabel()}",
                ['period' => $period->toArray()]
            );

            return $balanceSheet;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate balance sheet', [
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
            throw StatementGenerationException::forType('Balance Sheet', $e->getMessage());
        }
    }

    /**
     * Generate an Income Statement for the specified period.
     *
     * @param array<string, mixed>|null $options
     */
    public function generateIncomeStatement(
        ReportingPeriod $period,
        ?array $options = null
    ): IncomeStatementInterface {
        $options = $options ?? [];
        $entityId = $options['entity_id'] ?? $this->getDefaultEntityId();

        $this->logger->info('Generating income statement via AccountingManager', [
            'entity_id' => $entityId,
            'period' => $period->getLabel(),
        ]);

        try {
            $incomeStatement = $this->statementBuilder->buildIncomeStatement(
                $entityId,
                $period,
                $options
            );

            // Save if requested
            if ($options['save'] ?? false) {
                $this->statementRepository->save($incomeStatement);
            }

            // Audit log
            $this->auditLogger->log(
                $entityId,
                'income_statement_generated',
                "Income Statement generated for {$period->getLabel()}",
                ['period' => $period->toArray()]
            );

            return $incomeStatement;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate income statement', [
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
            throw StatementGenerationException::forType('Income Statement', $e->getMessage());
        }
    }

    /**
     * Generate a Cash Flow Statement for the specified period.
     *
     * @param array<string, mixed>|null $options
     */
    public function generateCashFlowStatement(
        ReportingPeriod $period,
        CashFlowMethod $method,
        ?array $options = null
    ): CashFlowStatementInterface {
        $options = $options ?? [];
        $entityId = $options['entity_id'] ?? $this->getDefaultEntityId();

        $this->logger->info('Generating cash flow statement via AccountingManager', [
            'entity_id' => $entityId,
            'period' => $period->getLabel(),
            'method' => $method->value,
        ]);

        try {
            $cashFlowStatement = $this->statementBuilder->buildCashFlowStatement(
                $entityId,
                $period,
                $method,
                $options
            );

            // Save if requested
            if ($options['save'] ?? false) {
                $this->statementRepository->save($cashFlowStatement);
            }

            // Audit log
            $this->auditLogger->log(
                $entityId,
                'cash_flow_statement_generated',
                "Cash Flow Statement ({$method->value}) generated for {$period->getLabel()}",
                ['period' => $period->toArray(), 'method' => $method->value]
            );

            return $cashFlowStatement;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate cash flow statement', [
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
            throw StatementGenerationException::forType('Cash Flow Statement', $e->getMessage());
        }
    }

    /**
     * Generate a Trial Balance for the specified period.
     *
     * @param array<string, mixed>|null $options
     * @return array<string, mixed>
     */
    public function generateTrialBalance(
        ReportingPeriod $period,
        ?array $options = null
    ): array {
        $options = $options ?? [];
        $entityId = $options['entity_id'] ?? $this->getDefaultEntityId();

        $this->logger->info('Generating trial balance via AccountingManager', [
            'entity_id' => $entityId,
            'period' => $period->getLabel(),
        ]);

        // Trial balance is generated from the consolidation engine's helper method
        $trialBalance = $this->consolidationEngine->generateConsolidatedTrialBalance(
            [$entityId],
            $period
        );

        // Audit log
        $this->auditLogger->log(
            $entityId,
            'trial_balance_generated',
            "Trial Balance generated for {$period->getLabel()}",
            ['period' => $period->toArray(), 'accounts' => count($trialBalance)]
        );

        return [
            'entity_id' => $entityId,
            'period' => $period->toArray(),
            'accounts' => $trialBalance,
            'total_debits' => array_sum(array_column($trialBalance, 'debit')),
            'total_credits' => array_sum(array_column($trialBalance, 'credit')),
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    // ==================== Period Close ====================

    /**
     * Close a month-end period.
     *
     * @param array<string, mixed> $options
     */
    public function closeMonth(string $periodId, array $options = []): void
    {
        $this->logger->info('Closing month via AccountingManager', ['period_id' => $periodId]);

        try {
            $this->periodCloseService->closeMonth($periodId, $options);

            $this->logger->info('Month closed successfully', ['period_id' => $periodId]);

        } catch (PeriodNotClosedException $e) {
            $this->logger->error('Failed to close month', [
                'period_id' => $periodId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Close a year-end period.
     *
     * @param array<string, mixed> $options
     */
    public function closeYear(string $fiscalYearId, array $options = []): void
    {
        $this->logger->info('Closing year via AccountingManager', ['fiscal_year_id' => $fiscalYearId]);

        try {
            $this->periodCloseService->closeYear($fiscalYearId, $options);

            $this->logger->info('Year closed successfully', ['fiscal_year_id' => $fiscalYearId]);

        } catch (PeriodNotClosedException $e) {
            $this->logger->error('Failed to close year', [
                'fiscal_year_id' => $fiscalYearId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reopen a previously closed period.
     */
    public function reopenPeriod(string $periodId, string $reason): void
    {
        $this->logger->warning('Reopening period via AccountingManager', [
            'period_id' => $periodId,
            'reason' => $reason,
        ]);

        $this->periodCloseService->reopenPeriod($periodId, $reason);
    }

    /**
     * Get the close status of a period.
     */
    public function getPeriodCloseStatus(string $periodId): PeriodCloseStatus
    {
        return $this->periodCloseService->getPeriodCloseStatus($periodId);
    }

    // ==================== Consolidation ====================

    /**
     * Consolidate statements from multiple entities.
     *
     * @param string[] $entityIds
     * @param array<string, mixed> $options
     */
    public function consolidateStatements(
        array $entityIds,
        ReportingPeriod $period,
        ?array $options = null
    ): FinancialStatementInterface {
        $options = $options ?? [];
        $method = $options['method'] ?? ConsolidationMethod::FULL;

        $this->logger->info('Consolidating statements via AccountingManager', [
            'entities' => count($entityIds),
            'period' => $period->getLabel(),
            'method' => $method->value,
        ]);

        try {
            $consolidated = $this->consolidationEngine->consolidateStatements(
                $entityIds,
                $period,
                $method,
                $options
            );

            // Save if requested
            if ($options['save'] ?? false) {
                $this->statementRepository->save($consolidated);
            }

            // Audit log
            $this->auditLogger->log(
                'CONSOLIDATED',
                'statements_consolidated',
                "Consolidated {$period->getLabel()} for " . count($entityIds) . " entities",
                [
                    'entities' => $entityIds,
                    'period' => $period->toArray(),
                    'method' => $method->value,
                ]
            );

            return $consolidated;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to consolidate statements', [
                'entities' => $entityIds,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Apply elimination rules to statements.
     *
     * @param array<int, \Nexus\Accounting\Core\ValueObjects\ConsolidationRule> $rules
     * @param FinancialStatementInterface[] $statements
     * @return array<string, mixed>
     */
    public function applyEliminationRules(array $rules, array $statements): array
    {
        $this->logger->info('Applying elimination rules via AccountingManager', [
            'rules' => count($rules),
            'statements' => count($statements),
        ]);

        return $this->consolidationEngine->applyEliminationRules($rules, $statements);
    }

    // ==================== Compliance & Export ====================

    /**
     * Apply compliance template to a statement.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function applyComplianceTemplate(
        FinancialStatementInterface $statement,
        ComplianceStandard $standard,
        array $options = []
    ): array {
        $this->logger->info('Applying compliance template', [
            'statement_type' => $statement->getType()->value,
            'standard' => $standard->toString(),
        ]);

        // This would use a ComplianceTemplateInterface implementation
        // For now, return the statement with compliance metadata
        return array_merge($statement->toArray(), [
            'compliance_standard' => $standard->toArray(),
            'compliance_applied_at' => new \DateTimeImmutable(),
        ]);
    }

    /**
     * Export a statement to the specified format.
     */
    public function exportStatement(
        FinancialStatementInterface $statement,
        StatementFormat $format,
        ?array $options = null
    ): string {
        $options = $options ?? [];

        $this->logger->info('Exporting statement', [
            'statement_type' => $statement->getType()->value,
            'format' => $format->value,
        ]);

        try {
            $exported = $this->reportFormatter->format($statement, $format, $options);

            // Audit log
            $this->auditLogger->log(
                $statement->getEntityId(),
                'statement_exported',
                "Statement exported to {$format->value}",
                [
                    'type' => $statement->getType()->value,
                    'format' => $format->value,
                ]
            );

            return $exported;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to export statement', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ==================== Variance & Analysis ====================

    /**
     * Calculate budget variance for an account.
     */
    public function calculateBudgetVariance(
        string $accountId,
        ReportingPeriod $period
    ): VarianceAnalysis {
        $this->logger->debug('Calculating budget variance via AccountingManager', [
            'account_id' => $accountId,
            'period' => $period->getLabel(),
        ]);

        return $this->varianceCalculator->calculateAccountVariance($accountId, $period);
    }

    /**
     * Get comparative periods for analysis.
     *
     * @return ReportingPeriod[]
     */
    public function getComparativePeriods(
        ReportingPeriod $base,
        int $numberOfPeriods
    ): array {
        $this->logger->debug('Getting comparative periods', [
            'base' => $base->getLabel(),
            'count' => $numberOfPeriods,
        ]);

        $periods = [$base];
        $currentDate = $base->getStartDate();

        for ($i = 1; $i < $numberOfPeriods; $i++) {
            // Go back by period length
            $previousDate = $currentDate->modify('-' . $base->getDays() . ' days');
            
            if ($base->isMonth()) {
                $periods[] = ReportingPeriod::forMonth(
                    (int) $previousDate->format('Y'),
                    (int) $previousDate->format('m')
                );
            } elseif ($base->isYear()) {
                $periods[] = ReportingPeriod::forYear((int) $previousDate->format('Y'));
            } else {
                // Custom period
                $endDate = $currentDate->modify('-1 day');
                $periods[] = ReportingPeriod::custom(
                    $previousDate,
                    $endDate,
                    "Period " . ($i + 1)
                );
            }

            $currentDate = $previousDate;
        }

        return array_reverse($periods);
    }

    /**
     * Generate a segment report.
     *
     * @param array<string, mixed>|null $options
     * @return array<string, mixed>
     */
    public function generateSegmentReport(
        SegmentIdentifier $segment,
        ReportingPeriod $period,
        ?array $options = null
    ): array {
        $options = $options ?? [];

        $this->logger->info('Generating segment report', [
            'segment' => $segment->getLabel(),
            'period' => $period->getLabel(),
        ]);

        // This would filter accounts by segment dimensions
        // For now, return a basic structure
        return [
            'segment' => $segment->toArray(),
            'period' => $period->toArray(),
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    // ==================== Helper Methods ====================

    /**
     * Get the default entity ID from settings.
     */
    private function getDefaultEntityId(): string
    {
        return $this->settings->getString('accounting.default_entity_id', 'default');
    }
}
