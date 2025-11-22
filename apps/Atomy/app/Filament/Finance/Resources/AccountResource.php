<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources;

use App\Filament\Finance\Resources\AccountResource\Pages;
use App\DataTransferObjects\Finance\CreateAccountDto;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\ValueObjects\AccountType;
use Nexus\Finance\ValueObjects\NormalBalance;
use BackedEnum;
use UnitEnum;

/**
 * Account Resource
 * 
 * Service-layer-only pattern: All operations use FinanceManagerInterface.
 * No direct Eloquent model access.
 */
class AccountResource extends Resource
{
    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-banknotes';

    protected static string | UnitEnum | null $navigationGroup = 'General Ledger';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\TextInput::make('account_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->label('Account Number')
                            ->placeholder('e.g., 1000, 1100, 5000'),

                        Forms\Components\TextInput::make('account_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Account Name')
                            ->placeholder('e.g., Cash, Accounts Receivable'),

                        Forms\Components\Select::make('account_type')
                            ->required()
                            ->options([
                                AccountType::Asset->value => 'Asset',
                                AccountType::Liability->value => 'Liability',
                                AccountType::Equity->value => 'Equity',
                                AccountType::Revenue->value => 'Revenue',
                                AccountType::Expense->value => 'Expense',
                            ])
                            ->native(false)
                            ->label('Account Type'),

                        Forms\Components\Select::make('normal_balance')
                            ->required()
                            ->options([
                                NormalBalance::Debit->value => 'Debit',
                                NormalBalance::Credit->value => 'Credit',
                            ])
                            ->native(false)
                            ->label('Normal Balance')
                            ->helperText('Assets and Expenses = Debit, Liabilities/Equity/Revenue = Credit'),

                        Forms\Components\Select::make('parent_account_id')
                            ->label('Parent Account')
                            ->searchable()
                            ->relationship('parent', 'name')
                            ->nullable()
                            ->helperText('Optional: Select parent account for hierarchical structure'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->rows(3)
                            ->label('Description')
                            ->placeholder('Optional: Account purpose or notes'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active')
                            ->helperText('Inactive accounts cannot be used in new transactions'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label('Code'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name')
                    ->description(fn ($record): string => $record->type),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'asset' => 'success',
                        'liability' => 'danger',
                        'equity' => 'info',
                        'revenue' => 'primary',
                        'expense' => 'warning',
                        default => 'gray',
                    })
                    ->label('Type'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'view' => Pages\ViewAccount::route('/{record}'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }

    /**
     * Get the service instance
     */
    protected static function getFinanceManager(): FinanceManagerInterface
    {
        return app(FinanceManagerInterface::class);
    }
}
