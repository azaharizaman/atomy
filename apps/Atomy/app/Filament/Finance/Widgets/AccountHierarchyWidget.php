<?php

declare(strict_types=1);

namespace App\Filament\Finance\Widgets;

use Filament\Widgets\Widget;
use Nexus\Finance\Contracts\FinanceManagerInterface;

/**
 * Account Hierarchy Widget
 * 
 * Displays hierarchical chart of accounts tree structure.
 * Uses cached getAccountTree() method from FinanceManager.
 */
class AccountHierarchyWidget extends Widget
{
    protected static string $view = 'filament.finance.widgets.account-hierarchy';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    /**
     * Get account tree data from Finance Manager (cached).
     */
    public function getAccountTree(): array
    {
        $financeManager = app(FinanceManagerInterface::class);
        
        // Filters: only active accounts
        return $financeManager->getAccountTree(['is_active' => true]);
    }

    /**
     * Get account type badge color.
     */
    public function getTypeBadgeColor(string $type): string
    {
        return match ($type) {
            'Asset' => 'success',
            'Liability' => 'danger',
            'Equity' => 'info',
            'Revenue' => 'warning',
            'Expense' => 'gray',
            default => 'gray',
        };
    }
}
