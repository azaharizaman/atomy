<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\JournalEntryResource\Pages;

use App\Filament\Finance\Resources\JournalEntryResource;
use App\DataTransferObjects\Finance\CreateJournalEntryDto;
use Filament\Resources\Pages\CreateRecord;
use Nexus\Finance\Contracts\FinanceManagerInterface;
use Nexus\Finance\Exceptions\UnbalancedJournalEntryException;

/**
 * Create Journal Entry - Service Layer Pattern
 * 
 * Flow: Filament Form → CreateJournalEntryDto → FinanceManagerInterface
 * No direct Eloquent model usage.
 */
class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Override to use service layer instead of direct Eloquent save.
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            // Step 1: Convert Filament form data to DTO
            $dto = CreateJournalEntryDto::fromArray($data);

            // Step 2: Validate balance
            if (!$dto->isBalanced()) {
                throw new UnbalancedJournalEntryException(
                    'Journal entry is not balanced. Debits: RM ' . number_format($dto->getTotalDebits(), 2) .
                    ', Credits: RM ' . number_format($dto->getTotalCredits(), 2)
                );
            }

            // Step 3: Call domain service
            $financeManager = app(FinanceManagerInterface::class);
            $journalEntry = $financeManager->createJournalEntry($dto->toArray());

            // Step 4: Return the created entry
            return \App\Models\Finance\JournalEntry::find($journalEntry->getId());

        } catch (UnbalancedJournalEntryException $e) {
            // Show validation error in Filament
            $this->halt();
            \Filament\Notifications\Notification::make()
                ->title('Validation Error')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }
    }
}
