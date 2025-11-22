<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\AccountResource\Pages;

use App\Filament\Finance\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
