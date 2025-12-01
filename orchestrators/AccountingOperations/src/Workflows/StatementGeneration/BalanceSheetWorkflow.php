<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\StatementGeneration;

use Nexus\AccountingOperations\Contracts\AccountingWorkflowInterface;
use Nexus\AccountingOperations\Coordinators\BalanceSheetCoordinator;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\FinancialStatements\Entities\BalanceSheet;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Workflow for generating Balance Sheet statements
 */
final readonly class BalanceSheetWorkflow implements AccountingWorkflowInterface
{
    public function __construct(
        private BalanceSheetCoordinator $coordinator
    ) {}

    /**
     * Execute the balance sheet generation workflow
     *
     * @param StatementGenerationRequest $request
     * @return array{statement: BalanceSheet, metadata: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(object $request): array
    {
        if (!$request instanceof StatementGenerationRequest) {
            throw new WorkflowException('Invalid request type. Expected StatementGenerationRequest.');
        }

        try {
            $startTime = microtime(true);

            // Generate balance sheet
            $balanceSheet = $this->coordinator->generate(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                asOfDate: $request->asOfDate,
                complianceFramework: $request->complianceFramework,
                compareWithPriorPeriod: $request->compareWithPriorPeriod
            );

            $endTime = microtime(true);

            return [
                'statement' => $balanceSheet,
                'metadata' => [
                    'generated_at' => new \DateTimeImmutable(),
                    'generation_time_ms' => ($endTime - $startTime) * 1000,
                    'period_id' => $request->periodId,
                    'as_of_date' => $request->asOfDate,
                    'compliance_framework' => $request->complianceFramework?->value,
                    'is_balanced' => $balanceSheet->isBalanced(),
                ],
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Balance sheet generation failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Get workflow name
     */
    public function getName(): string
    {
        return 'balance_sheet_generation';
    }

    /**
     * Get workflow description
     */
    public function getDescription(): string
    {
        return 'Generates a Balance Sheet (Statement of Financial Position) for a given period';
    }
}
