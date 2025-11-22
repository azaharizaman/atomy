<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\PeriodResource\Pages;
use App\Models\Period;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Enums\PeriodStatus;

/**
 * Period Resource
 * 
 * Manage fiscal periods for transaction posting and financial reporting.
 */
class PeriodResource extends Resource
{
    protected static ?string $model = Period::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Period';

    protected static ?string $pluralModelLabel = 'Periods';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Period Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                PeriodType::Monthly->value => 'Monthly',
                                PeriodType::Quarterly->value => 'Quarterly',
                                PeriodType::Yearly->value => 'Yearly',
                            ])
                            ->default(PeriodType::Monthly->value)
                            ->label('Period Type')
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                // Auto-adjust end date based on type
                                $startDate = $get('start_date');
                                if ($startDate) {
                                    $start = new \DateTimeImmutable($startDate);
                                    $end = match ($state) {
                                        PeriodType::Monthly->value => $start->modify('last day of this month'),
                                        PeriodType::Quarterly->value => $start->modify('+3 months -1 day'),
                                        PeriodType::Yearly->value => $start->modify('+1 year -1 day'),
                                        default => $start->modify('+1 month -1 day'),
                                    };
                                    $set('end_date', $end->format('Y-m-d'));
                                }
                            }),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->label('Period Name')
                            ->placeholder('e.g., January 2024, Q1 2024, FY-2024'),

                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->native(false)
                            ->label('Start Date')
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                // Auto-calculate end date based on type
                                $type = $get('type');
                                if ($state && $type) {
                                    $start = new \DateTimeImmutable($state);
                                    $end = match ($type) {
                                        PeriodType::Monthly->value => $start->modify('last day of this month'),
                                        PeriodType::Quarterly->value => $start->modify('+3 months -1 day'),
                                        PeriodType::Yearly->value => $start->modify('+1 year -1 day'),
                                        default => $start->modify('+1 month -1 day'),
                                    };
                                    $set('end_date', $end->format('Y-m-d'));
                                    
                                    // Auto-generate fiscal year
                                    $set('fiscal_year', 'FY-' . $start->format('Y'));
                                }
                            }),

                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->native(false)
                            ->label('End Date')
                            ->afterOrEqual('start_date'),

                        Forms\Components\TextInput::make('fiscal_year')
                            ->required()
                            ->maxLength(20)
                            ->label('Fiscal Year')
                            ->placeholder('e.g., FY-2024')
                            ->default(fn() => 'FY-' . now()->year),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->options([
                                PeriodStatus::Open->value => 'Open (Posting Allowed)',
                                PeriodStatus::Closed->value => 'Closed (Posting Not Allowed)',
                                PeriodStatus::Locked->value => 'Locked (Permanently Closed)',
                            ])
                            ->default(PeriodStatus::Open->value)
                            ->label('Status')
                            ->helperText('Open: Allow posting | Closed: Prevent posting | Locked: Permanently closed'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->rows(3)
                            ->label('Description')
                            ->placeholder('Optional notes about this period'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Period Name'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (PeriodType $state): string => match ($state) {
                        PeriodType::Monthly => 'info',
                        PeriodType::Quarterly => 'warning',
                        PeriodType::Yearly => 'success',
                    })
                    ->label('Type'),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->label('Start Date'),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->label('End Date'),

                Tables\Columns\TextColumn::make('fiscal_year')
                    ->searchable()
                    ->sortable()
                    ->label('Fiscal Year'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (PeriodStatus $state): string => match ($state) {
                        PeriodStatus::Open => 'success',
                        PeriodStatus::Closed => 'warning',
                        PeriodStatus::Locked => 'danger',
                    })
                    ->label('Status'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        PeriodType::Monthly->value => 'Monthly',
                        PeriodType::Quarterly->value => 'Quarterly',
                        PeriodType::Yearly->value => 'Yearly',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PeriodStatus::Open->value => 'Open',
                        PeriodStatus::Closed->value => 'Closed',
                        PeriodStatus::Locked->value => 'Locked',
                    ]),

                Tables\Filters\SelectFilter::make('fiscal_year')
                    ->options(function () {
                        return Period::query()
                            ->distinct()
                            ->orderBy('fiscal_year', 'desc')
                            ->pluck('fiscal_year', 'fiscal_year')
                            ->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->status === PeriodStatus::Locked),
                Tables\Actions\Action::make('close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === PeriodStatus::Open)
                    ->action(fn ($record) => $record->update(['status' => PeriodStatus::Closed]))
                    ->successNotificationTitle('Period closed successfully'),
                Tables\Actions\Action::make('lock')
                    ->icon('heroicon-o-shield-check')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Locking a period is permanent and cannot be undone. All transactions will be frozen.')
                    ->visible(fn ($record) => $record->status === PeriodStatus::Closed)
                    ->action(fn ($record) => $record->update(['status' => PeriodStatus::Locked]))
                    ->successNotificationTitle('Period locked permanently'),
                Tables\Actions\Action::make('reopen')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === PeriodStatus::Closed)
                    ->action(fn ($record) => $record->update(['status' => PeriodStatus::Open]))
                    ->successNotificationTitle('Period reopened successfully'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn ($records) => $records->contains(fn ($r) => $r->status === PeriodStatus::Locked)),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeriods::route('/'),
            'create' => Pages\CreatePeriod::route('/create'),
            'view' => Pages\ViewPeriod::route('/{record}'),
            'edit' => Pages\EditPeriod::route('/{record}/edit'),
        ];
    }
}
