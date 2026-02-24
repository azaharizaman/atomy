<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Enums;

/**
 * Subledger Type Enum
 *
 * Represents the different types of subledgers that can post to the General Ledger.
 */
enum SubledgerType: string
{
    case RECEIVABLE = 'RECEIVABLE';
    case PAYABLE = 'PAYABLE';
    case ASSET = 'ASSET';

    /**
     * Check if this is a receivable subledger
     */
    public function isReceivable(): bool
    {
        return $this === self::RECEIVABLE;
    }

    /**
     * Check if this is a payable subledger
     */
    public function isPayable(): bool
    {
        return $this === self::PAYABLE;
    }

    /**
     * Check if this is an asset subledger
     */
    public function isAsset(): bool
    {
        return $this === self::ASSET;
    }

    /**
     * Get the control account prefix for this subledger type
     */
    public function getControlAccountPrefix(): string
    {
        return match ($this) {
            self::RECEIVABLE => 'AR',
            self::PAYABLE => 'AP',
            self::ASSET => 'FA',
        };
    }
}
