<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Layouts;

use Nexus\FinancialStatements\Contracts\StatementTemplateInterface;

/**
 * IFRS-compliant income statement layout.
 */
final readonly class IfrsIncomeStatementLayout implements StatementTemplateInterface
{
    public function getId(): string
    {
        return 'ifrs_income_statement';
    }

    public function getName(): string
    {
        return 'IFRS Statement of Profit or Loss';
    }

    public function getSectionDefinitions(): array
    {
        return [
            'revenue' => [
                'name' => 'Revenue',
                'order' => 1,
                'accounts' => ['4000-4499'],
            ],
            'cost_of_sales' => [
                'name' => 'Cost of Sales',
                'order' => 2,
                'accounts' => ['5000-5499'],
            ],
            'distribution_costs' => [
                'name' => 'Distribution Costs',
                'order' => 3,
                'accounts' => ['6000-6299'],
            ],
            'administrative_expenses' => [
                'name' => 'Administrative Expenses',
                'order' => 4,
                'accounts' => ['6300-6699'],
            ],
            'other_operating_income' => [
                'name' => 'Other Operating Income',
                'order' => 5,
                'accounts' => ['7000-7499'],
            ],
            'finance_costs' => [
                'name' => 'Finance Costs',
                'order' => 6,
                'accounts' => ['7500-7999'],
            ],
            'income_tax_expense' => [
                'name' => 'Income Tax Expense',
                'order' => 7,
                'accounts' => ['8000-8499'],
            ],
        ];
    }

    public function getComplianceFramework(): string
    {
        return 'ifrs';
    }
}
