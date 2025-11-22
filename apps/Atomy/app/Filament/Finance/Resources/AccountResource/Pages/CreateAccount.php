<?php

declare(strict_types=1);

namespace App\Filament\Finance\Resources\AccountResource\Pages;

use App\Filament\Finance\Resources\AccountResource;
use App\DataTransferObjects\Finance\CreateAccountDto;
use Filament\Resources\Pages\CreateRecord;
use Nexus\Finance\Contracts\FinanceManagerInterface;

/**
 * Create Account Page
 * 
 * Service-layer-only: Uses DTO → FinanceManager → Domain
 */
class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    /**
     * Override to use service layer instead of direct Eloquent
     */
    protected function handleRecordCreation(array $data): mixed
    {
        // Convert form data to DTO
        $dto = CreateAccountDto::fromArray($data);

        // Call service layer (framework-agnostic)
        $financeManager = app(FinanceManagerInterface::class);
        $account = $financeManager->createAccount($dto->toArray());

        // Return account ID for redirect
        return $account->getId();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
