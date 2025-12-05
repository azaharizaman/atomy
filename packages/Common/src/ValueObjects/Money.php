<?php

declare(strict_types=1);

namespace Nexus\Common\ValueObjects;

use Nexus\Common\Contracts\Addable;
use Nexus\Common\Contracts\Comparable;
use Nexus\Common\Contracts\CurrencyConvertible;
use Nexus\Common\Contracts\Divisible;
use Nexus\Common\Contracts\Formattable;
use Nexus\Common\Contracts\Multipliable;
use Nexus\Common\Contracts\SerializableVO;
use Nexus\Common\Contracts\Subtractable;
use Nexus\Common\Exceptions\CurrencyMismatchException;
use Nexus\Common\Exceptions\InvalidMoneyException;

/**
 * Immutable monetary value object with precision arithmetic.
 * 
 * Stores amount as integer (smallest currency unit/cents) to avoid floating point issues.
 */
final readonly class Money implements
    Comparable,
    Addable,
    Subtractable,
    Multipliable,
    Divisible,
    CurrencyConvertible,
    SerializableVO,
    Formattable
{
    private int $amountInMinorUnits;
    private string $currency;

    /**
     * @param int $amountInMinorUnits Amount in smallest currency unit (e.g., cents for USD)
     * @param string $currency ISO 4217 currency code (e.g., 'USD', 'MYR', 'EUR')
     * @throws InvalidMoneyException
     */
    public function __construct(int $amountInMinorUnits, string $currency)
    {
        if (strlen($currency) !== 3) {
            throw new InvalidMoneyException("Currency must be 3-character ISO 4217 code, got: {$currency}");
        }

        $this->amountInMinorUnits = $amountInMinorUnits;
        $this->currency = strtoupper($currency);
    }

    /**
     * Create Money from decimal amount.
     * 
     * @param float|string $amount Decimal amount (e.g., 100.50)
     * @param string $currency ISO 4217 currency code
     * @param int $scale Number of decimal places (default: 2)
     * @return self
     */
    public static function of(float|string $amount, string $currency, int $scale = 2): self
    {
        $multiplier = 10 ** $scale;
        
        if (is_string($amount)) {
            $amountInMinorUnits = (int) round((float) $amount * $multiplier);
        } else {
            $amountInMinorUnits = (int) round($amount * $multiplier);
        }

        return new self($amountInMinorUnits, $currency);
    }

    /**
     * Create zero money.
     */
    public static function zero(string $currency): self
    {
        return new self(0, $currency);
    }

    /**
     * Get amount in smallest currency unit.
     */
    public function getAmountInMinorUnits(): int
    {
        return $this->amountInMinorUnits;
    }

    /**
     * Get currency code.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Get decimal amount.
     * 
     * @param int $scale Number of decimal places (default: 2)
     */
    public function getAmount(int $scale = 2): float
    {
        $divisor = 10 ** $scale;
        return $this->amountInMinorUnits / $divisor;
    }

    /**
     * Get formatted amount as string.
     * 
     * @param array<string, mixed> $options Formatting options (decimals, symbol, etc.)
     */
    public function format(array $options = []): string
    {
        $decimals = $options['decimals'] ?? 2;
        $symbol = $options['symbol'] ?? true;
        
        $divisor = 10 ** $decimals;
        $formatted = number_format($this->amountInMinorUnits / $divisor, $decimals, '.', '');
        
        return $symbol ? $formatted . ' ' . $this->currency : $formatted;
    }

    /**
     * Add another money amount.
     * 
     * @throws CurrencyMismatchException
     */
    public function add(Addable $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only add another Money');
        }

        $this->assertSameCurrency($other);

        return new self(
            $this->amountInMinorUnits + $other->amountInMinorUnits,
            $this->currency
        );
    }

    /**
     * Subtract another money amount.
     * 
     * @throws CurrencyMismatchException
     */
    public function subtract(Subtractable $other): static
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only subtract another Money');
        }

        $this->assertSameCurrency($other);

        return new self(
            $this->amountInMinorUnits - $other->amountInMinorUnits,
            $this->currency
        );
    }

    /**
     * Multiply by a factor.
     * 
     * @param float|int $multiplier
     */
    public function multiply(float|int $multiplier): static
    {
        $result = (int) round($this->amountInMinorUnits * $multiplier);

        return new self($result, $this->currency);
    }

    /**
     * Divide by a divisor.
     * 
     * @param float|int $divisor
     * @throws InvalidMoneyException
     */
    public function divide(float|int $divisor): static
    {
        if ($divisor == 0) {
            throw new InvalidMoneyException('Cannot divide by zero');
        }

        $result = (int) round($this->amountInMinorUnits / $divisor);

        return new self($result, $this->currency);
    }

    /**
     * Get absolute value.
     */
    public function abs(): self
    {
        return new self(abs($this->amountInMinorUnits), $this->currency);
    }

    /**
     * Negate the amount.
     */
    public function negate(): self
    {
        return new self(-$this->amountInMinorUnits, $this->currency);
    }

    /**
     * Check if amount is positive.
     */
    public function isPositive(): bool
    {
        return $this->amountInMinorUnits > 0;
    }

    /**
     * Check if amount is negative.
     */
    public function isNegative(): bool
    {
        return $this->amountInMinorUnits < 0;
    }

    /**
     * Check if amount is zero.
     */
    public function isZero(): bool
    {
        return $this->amountInMinorUnits === 0;
    }

    /**
     * Compare with another money amount.
     * 
     * @return int Returns -1 if less than, 0 if equal, 1 if greater than
     * @throws CurrencyMismatchException
     */
    public function compareTo(Comparable $other): int
    {
        if (!$other instanceof self) {
            throw new \InvalidArgumentException('Can only compare with another Money');
        }

        $this->assertSameCurrency($other);

        if ($this->amountInMinorUnits < $other->amountInMinorUnits) {
            return -1;
        }

        if ($this->amountInMinorUnits > $other->amountInMinorUnits) {
            return 1;
        }

        return 0;
    }

    /**
     * Check if equal to another money amount.
     * 
     * @throws CurrencyMismatchException
     */
    public function equals(Comparable $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        $this->assertSameCurrency($other);

        return $this->amountInMinorUnits === $other->amountInMinorUnits;
    }

    /**
     * Check if greater than another money amount.
     * 
     * @throws CurrencyMismatchException
     */
    public function greaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * Check if greater than or equal to another money amount.
     * 
     * @throws CurrencyMismatchException
     */
    public function greaterThanOrEqual(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * Check if less than another money amount.
     * 
     * @throws CurrencyMismatchException
     */
    public function lessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * Check if less than or equal to another money amount.
     * 
     * @throws CurrencyMismatchException
     */
    public function lessThanOrEqual(self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * Allocate money according to ratios.
     * 
     * @param array<int> $ratios
     * @return array<self>
     * @throws InvalidMoneyException
     */
    public function allocate(array $ratios): array
    {
        if (empty($ratios)) {
            throw new InvalidMoneyException('Cannot allocate to empty ratios');
        }

        $total = array_sum($ratios);
        
        if ($total <= 0) {
            throw new InvalidMoneyException('Sum of ratios must be positive');
        }

        $remainder = $this->amountInMinorUnits;
        $results = [];

        foreach ($ratios as $ratio) {
            $share = (int) floor(($this->amountInMinorUnits * $ratio) / $total);
            $results[] = new self($share, $this->currency);
            $remainder -= $share;
        }

        // Distribute remainder to first results
        for ($i = 0; $remainder > 0; $i++) {
            $results[$i] = new self($results[$i]->amountInMinorUnits + 1, $this->currency);
            $remainder--;
        }

        return $results;
    }

    /**
     * Convert to another currency using exchange rate.
     * 
     * @param string $toCurrency Target currency code
     * @param float $exchangeRate Exchange rate (multiply factor)
     */
    public function convertToCurrency(string $toCurrency, float $exchangeRate): static
    {
        $convertedAmount = (int) round($this->amountInMinorUnits * $exchangeRate);
        return new self($convertedAmount, $toCurrency);
    }

    /**
     * Convert to another currency using high-precision string exchange rate.
     * 
     * Uses bcmath for arbitrary precision arithmetic to prevent precision loss
     * in exchange rate conversions.
     * 
     * @param string $toCurrency Target currency code
     * @param string $exchangeRate Exchange rate as string (multiply factor)
     * @param int $scale Scale for bcmath operations (default: 8 decimal places)
     * @return static
     */
    public function convertToCurrencyWithStringRate(string $toCurrency, string $exchangeRate, int $scale = 8): static
    {
        // Convert minor units to string for bcmath
        $minorUnitsStr = (string) $this->amountInMinorUnits;
        
        // Perform multiplication with arbitrary precision
        $convertedStr = bcmul($minorUnitsStr, $exchangeRate, $scale);
        
        // Round using bcmath: add ±0.5 based on sign, then truncate
        // For "round half away from zero": positive add 0.5, negative add -0.5
        $adjustment = bccomp($convertedStr, '0', $scale) < 0 ? '-0.5' : '0.5';
        $roundedStr = bcadd($convertedStr, $adjustment, 0);
        
        // Parse as integer
        $convertedAmount = (int) $roundedStr;
        
        return new self($convertedAmount, $toCurrency);
    }

    /**
     * Assert that both money objects have the same currency.
     * 
     * @throws CurrencyMismatchException
     */
    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException(
                "Currency mismatch: {$this->currency} vs {$other->currency}"
            );
        }
    }

    /**
     * Convert to string representation.
     */
    public function toString(): string
    {
        return $this->format() . ' ' . $this->currency;
    }

    /**
     * Convert to array representation.
     * 
     * @return array{amount: float, currency: string, amountInMinorUnits: int}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->getAmount(),
            'currency' => $this->currency,
            'amountInMinorUnits' => $this->amountInMinorUnits,
        ];
    }

    /**
     * Create from array representation.
     */
    public static function fromArray(array $data): static
    {
        if (isset($data['amountInMinorUnits'])) {
            return new self($data['amountInMinorUnits'], $data['currency']);
        }

        return self::of($data['amount'], $data['currency']);
    }
}
