<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\StatementGeneration;

use Nexus\AccountingOperations\Contracts\AccountingWorkflowInterface;
use Nexus\AccountingOperations\Coordinators\StatementOfChangesInEquityCoordinator;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\FinancialStatements\Entities\StatementOfChangesInEquity;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Workflow for generating Statement of Changes in Equity
 */
final readonly class StatementOfChangesWorkflow implements AccountingWorkflowInterface
{
    public function __construct(
        private StatementOfChangesInEquityCoordinator $coordinator
    ) {}

    /**
     * Execute the statement of changes in equity generation workflow
     *
     * @param StatementGenerationRequest $request
     * @return array{statement: StatementOfChangesInEquity, metadata: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(object $request): array
    {
        if (!$request instanceof StatementGenerationRequest) {
            throw new WorkflowException('Invalid request type. Expected StatementGenerationRequest.');
        }

        try {
            $startTime = microtime(true);

            // Generate statement of changes in equity
            $statement = $this->coordinator->generate(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                fromDate: $request->fromDate,
                toDate: $request->toDate,
                complianceFramework: $request->complianceFramework
            );

            $endTime = microtime(true);

            return [
                'statement' => $statement,
                'metadata' => [
                    'generated_at' => new \DateTimeImmutable(),
                    'generation_time_ms' => ($endTime - $startTime) * 1000,
                    'period_id' => $request->periodId,
                    'from_date' => $request->fromDate,
                    'to_date' => $request->toDate,
                    'compliance_framework' => $request->complianceFramework?->value,
                    'opening_equity' => $statement->getOpeningTotalEquity(),
                    'closing_equity' => $statement->getClosingTotalEquity(),
                    'net_change' => $statement->getNetChangeInEquity(),
                ],
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Statement of changes in equity generation failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Get workflow name
     */
    public function getName(): string
    {
        return 'statement_of_changes_generation';
    }

    /**
     * Get workflow description
     */
    public function getDescription(): string
    {
        return 'Generates a Statement of Changes in Equity for a given period';
    }
}
