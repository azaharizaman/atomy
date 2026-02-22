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

    // Legacy/test-only codes - kept for backward compatibility with tests
    // These represent older string formats used in legacy systems
    /** @deprecated Use RECEIVABLE, PAYABLE, or ASSET for new code */
    case AR = 'AR';
    /** @deprecated Use RECEIVABLE, PAYABLE, or ASSET for new code */
    case AP = 'AP';
    /** @deprecated Use RECEIVABLE, PAYABLE, or ASSET for new code */
    case FA = 'FA';
    /** @deprecated Use RECEIVABLE, PAYABLE, or ASSET for new code */
    case INV = 'INV';
    /** @deprecated Use RECEIVABLE, PAYABLE, or ASSET for new code */
    case INVENTORY = 'inventory';
    /** @deprecated Use RECEIVABLE, PAYABLE, or ASSET for new code */
    case PAYROLL = 'payroll';

    /**
     * Create SubledgerType from a string value.
     *
     * @param string $value The string value to convert
     * @return self The corresponding SubledgerType case
     * @throws \InvalidArgumentException If the value cannot be matched
     */
    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        return self::tryFrom($normalized) ?? self::tryFrom($value) ?? throw new \InvalidArgumentException(
            sprintf('Invalid SubledgerType value: "%s"', $value)
        );
    }
}
