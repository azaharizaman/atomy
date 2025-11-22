<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\JournalEntryResource\Pages;
use App\DataTransferObjects\Finance\CreateJournalEntryDto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Nexus\Finance\Contracts\FinanceManagerInterface;

/**
 * Journal Entry Resource
 * 
 * Service-layer-only pattern: All operations use FinanceManagerInterface.
 * Uses repeater for double-entry line items.
 */
class JournalEntryResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'General Ledger';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Journal Entry';

    protected static ?string $pluralModelLabel = 'Journal Entries';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Entry Details')
                    ->schema([
                        Forms\Components\DatePicker::make('entry_date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->label('Entry Date'),

                        Forms\Components\TextInput::make('reference_number')
                            ->maxLength(50)
                            ->label('Reference Number')
                            ->placeholder('Optional: PO#, Invoice#, etc.'),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(500)
                            ->rows(2)
                            ->label('Description')
                            ->placeholder('Brief description of the transaction'),

                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->rows(3)
                            ->label('Notes')
                            ->placeholder('Optional: Additional details'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Journal Entry Lines')
                    ->description('Add debit and credit lines. Total debits must equal total credits.')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->label('Account')
                                    ->required()
                                    ->searchable()
                                    ->options(function () {
                                        return \App\Models\Finance\Account::query()
                                            ->orderBy('code')
                                            ->get()
                                            ->mapWithKeys(fn ($account) => [$account->id => "{$account->code} - {$account->name}"])
                                            ->toArray();
                                    })
                                    ->preload()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->prefix('RM')
                                    ->label('Amount')
                                    ->placeholder('0.00'),

                                Forms\Components\ToggleButtons::make('is_debit')
                                    ->label('Type')
                                    ->required()
                                    ->boolean()
                                    ->grouped()
                                    ->options([
                                        true => 'Debit',
                                        false => 'Credit',
                                    ])
                                    ->colors([
                                        true => 'success',
                                        false => 'danger',
                                    ])
                                    ->icons([
                                        true => 'heroicon-o-plus-circle',
                                        false => 'heroicon-o-minus-circle',
                                    ])
                                    ->inline(),

                                Forms\Components\TextInput::make('description')
                                    ->maxLength(255)
                                    ->label('Line Description')
                                    ->placeholder('Optional')
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->minItems(2)
                            ->defaultItems(2)
                            ->addActionLabel('Add Line')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['account_id'], $state['amount']) 
                                    ? ($state['is_debit'] ? 'DR' : 'CR') . ' RM ' . number_format((float)$state['amount'], 2)
                                    : null
                            ),
                    ]),

                Forms\Components\Section::make('Totals')
                    ->schema([
                        Forms\Components\Placeholder::make('total_debits')
                            ->label('Total Debits')
                            ->content(fn (?array $state): string => 
                                'RM ' . number_format(
                                    array_sum(array_map(
                                        fn ($line) => ($line['is_debit'] ?? false) ? (float)($line['amount'] ?? 0) : 0,
                                        $state['lines'] ?? []
                                    )), 2
                                )
                            ),

                        Forms\Components\Placeholder::make('total_credits')
                            ->label('Total Credits')
                            ->content(fn (?array $state): string => 
                                'RM ' . number_format(
                                    array_sum(array_map(
                                        fn ($line) => !($line['is_debit'] ?? true) ? (float)($line['amount'] ?? 0) : 0,
                                        $state['lines'] ?? []
                                    )), 2
                                )
                            ),

                        Forms\Components\Placeholder::make('balance_check')
                            ->label('Status')
                            ->content(function (?array $state): string {
                                $debits = array_sum(array_map(
                                    fn ($line) => ($line['is_debit'] ?? false) ? (float)($line['amount'] ?? 0) : 0,
                                    $state['lines'] ?? []
                                ));
                                $credits = array_sum(array_map(
                                    fn ($line) => !($line['is_debit'] ?? true) ? (float)($line['amount'] ?? 0) : 0,
                                    $state['lines'] ?? []
                                ));
                                
                                return abs($debits - $credits) < 0.01 
                                    ? '✓ Balanced' 
                                    : '✗ Out of Balance (Diff: RM ' . number_format(abs($debits - $credits), 2) . ')';
                            })
                            ->color(fn (?array $state): string => 
                                abs(
                                    array_sum(array_map(fn ($line) => ($line['is_debit'] ?? false) ? (float)($line['amount'] ?? 0) : 0, $state['lines'] ?? [])) -
                                    array_sum(array_map(fn ($line) => !($line['is_debit'] ?? true) ? (float)($line['amount'] ?? 0) : 0, $state['lines'] ?? []))
                                ) < 0.01 ? 'success' : 'danger'
                            ),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_number')
                    ->searchable()
                    ->sortable()
                    ->label('Entry #')
                    ->copyable(),

                Tables\Columns\TextColumn::make('entry_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->label('Description'),

                Tables\Columns\TextColumn::make('total_debit')
                    ->money('MYR')
                    ->label('Amount'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'success',
                        'reversed' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),

                Tables\Columns\TextColumn::make('posted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
                    ]),

                Tables\Filters\Filter::make('entry_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->native(false),
                        Forms\Components\DatePicker::make('until')->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('entry_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->status !== 'draft'),
                Tables\Actions\Action::make('post')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        app(FinanceManagerInterface::class)->postJournalEntry($record->id);
                    })
                    ->successNotificationTitle('Journal entry posted successfully'),
                Tables\Actions\Action::make('reverse')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'posted')
                    ->form([
                        Forms\Components\DatePicker::make('reversal_date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->label('Reversal Date'),
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->maxLength(500)
                            ->label('Reason for Reversal'),
                    ])
                    ->action(function ($record, array $data) {
                        app(FinanceManagerInterface::class)->reverseJournalEntry(
                            $record->id,
                            new \DateTimeImmutable($data['reversal_date']),
                            $data['reason']
                        );
                    })
                    ->successNotificationTitle('Journal entry reversed successfully'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn ($records) => $records->contains(fn ($r) => $r->status !== 'draft')),
                ]),
            ])
            ->defaultSort('entry_date', 'desc');
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
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
