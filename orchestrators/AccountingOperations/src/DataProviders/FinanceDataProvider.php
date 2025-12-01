<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DataProviders;

use Nexus\FinancialStatements\Contracts\StatementDataProviderInterface;
use Nexus\FinancialStatements\ValueObjects\AccountBalance;

/**
 * Data provider that integrates with Nexus\Finance for statement data.
 */
final readonly class FinanceDataProvider implements StatementDataProviderInterface
{
    public function __construct(
        // Injected dependencies from consuming application
    ) {}

    /**
     * @return array<AccountBalance>
     */
    public function getAccountBalances(string $tenantId, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<AccountBalance>
     */
    public function getComparativeBalances(string $tenantId, string $periodId, string $comparativePeriodId): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPeriodMetadata(string $tenantId, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, float>
     */
    public function getCashFlowData(string $tenantId, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEquityMovements(string $tenantId, string $periodId): array
    {
        // Implementation provided by consuming application
        return [];
    }
}
