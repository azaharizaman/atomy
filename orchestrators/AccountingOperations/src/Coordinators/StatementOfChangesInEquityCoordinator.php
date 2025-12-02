<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\Services\FinanceStatementBuilder;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\FinancialStatements\Entities\StatementOfChangesInEquity;
use Nexus\FinancialStatements\Enums\StatementType;

/**
 * Coordinator for statement of changes in equity generation.
 */
final readonly class StatementOfChangesInEquityCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private FinanceStatementBuilder $builder,
    ) {}

    public function getName(): string
    {
        return 'statement_of_changes_in_equity';
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

    public function generate(StatementGenerationRequest $request): StatementOfChangesInEquity
    {
        /** @var StatementOfChangesInEquity $statement */
        $statement = $this->builder->build(
            $request->tenantId,
            $request->periodId,
            StatementType::CHANGES_IN_EQUITY,
            $request->framework,
            $request->comparativePeriodId,
        );

        return $statement;
    }
}
