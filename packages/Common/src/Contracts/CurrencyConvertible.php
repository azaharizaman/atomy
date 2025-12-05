<?php

declare(strict_types=1);

namespace Nexus\Common\Contracts;

/**
 * Interface for value objects that support currency conversion.
 *
 * **Important Architectural Note:**
 * This interface provides a LOW-LEVEL conversion mechanism when you already
 * have the exchange rate. For proper currency management, use `Nexus\Currency`:
 *
 * - `Nexus\Common\ValueObjects\Money`: Immutable value representation
 *   - ✅ Basic arithmetic (add, subtract, multiply, divide)
 *   - ✅ Comparison operations
 *   - ✅ Formatting
 *   - ✅ Low-level conversion (when exchange rate is provided)
 *
 * - `Nexus\Currency` Package: Currency operations and exchange rate management
 *   - ✅ Exchange rate management and storage
 *   - ✅ Currency conversion logic (fetches rates automatically)
 *   - ✅ Multi-currency operations
 *   - ✅ Historical exchange rates
 *   - ✅ Returns Money VOs as results
 *
 * **When to Use This Interface:**
 * - When you already have the exchange rate from an external source
 * - For simple, one-off conversions in domain logic
 * - When building currency-related services
 *
 * **When to Use Nexus\Currency Instead:**
 * - When you need to look up exchange rates
 * - For multi-currency financial operations
 * - When historical rates are required
 * - For complex currency conversion workflows
 *
 * **Expected Behavior:**
 * - convertToCurrency() MUST be immutable (return a new instance)
 * - Exchange rate is FROM current currency TO target currency
 * - The result MUST have the target currency code
 * - Rounding should be appropriate for the currency (typically 2 decimal places)
 *
 * **Example Implementation:**
 * ```php
 * final readonly class Money implements CurrencyConvertible
 * {
 *     public function convertToCurrency(string $toCurrency, float $exchangeRate): static
 *     {
 *         if ($exchangeRate <= 0) {
 *             throw new InvalidMoneyException('Exchange rate must be positive');
 *         }
 *         $convertedAmount = (int) round($this->amountInMinorUnits * $exchangeRate);
 *         return new self($convertedAmount, $toCurrency);
 *     }
 *
 *     public function getCurrency(): string
 *     {
 *         return $this->currency;
 *     }
 * }
 * ```
 *
 * **Recommended Usage with Nexus\Currency:**
 * ```php
 * // ✅ CORRECT: Use Currency package for exchange rate lookup
 * public function __construct(
 *     private CurrencyManagerInterface $currencyManager
 * ) {}
 *
 * public function convertToBaseCurrency(Money $amount): Money
 * {
 *     return $this->currencyManager->convert(
 *         amount: $amount,
 *         toCurrency: 'MYR'
 *     );
 * }
 * ```
 *
 * @see \Nexus\Common\ValueObjects\Money
 * @see \Nexus\Currency\Contracts\CurrencyManagerInterface
 */
interface CurrencyConvertible
{
    /**
     * Convert to another currency using the provided exchange rate.
     *
     * @param string $toCurrency Target ISO 4217 currency code (e.g., 'USD', 'MYR')
     * @param float $exchangeRate Exchange rate from current to target currency (must be > 0)
     * @return static New instance in the target currency (immutable)
     * @throws \InvalidArgumentException If exchange rate is invalid
     */
    public function convertToCurrency(string $toCurrency, float $exchangeRate): static;

    /**
     * Get the ISO 4217 currency code.
     *
     * @return string Three-letter currency code (e.g., 'USD', 'MYR', 'EUR')
     */
    public function getCurrency(): string;
}
