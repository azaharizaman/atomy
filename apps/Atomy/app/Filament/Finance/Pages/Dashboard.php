<?php

declare(strict_types=1);

namespace App\Filament\Finance\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Finance Dashboard
 * 
 * Customized dashboard for Finance panel with domain-specific widgets.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Finance\Widgets\PeriodStatusWidget::class,
            \App\Filament\Finance\Widgets\AccountHierarchyWidget::class,
            \App\Filament\Finance\Widgets\RecentJournalEntriesWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
