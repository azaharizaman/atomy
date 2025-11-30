<?php

declare(strict_types=1);

namespace Nexus\CashManagement\ValueObjects;

use DateTimeImmutable;

/**
 * Cash Position Value Object
 *
 * Immutable snapshot of cash position at a specific point in time.
 */
final readonly class CashPosition
{
    public function __construct(
        private string $balance,
        private string $currency,
        private DateTimeImmutable $asOfDate,
        private ?string $bankAccountId = null
    ) {
    }

    public function getBalance(): string
    {
        return $this->balance;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAsOfDate(): DateTimeImmutable
    {
        return $this->asOfDate;
    }

    public function getBankAccountId(): ?string
    {
        return $this->bankAccountId;
    }

    /**
     * Check if balance is positive
     */
    public function isPositive(): bool
    {
        return bccomp($this->balance, '0', 4) > 0;
    }

    /**
     * Check if balance is negative
     */
    public function isNegative(): bool
    {
        return bccomp($this->balance, '0', 4) < 0;
    }

    /**
     * Check if balance is zero
     */
    public function isZero(): bool
    {
        return bccomp($this->balance, '0', 4) === 0;
    }

    /**
     * Get formatted balance string
     */
    public function getFormattedBalance(): string
    {
        // Format with proper decimal handling using bcmath to avoid precision loss
        $rounded = bcadd($this->balance, '0', 2); // round to 2 decimals, string
        // Split into sign, integer, and decimal parts
        $sign = '';
        if (str_starts_with($rounded, '-')) {
            $sign = '-';
            $rounded = substr($rounded, 1);
        }
        [$intPart, $decPart] = explode('.', $rounded . '.00', 2);
        $intPartWithSep = number_format((int)$intPart, 0, '', ',');
        $formatted = $sign . $intPartWithSep . '.' . substr($decPart, 0, 2);
        return sprintf('%s %s', $this->currency, $formatted);
    }
}
