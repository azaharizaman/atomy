<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Listeners;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Listener that logs to audit trail when a financial statement is generated
 */
final readonly class AuditLogOnStatementGenerated
{
    public function __construct(
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Handle the statement generated event
     *
     * @param object $event The statement generated event (framework-specific)
     * @return void
     */
    public function handle(object $event): void
    {
        $tenantId = $event->tenantId ?? '';
        $periodId = $event->periodId ?? '';
        $statementType = $event->statementType ?? 'unknown';
        $statementId = $event->statementId ?? '';
        $generatedBy = $event->generatedBy ?? 'system';

        $this->logger->info('Logging financial statement generation to audit trail', [
            'tenant_id' => $tenantId,
            'period_id' => $periodId,
            'statement_type' => $statementType,
            'statement_id' => $statementId,
        ]);

        try {
            $this->auditLogger->log(
                entityId: $statementId,
                action: 'statement_generated',
                description: sprintf(
                    '%s generated for period %s',
                    $this->formatStatementType($statementType),
                    $periodId
                ),
                metadata: [
                    'tenant_id' => $tenantId,
                    'period_id' => $periodId,
                    'statement_type' => $statementType,
                    'generated_by' => $generatedBy,
                    'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    'compliance_framework' => $event->complianceFramework ?? null,
                    'is_balanced' => $event->isBalanced ?? null,
                    'generation_time_ms' => $event->generationTimeMs ?? null,
                ]
            );

            $this->logger->debug('Audit log entry created for statement generation', [
                'statement_id' => $statementId,
            ]);
        } catch (\Throwable $e) {
            // Audit logging failure should not break the main flow
            $this->logger->error('Failed to create audit log for statement generation', [
                'statement_id' => $statementId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format statement type for display
     */
    private function formatStatementType(string $type): string
    {
        return match ($type) {
            'balance_sheet' => 'Balance Sheet',
            'income_statement' => 'Income Statement',
            'cash_flow' => 'Cash Flow Statement',
            'statement_of_changes_in_equity' => 'Statement of Changes in Equity',
            'trial_balance' => 'Trial Balance',
            'notes' => 'Notes to Financial Statements',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}
