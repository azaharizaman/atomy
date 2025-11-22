<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditLogs\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for read-only resource
        ];
    }
}
