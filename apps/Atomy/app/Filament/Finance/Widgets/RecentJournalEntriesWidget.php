<?php

declare(strict_types=1);

namespace App\Filament\Finance\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Nexus\Finance\Contracts\FinanceManagerInterface;

/**
 * Recent Journal Entries Widget
 * 
 * Displays the most recent journal entries on the dashboard.
 * Uses cached getRecentEntries() method from FinanceManager.
 */
class RecentJournalEntriesWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $financeManager = app(FinanceManagerInterface::class);
        $recentEntries = $financeManager->getRecentEntries(10, []);

        return $table
            ->heading('Recent Journal Entries')
            ->query(
                \App\Models\Finance\JournalEntry::query()
                    ->whereIn('id', array_column($recentEntries, 'id'))
                    ->orderBy('entry_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('entry_number')
                    ->label('Entry #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_debit')
                    ->label('Amount')
                    ->money('MYR'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'posted' => 'success',
                        'reversed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn ($record): string => route('filament.finance.resources.journal-entries.view', $record))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
