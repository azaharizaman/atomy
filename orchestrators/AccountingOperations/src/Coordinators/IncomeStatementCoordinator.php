<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\Services\FinanceStatementBuilder;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\FinancialStatements\Entities\IncomeStatement;
use Nexus\FinancialStatements\Enums\StatementType;

/**
 * Coordinator for income statement generation.
 */
final readonly class IncomeStatementCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private FinanceStatementBuilder $builder,
    ) {}

    public function getName(): string
    {
        return 'income_statement';
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

    public function generate(StatementGenerationRequest $request): IncomeStatement
    {
        /** @var IncomeStatement $statement */
        $statement = $this->builder->build(
            $request->tenantId,
            $request->periodId,
            StatementType::INCOME_STATEMENT,
            $request->framework,
            $request->comparativePeriodId,
        );

        return $statement;
    }
}
