<?php

declare(strict_types=1);

namespace Nexus\Currency\Contracts;

use Nexus\Currency\ValueObjects\Currency;

/**
 * Currency Repository Interface
 *
 * Defines the contract for retrieving and managing currency metadata.
 * Implementations should provide access to ISO 4217 currency data.
 *
 * @package Nexus\Currency\Contracts
 */
interface CurrencyRepositoryInterface
{
    /**
     * Find a currency by its ISO 4217 code.
     *
     * @param string $code The 3-letter ISO 4217 currency code (e.g., "USD", "EUR")
     * @return Currency|null The currency if found, null otherwise
     */
    public function findByCode(string $code): ?Currency;

    /**
     * Get all available currencies.
     *
     * @return array<string, Currency> Array of currencies indexed by code
     */
    public function getAll(): array;

    /**
     * Check if a currency code exists.
     *
     * @param string $code The 3-letter ISO 4217 currency code
     * @return bool True if the currency exists, false otherwise
     */
    public function exists(string $code): bool;

    /**
     * Get multiple currencies by their codes.
     *
     * @param array<string> $codes Array of currency codes
     * @return array<string, Currency> Array of found currencies indexed by code
     */
    public function findByCodes(array $codes): array;

    /**
     * Get all active/enabled currencies.
     *
     * @return array<string, Currency> Array of active currencies indexed by code
     */
    public function getActive(): array;

    /**
     * Search currencies by name or code.
     *
     * @param string $query Search query
     * @return array<string, Currency> Array of matching currencies
     */
    public function search(string $query): array;
}
