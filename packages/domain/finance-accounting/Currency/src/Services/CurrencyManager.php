<?php

declare(strict_types=1);

namespace Nexus\Currency\Services;

use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Currency\Contracts\CurrencyRepositoryInterface;
use Nexus\Currency\Exceptions\CurrencyNotFoundException;
use Nexus\Currency\Exceptions\InvalidCurrencyCodeException;
use Nexus\Currency\ValueObjects\Currency;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Currency Manager Service
 *
 * Provides high-level currency management operations with validation,
 * formatting, and metadata access.
 *
 * @package Nexus\Currency\Services
 */
class CurrencyManager implements CurrencyManagerInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly CurrencyRepositoryInterface $repository,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Get a currency by its code.
     *
     * @param string $code ISO 4217 currency code
     * @return Currency
     * @throws CurrencyNotFoundException If currency not found
     */
    public function getCurrency(string $code): Currency
    {
        $normalizedCode = $this->normalizeCode($code);

        $currency = $this->repository->findByCode($normalizedCode);

        if ($currency === null) {
            $this->logger->warning("Currency not found: {$normalizedCode}");
            throw CurrencyNotFoundException::byCode($normalizedCode);
        }

        return $currency;
    }

    /**
     * Validate a currency code.
     *
     * @param string $code ISO 4217 currency code
     * @return void
     * @throws InvalidCurrencyCodeException If code is invalid
     */
    public function validateCode(string $code): void
    {
        if (empty($code)) {
            throw InvalidCurrencyCodeException::empty();
        }

        if (strlen($code) !== 3) {
            throw InvalidCurrencyCodeException::wrongLength($code);
        }

        if (!ctype_alpha($code)) {
            throw InvalidCurrencyCodeException::notAlphabetic($code);
        }

        if (!ctype_upper($code)) {
            throw InvalidCurrencyCodeException::notUppercase($code);
        }

        // Check if currency exists in repository
        if (!$this->repository->exists($code)) {
            throw CurrencyNotFoundException::byCode($code);
        }
    }

    /**
     * Get the decimal precision for a currency.
     *
     * @param string $code ISO 4217 currency code
     * @return int Number of decimal places
     * @throws CurrencyNotFoundException If currency not found
     */
    public function getDecimalPrecision(string $code): int
    {
        $currency = $this->getCurrency($code);
        return $currency->getDecimalPlaces();
    }

    /**
     * Format an amount according to currency rules.
     *
     * @param string $amount Amount as BCMath string
     * @param string $currencyCode ISO 4217 currency code
     * @param bool $includeSymbol Whether to include currency symbol
     * @param bool $includeCode Whether to include currency code
     * @return string Formatted amount
     * @throws CurrencyNotFoundException If currency not found
     */
    public function formatAmount(
        string $amount,
        string $currencyCode,
        bool $includeSymbol = true,
        bool $includeCode = false
    ): string {
        $currency = $this->getCurrency($currencyCode);
        return $currency->formatAmount($amount, $includeSymbol, $includeCode);
    }

    /**
     * Check if a currency code is valid and exists.
     *
     * @param string $code ISO 4217 currency code
     * @return bool True if valid and exists
     */
    public function exists(string $code): bool
    {
        if (strlen($code) !== 3 || !ctype_alpha($code) || !ctype_upper($code)) {
            return false;
        }

        return $this->repository->exists($code);
    }

    /**
     * Get all available currencies.
     *
     * @return array<string, Currency> Array of currencies indexed by code
     */
    public function getAllCurrencies(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Get multiple currencies by their codes.
     *
     * @param array<string> $codes Array of currency codes
     * @return array<string, Currency> Array of found currencies
     */
    public function getCurrencies(array $codes): array
    {
        $normalizedCodes = array_map(fn($code) => $this->normalizeCode($code), $codes);
        return $this->repository->findByCodes($normalizedCodes);
    }

    /**
     * Search for currencies by name or code.
     *
     * @param string $query Search query
     * @return array<string, Currency> Matching currencies
     */
    public function searchCurrencies(string $query): array
    {
        return $this->repository->search($query);
    }

    /**
     * Get only active currencies.
     *
     * @return array<string, Currency> Active currencies
     */
    public function getActiveCurrencies(): array
    {
        return $this->repository->getActive();
    }

    /**
     * Normalize a currency code to uppercase.
     *
     * @param string $code Currency code
     * @return string Normalized code
     */
    private function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}
