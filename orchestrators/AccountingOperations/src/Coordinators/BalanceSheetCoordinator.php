<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\Services\FinanceStatementBuilder;
use Nexus\AccountingOperations\DTOs\StatementGenerationRequest;
use Nexus\FinancialStatements\Entities\BalanceSheet;
use Nexus\FinancialStatements\Enums\StatementType;

/**
 * Coordinator for balance sheet generation.
 */
final readonly class BalanceSheetCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private FinanceStatementBuilder $builder,
    ) {}

    public function getName(): string
    {
        return 'balance_sheet';
    }

    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        return true; // Check with builder's data provider
    }

    /**
     * @return array<string>
     */
    public function getSupportedOperations(): array
    {
        return ['generate', 'validate', 'export'];
    }

    public function generate(StatementGenerationRequest $request): BalanceSheet
    {
        /** @var BalanceSheet $statement */
        $statement = $this->builder->build(
            $request->tenantId,
            $request->periodId,
            StatementType::BALANCE_SHEET,
            $request->framework,
            $request->comparativePeriodId,
        );

        return $statement;
    }
}
