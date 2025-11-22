<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\EventStreamResource\Pages;
use App\Models\EventStream;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * EventStream Resource
 * 
 * Debug and audit tool for viewing immutable event log.
 * Supports temporal queries and event versioning.
 */
class EventStreamResource extends Resource
{
    protected static ?string $model = EventStream::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Event';

    protected static ?string $pluralModelLabel = 'Event Stream';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('event_id')
                    ->label('Event ID')
                    ->disabled(),

                Forms\Components\TextInput::make('aggregate_id')
                    ->label('Aggregate ID')
                    ->disabled(),

                Forms\Components\TextInput::make('aggregate_type')
                    ->label('Aggregate Type')
                    ->disabled(),

                Forms\Components\TextInput::make('event_type')
                    ->label('Event Type')
                    ->disabled(),

                Forms\Components\TextInput::make('event_version')
                    ->label('Event Version')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('occurred_at')
                    ->label('Occurred At')
                    ->disabled(),

                Forms\Components\KeyValue::make('payload')
                    ->label('Event Payload')
                    ->disabled(),

                Forms\Components\KeyValue::make('metadata')
                    ->label('Event Metadata')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_id')
                    ->label('Event ID')
                    ->limit(10)
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('aggregate_id')
                    ->label('Aggregate ID')
                    ->limit(10)
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('aggregate_type')
                    ->label('Aggregate Type')
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('event_type')
                    ->label('Event Type')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('event_version')
                    ->label('Version')
                    ->sortable(),

                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Occurred At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('aggregate_type')
                    ->options([
                        'account' => 'Account',
                        'journal_entry' => 'Journal Entry',
                    ]),

                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'AccountDebitedEvent' => 'Account Debited',
                        'AccountCreditedEvent' => 'Account Credited',
                        'JournalEntryPostedEvent' => 'Journal Entry Posted',
                        'JournalEntryReversedEvent' => 'Journal Entry Reversed',
                    ]),

                Tables\Filters\Filter::make('occurred_at')
                    ->form([
                        Forms\Components\DateTimePicker::make('from')->native(false),
                        Forms\Components\DateTimePicker::make('until')->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->where('occurred_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->where('occurred_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('occurred_at', 'desc');
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
            'index' => Pages\ListEventStreams::route('/'),
            'view' => Pages\ViewEventStream::route('/{record}'),
        ];
    }
}
