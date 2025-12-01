<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Layouts;

use Nexus\FinancialStatements\Contracts\StatementTemplateInterface;

/**
 * GAAP-compliant income statement layout.
 */
final readonly class GaapIncomeStatementLayout implements StatementTemplateInterface
{
    public function getId(): string
    {
        return 'gaap_income_statement';
    }

    public function getName(): string
    {
        return 'GAAP Income Statement';
    }

    public function getSectionDefinitions(): array
    {
        return [
            'revenues' => [
                'name' => 'Revenues',
                'order' => 1,
                'accounts' => ['4000-4499'],
            ],
            'cost_of_goods_sold' => [
                'name' => 'Cost of Goods Sold',
                'order' => 2,
                'accounts' => ['5000-5499'],
            ],
            'operating_expenses' => [
                'name' => 'Operating Expenses',
                'order' => 3,
                'accounts' => ['6000-6999'],
            ],
            'other_income' => [
                'name' => 'Other Income',
                'order' => 4,
                'accounts' => ['7000-7499'],
            ],
            'other_expenses' => [
                'name' => 'Other Expenses',
                'order' => 5,
                'accounts' => ['7500-7999'],
            ],
            'income_taxes' => [
                'name' => 'Income Taxes',
                'order' => 6,
                'accounts' => ['8000-8499'],
            ],
        ];
    }

    public function getComplianceFramework(): string
    {
        return 'gaap';
    }
}
