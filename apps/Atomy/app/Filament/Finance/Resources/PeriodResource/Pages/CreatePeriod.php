<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\PeriodResource\Pages;

use App\Filament\Finance\Resources\PeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePeriod extends CreateRecord
{
    protected static string $resource = PeriodResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
