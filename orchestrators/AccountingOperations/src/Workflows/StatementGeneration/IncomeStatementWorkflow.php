<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\StatementGeneration;

use Nexus\AccountingOperations\Contracts\AccountingWorkflowInterface;
use Nexus\AccountingOperations\Coordinators\IncomeStatementCoordinator;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\FinancialStatements\Entities\IncomeStatement;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Workflow for generating Income Statement (Profit & Loss)
 */
final readonly class IncomeStatementWorkflow implements AccountingWorkflowInterface
{
    public function __construct(
        private IncomeStatementCoordinator $coordinator
    ) {}

    /**
     * Execute the income statement generation workflow
     *
     * @param StatementGenerationRequest $request
     * @return array{statement: IncomeStatement, metadata: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(object $request): array
    {
        if (!$request instanceof StatementGenerationRequest) {
            throw new WorkflowException('Invalid request type. Expected StatementGenerationRequest.');
        }

        try {
            $startTime = microtime(true);

            // Generate income statement
            $incomeStatement = $this->coordinator->generate(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                fromDate: $request->fromDate,
                toDate: $request->toDate,
                complianceFramework: $request->complianceFramework,
                compareWithPriorPeriod: $request->compareWithPriorPeriod
            );

            $endTime = microtime(true);

            return [
                'statement' => $incomeStatement,
                'metadata' => [
                    'generated_at' => new \DateTimeImmutable(),
                    'generation_time_ms' => ($endTime - $startTime) * 1000,
                    'period_id' => $request->periodId,
                    'from_date' => $request->fromDate,
                    'to_date' => $request->toDate,
                    'compliance_framework' => $request->complianceFramework?->value,
                    'net_income' => $incomeStatement->getNetIncome(),
                ],
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Income statement generation failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Get workflow name
     */
    public function getName(): string
    {
        return 'income_statement_generation';
    }

    /**
     * Get workflow description
     */
    public function getDescription(): string
    {
        return 'Generates an Income Statement (Profit & Loss) for a given period';
    }
}
