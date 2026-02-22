<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Enums;

/**
 * Enum representing subledger types for GL operations.
 *
 * @since 1.0.0
 */
enum SubledgerType: string
{
    case RECEIVABLE = 'receivable';
    case PAYABLE = 'payable';
    case ASSET = 'asset';

    // Additional codes used in tests
    case AR = 'AR';
    case AP = 'AP';
    case FA = 'FA';
    case INV = 'INV';
    case INVENTORY = 'inventory';
    case PAYROLL = 'payroll';

    /**
     * Create SubledgerType from a string value.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? match (strtoupper($value)) {
            'AR' => self::AR,
            'AP' => self::AP,
            'FA' => self::FA,
            'INV' => self::INV,
            'INVENTORY' => self::INVENTORY,
            'PAYROLL' => self::PAYROLL,
            default => self::RECEIVABLE,
        };
    }
}
