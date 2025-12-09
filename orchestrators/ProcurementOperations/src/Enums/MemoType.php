<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Enums;

/**
 * Types of credit/debit memos in procurement.
 */
enum MemoType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';

    /**
     * Get human-readable description.
     */
    public function description(): string
    {
        return match ($this) {
            self::CREDIT => 'Credit Memo (reduces amount owed to vendor)',
            self::DEBIT => 'Debit Memo (increases amount owed to vendor)',
        };
    }

    /**
     * Get the GL posting sign multiplier.
     * Credit = -1 (reduces payable), Debit = +1 (increases payable)
     */
    public function glMultiplier(): int
    {
        return match ($this) {
            self::CREDIT => -1,
            self::DEBIT => 1,
        };
    }

    /**
     * Check if this memo reduces the vendor balance.
     */
    public function reducesVendorBalance(): bool
    {
        return $this === self::CREDIT;
    }
}
