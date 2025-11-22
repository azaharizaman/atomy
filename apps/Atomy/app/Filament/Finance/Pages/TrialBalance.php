<?php

declare(strict_types=1);

namespace App\Filament\Finance\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Nexus\Finance\Contracts\FinanceManagerInterface;

/**
 * Trial Balance Report
 * 
 * Generates trial balance report as of a specific date.
 * Uses cached generateTrialBalance() method from FinanceManager.
 */
class TrialBalance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static string $view = 'filament.finance.pages.trial-balance';

    protected static ?string $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Trial Balance';

    public ?string $asOfDate = null;

    public ?array $trialBalanceData = null;

    public function mount(): void
    {
        $this->asOfDate = now()->format('Y-m-d');
        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('asOfDate')
                    ->label('As Of Date')
                    ->required()
                    ->native(false)
                    ->default(now())
                    ->afterStateUpdated(fn () => $this->generateReport())
                    ->live(),
            ]);
    }

    public function generateReport(): void
    {
        if (!$this->asOfDate) {
            return;
        }

        $financeManager = app(FinanceManagerInterface::class);
        $asOfDate = new \DateTimeImmutable($this->asOfDate);
        
        $this->trialBalanceData = $financeManager->generateTrialBalance($asOfDate);
    }

    public function getTrialBalanceData(): ?array
    {
        return $this->trialBalanceData;
    }
}
