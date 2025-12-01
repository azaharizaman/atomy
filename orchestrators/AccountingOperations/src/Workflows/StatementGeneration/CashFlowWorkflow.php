<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Workflows\StatementGeneration;

use Nexus\AccountingOperations\Contracts\AccountingWorkflowInterface;
use Nexus\AccountingOperations\Coordinators\CashFlowCoordinator;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\FinancialStatements\Entities\CashFlowStatement;
use Nexus\FinancialStatements\Enums\CashFlowMethod;
use Nexus\AccountingOperations\Exceptions\WorkflowException;

/**
 * Workflow for generating Cash Flow Statement
 */
final readonly class CashFlowWorkflow implements AccountingWorkflowInterface
{
    public function __construct(
        private CashFlowCoordinator $coordinator
    ) {}

    /**
     * Execute the cash flow statement generation workflow
     *
     * @param StatementGenerationRequest $request
     * @return array{statement: CashFlowStatement, metadata: array<string, mixed>}
     * @throws WorkflowException
     */
    public function execute(object $request): array
    {
        if (!$request instanceof StatementGenerationRequest) {
            throw new WorkflowException('Invalid request type. Expected StatementGenerationRequest.');
        }

        try {
            $startTime = microtime(true);

            // Determine cash flow method (default to indirect)
            $method = $request->cashFlowMethod ?? CashFlowMethod::INDIRECT;

            // Generate cash flow statement
            $cashFlowStatement = $this->coordinator->generate(
                tenantId: $request->tenantId,
                periodId: $request->periodId,
                fromDate: $request->fromDate,
                toDate: $request->toDate,
                method: $method,
                complianceFramework: $request->complianceFramework
            );

            $endTime = microtime(true);

            return [
                'statement' => $cashFlowStatement,
                'metadata' => [
                    'generated_at' => new \DateTimeImmutable(),
                    'generation_time_ms' => ($endTime - $startTime) * 1000,
                    'period_id' => $request->periodId,
                    'from_date' => $request->fromDate,
                    'to_date' => $request->toDate,
                    'method' => $method->value,
                    'compliance_framework' => $request->complianceFramework?->value,
                    'net_change_in_cash' => $cashFlowStatement->getNetChangeInCash(),
                    'operating_activities' => $cashFlowStatement->getOperatingActivitiesTotal(),
                    'investing_activities' => $cashFlowStatement->getInvestingActivitiesTotal(),
                    'financing_activities' => $cashFlowStatement->getFinancingActivitiesTotal(),
                ],
            ];
        } catch (\Throwable $e) {
            if ($e instanceof WorkflowException) {
                throw $e;
            }
            throw new WorkflowException(
                "Cash flow statement generation failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Get workflow name
     */
    public function getName(): string
    {
        return 'cash_flow_generation';
    }

    /**
     * Get workflow description
     */
    public function getDescription(): string
    {
        return 'Generates a Cash Flow Statement for a given period';
    }
}
