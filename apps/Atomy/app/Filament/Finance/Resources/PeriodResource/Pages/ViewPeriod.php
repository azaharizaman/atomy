<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\PeriodResource\Pages;

use App\Filament\Finance\Resources\PeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPeriod extends ViewRecord
{
    protected static string $resource = PeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
