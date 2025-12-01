<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\Services\FinanceStatementBuilder;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\FinancialStatements\Entities\CashFlowStatement;
use Nexus\FinancialStatements\Enums\StatementType;

/**
 * Coordinator for cash flow statement generation.
 */
final readonly class CashFlowCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private FinanceStatementBuilder $builder,
    ) {}

    public function getName(): string
    {
        return 'cash_flow';
    }

    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        return true;
    }

    /**
     * @return array<string>
     */
    public function getSupportedOperations(): array
    {
        return ['generate', 'validate', 'export'];
    }

    public function generate(StatementGenerationRequest $request): CashFlowStatement
    {
        /** @var CashFlowStatement $statement */
        $statement = $this->builder->build(
            $request->tenantId,
            $request->periodId,
            StatementType::CASH_FLOW,
            $request->framework,
            $request->comparativePeriodId,
        );

        return $statement;
    }
}
