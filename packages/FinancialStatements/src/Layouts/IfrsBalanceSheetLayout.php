<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Layouts;

use Nexus\FinancialStatements\Contracts\StatementTemplateInterface;

/**
 * IFRS-compliant balance sheet layout.
 */
final readonly class IfrsBalanceSheetLayout implements StatementTemplateInterface
{
    public function getId(): string
    {
        return 'ifrs_balance_sheet';
    }

    public function getName(): string
    {
        return 'IFRS Statement of Financial Position';
    }

    public function getSectionDefinitions(): array
    {
        return [
            'non_current_assets' => [
                'name' => 'Non-Current Assets',
                'order' => 1,
                'accounts' => ['1500-1999'],
            ],
            'current_assets' => [
                'name' => 'Current Assets',
                'order' => 2,
                'accounts' => ['1000-1499'],
            ],
            'equity' => [
                'name' => 'Equity',
                'order' => 3,
                'accounts' => ['3000-3999'],
            ],
            'non_current_liabilities' => [
                'name' => 'Non-Current Liabilities',
                'order' => 4,
                'accounts' => ['2500-2999'],
            ],
            'current_liabilities' => [
                'name' => 'Current Liabilities',
                'order' => 5,
                'accounts' => ['2000-2499'],
            ],
        ];
    }

    public function getComplianceFramework(): string
    {
        return 'ifrs';
    }
}
