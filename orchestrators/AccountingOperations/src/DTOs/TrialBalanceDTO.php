<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DTOs;

/**
 * Data Transfer Object for trial balance.
 */
final readonly class TrialBalanceDTO
{
    /**
     * @param array<AccountBalanceDTO> $accounts
     */
    public function __construct(
        public string $id,
        public string $tenantId,
        public string $periodId,
        public array $accounts,
        public string $totalDebits,
        public string $totalCredits,
        public bool $isBalanced,
        public \DateTimeImmutable $generatedAt,
    ) {}

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'period_id' => $this->periodId,
            'accounts' => array_map(fn (AccountBalanceDTO $a) => $a->toArray(), $this->accounts),
            'total_debits' => $this->totalDebits,
            'total_credits' => $this->totalCredits,
            'is_balanced' => $this->isBalanced,
            'generated_at' => $this->generatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get the difference between debits and credits.
     */
    public function getDifference(): string
    {
        return bcsub($this->totalDebits, $this->totalCredits, 2);
    }
}
