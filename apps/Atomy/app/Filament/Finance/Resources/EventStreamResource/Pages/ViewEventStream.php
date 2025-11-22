<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\EventStreamResource\Pages;

use App\Filament\Finance\Resources\EventStreamResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEventStream extends ViewRecord
{
    protected static string $resource = EventStreamResource::class;
}
