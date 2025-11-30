<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

/**
 * Statement Hash Value Object
 *
 * Cryptographic hash for bank statement deduplication.
 */
final readonly class StatementHash
{
    public function __construct(
        private string $hash
    ) {
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Create hash from statement data
     */
    public static function create(
        string $bankAccountId,
        string $startDate,
        string $endDate,
        string $totalDebit,
        string $totalCredit
    ): self {
        $data = implode('|', [
            $bankAccountId,
            $startDate,
            $endDate,
            $totalDebit,
            $totalCredit,
        ]);

        return new self(hash('sha256', $data));
    }

    /**
     * Create hash from transaction line
     */
    public static function createLineHash(
        string $date,
        string $description,
        string $amount
    ): self {
        $data = implode('|', [$date, $description, $amount]);
        return new self(hash('sha256', $data));
    }

    public function equals(self $other): bool
    {
        return $this->hash === $other->hash;
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
