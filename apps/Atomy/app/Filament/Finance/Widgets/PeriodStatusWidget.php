<?php

declare(strict_types=1);

namespace App\Filament\Finance\Widgets;

use App\Models\Period;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Nexus\Period\Enums\PeriodStatus;

/**
 * Period Status Widget
 * 
 * Displays current period information and posting status.
 */
class PeriodStatusWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now = new \DateTimeImmutable();
        $currentPeriod = Period::query()
            ->where('start_date', '<=', $now->format('Y-m-d'))
            ->where('end_date', '>=', $now->format('Y-m-d'))
            ->first();

        if (!$currentPeriod) {
            return [
                Stat::make('Current Period', 'No Period Defined')
                    ->description('Create a period to enable transaction posting')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
            ];
        }

        $openCount = Period::query()
            ->where('status', PeriodStatus::Open)
            ->count();

        $closedCount = Period::query()
            ->where('status', PeriodStatus::Closed)
            ->count();

        $lockedCount = Period::query()
            ->where('status', PeriodStatus::Locked)
            ->count();

        return [
            Stat::make('Current Period', $currentPeriod->name)
                ->description(
                    $currentPeriod->status === PeriodStatus::Open 
                        ? 'Posting Allowed' 
                        : ($currentPeriod->status === PeriodStatus::Closed 
                            ? 'Posting Not Allowed' 
                            : 'Permanently Locked')
                )
                ->descriptionIcon(
                    $currentPeriod->status === PeriodStatus::Open 
                        ? 'heroicon-m-lock-open' 
                        : 'heroicon-m-lock-closed'
                )
                ->color(
                    $currentPeriod->status === PeriodStatus::Open 
                        ? 'success' 
                        : ($currentPeriod->status === PeriodStatus::Closed 
                            ? 'warning' 
                            : 'danger')
                )
                ->chart([0, 10, 5, 15, 10, 20, 15]),

            Stat::make('Open Periods', $openCount)
                ->description('Periods allowing posting')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Closed Periods', $closedCount)
                ->description('Periods preventing posting')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('warning'),

            Stat::make('Locked Periods', $lockedCount)
                ->description('Permanently frozen')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('danger'),
        ];
    }
}
