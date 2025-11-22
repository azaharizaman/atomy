<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditLogs\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit/delete actions for read-only resource
        ];
    }
}
