<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Listeners;

use Nexus\AccountingOperations\Coordinators\BalanceSheetCoordinator;
use Nexus\AccountingOperations\Coordinators\IncomeStatementCoordinator;
use Nexus\AccountingOperations\Coordinators\CashFlowCoordinator;
use Nexus\AccountingOperations\Coordinators\StatementOfChangesInEquityCoordinator;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener that generates all financial statements when a period is closed
 */
final readonly class GenerateStatementsOnPeriodClose
{
    public function __construct(
        private BalanceSheetCoordinator $balanceSheetCoordinator,
        private IncomeStatementCoordinator $incomeStatementCoordinator,
        private CashFlowCoordinator $cashFlowCoordinator,
        private StatementOfChangesInEquityCoordinator $equityCoordinator,
        private ?AuditLogManagerInterface $auditLogger = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Handle the period close event
     *
     * @param object $event The period close event (framework-specific)
     * @return void
     */
    public function handle(object $event): void
    {
        $tenantId = $event->tenantId ?? '';
        $periodId = $event->periodId ?? '';
        $asOfDate = $event->asOfDate ?? new \DateTimeImmutable();

        $this->logger->info('Generating financial statements for closed period', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
        ]);

        $generatedStatements = [];

        try {
            // Generate Balance Sheet
            $balanceSheet = $this->balanceSheetCoordinator->generate(
                tenantId: $tenantId,
                periodId: $periodId,
                asOfDate: $asOfDate
            );
            $generatedStatements[] = 'balance_sheet';

            // Generate Income Statement
            $incomeStatement = $this->incomeStatementCoordinator->generate(
                tenantId: $tenantId,
                periodId: $periodId,
                fromDate: $event->periodStartDate ?? $asOfDate->modify('-1 month'),
                toDate: $asOfDate
            );
            $generatedStatements[] = 'income_statement';

            // Generate Cash Flow Statement
            $cashFlow = $this->cashFlowCoordinator->generate(
                tenantId: $tenantId,
                periodId: $periodId,
                fromDate: $event->periodStartDate ?? $asOfDate->modify('-1 month'),
                toDate: $asOfDate
            );
            $generatedStatements[] = 'cash_flow';

            // Generate Statement of Changes in Equity
            $equityStatement = $this->equityCoordinator->generate(
                tenantId: $tenantId,
                periodId: $periodId,
                fromDate: $event->periodStartDate ?? $asOfDate->modify('-1 month'),
                toDate: $asOfDate
            );
            $generatedStatements[] = 'statement_of_changes_in_equity';

            $this->logger->info('Financial statements generated successfully', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'statements' => $generatedStatements,
            ]);

            // Log to audit trail
            $this->auditLogger?->log(
                entityId: $periodId,
                action: 'statements_generated',
                description: sprintf(
                    'Generated %d financial statements for period %s',
                    count($generatedStatements),
                    $periodId
                ),
                metadata: [
                    'tenant_id' => $tenantId,
                    'statements' => $generatedStatements,
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate financial statements', [
                'tenant_id' => $tenantId,
                'period_id' => $periodId,
                'generated_before_failure' => $generatedStatements,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
