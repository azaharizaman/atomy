<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\JournalEntryResource\Pages;

use App\Filament\Finance\Resources\JournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->hidden(fn () => $this->record->status !== 'draft'),
        ];
    }
}
