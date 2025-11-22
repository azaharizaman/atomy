<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Finance;

use Nexus\Finance\ValueObjects\AccountType;
use Nexus\Finance\ValueObjects\NormalBalance;

/**
 * Data Transfer Object for creating accounts.
 * 
 * APPLICATION LAYER CONTRACT: Lives in Atomy, not in Nexus\Finance package.
 * 
 * Purpose:
 * - Decouple Filament forms from domain entities
 * - Provide validation-friendly structure for UI layer
 * - Convert to array before passing to FinanceManagerInterface::createAccount()
 * 
 * Architecture:
 * Filament Form → CreateAccountDto (validation) → toArray() → FinanceManager (domain logic)
 */
final readonly class CreateAccountDto
{
    public function __construct(
        public string $accountNumber,
        public string $accountName,
        public AccountType $accountType,
        public NormalBalance $normalBalance,
        public ?string $parentAccountId = null,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}

    /**
     * Create DTO from array (e.g., from Filament form data)
     * 
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountNumber: $data['account_number'],
            accountName: $data['account_name'],
            accountType: AccountType::from($data['account_type']),
            normalBalance: NormalBalance::from($data['normal_balance']),
            parentAccountId: $data['parent_account_id'] ?? null,
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    /**
     * Convert to array for validation or serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_number' => $this->accountNumber,
            'account_name' => $this->accountName,
            'account_type' => $this->accountType->value,
            'normal_balance' => $this->normalBalance->value,
            'parent_account_id' => $this->parentAccountId,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}
