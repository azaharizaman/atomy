<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\AuditLogResource\Pages;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?int $navigationSort = 99;

    // Read-only resource - no create/edit
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Empty form schema since resource is read-only
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Log Name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => class_basename($state ?? 'N/A')),
                
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Causer')
                    ->searchable()
                    ->sortable()
                    ->default('System'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Log Name')
                    ->options([
                        'default' => 'Default',
                        'system' => 'System',
                        'user' => 'User',
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('To Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for audit logs
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\Section::make('Audit Log Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('log_name')
                            ->label('Log Name')
                            ->badge()
                            ->color('gray'),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description'),
                        
                        Infolists\Components\TextEntry::make('subject_type')
                            ->label('Subject Type')
                            ->formatStateUsing(fn ($state) => $state ?? 'N/A'),
                        
                        Infolists\Components\TextEntry::make('subject_id')
                            ->label('Subject ID'),
                        
                        Infolists\Components\TextEntry::make('causer.name')
                            ->label('Causer')
                            ->default('System'),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Date/Time')
                            ->dateTime(),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Properties')
                    ->schema([
                        Infolists\Components\TextEntry::make('properties')
                            ->label('Event Data')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
