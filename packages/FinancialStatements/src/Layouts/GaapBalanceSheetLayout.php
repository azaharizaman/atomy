<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Layouts;

use Nexus\FinancialStatements\Contracts\StatementTemplateInterface;

/**
 * GAAP-compliant balance sheet layout.
 */
final readonly class GaapBalanceSheetLayout implements StatementTemplateInterface
{
    public function getId(): string
    {
        return 'gaap_balance_sheet';
    }

    public function getName(): string
    {
        return 'GAAP Balance Sheet';
    }

    public function getSectionDefinitions(): array
    {
        return [
            'current_assets' => [
                'name' => 'Current Assets',
                'order' => 1,
                'accounts' => ['1000-1499'],
            ],
            'non_current_assets' => [
                'name' => 'Non-Current Assets',
                'order' => 2,
                'accounts' => ['1500-1999'],
            ],
            'current_liabilities' => [
                'name' => 'Current Liabilities',
                'order' => 3,
                'accounts' => ['2000-2499'],
            ],
            'non_current_liabilities' => [
                'name' => 'Non-Current Liabilities',
                'order' => 4,
                'accounts' => ['2500-2999'],
            ],
            'equity' => [
                'name' => 'Stockholders\' Equity',
                'order' => 5,
                'accounts' => ['3000-3999'],
            ],
        ];
    }

    public function getComplianceFramework(): string
    {
        return 'gaap';
    }
}
