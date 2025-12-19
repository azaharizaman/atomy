<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

use DateTimeImmutable;

/**
 * Exchange Rate Snapshot Value Object
 *
 * Captures the exchange rate at the time of a payment transaction.
 * This is an immutable snapshot - once captured, it never changes.
 *
 * Conversion methods:
 * - `convert()`: Converts from sourceCurrency → targetCurrency using the rate
 * - `convertBack()`: Converts from targetCurrency → sourceCurrency using the inverse rate
 *
 * This is NOT for currency conversion logic (that's Nexus\Currency's job).
 * This is for recording what rate was used when the payment was created.
 *
 * @package Nexus\Payment\ValueObjects
 */
final class ExchangeRateSnapshot
{
    /**
     * @param string $sourceCurrency Source currency code (ISO 4217, e.g., 'USD')
     * @param string $targetCurrency Target currency code (ISO 4217, e.g., 'MYR')
     * @param string $rate Exchange rate as string (to preserve precision)
     * @param DateTimeImmutable $capturedAt When the rate was captured
     * @param string|null $provider Provider of the rate (e.g., 'central_bank', 'xe')
     * @param string|null $rateType Type of rate (e.g., 'spot', 'forward', 'average')
     *
     * @throws \InvalidArgumentException If rate is invalid or currencies are not ISO 4217 format
     */
    public function __construct(
        public readonly string $sourceCurrency,
        public readonly string $targetCurrency,
        public readonly string $rate,
        public readonly DateTimeImmutable $capturedAt,
        public readonly ?string $provider = null,
        public readonly ?string $rateType = null,
    ) {
        // Validate currency codes (ISO 4217: 3 uppercase letters)
        if (!preg_match('/^[A-Z]{3}$/', $sourceCurrency)) {
            throw new \InvalidArgumentException(sprintf(
                'Source currency must be a valid ISO 4217 3-letter code, got: %s',
                $sourceCurrency
            ));
        }
        if (!preg_match('/^[A-Z]{3}$/', $targetCurrency)) {
            throw new \InvalidArgumentException(sprintf(
                'Target currency must be a valid ISO 4217 3-letter code, got: %s',
                $targetCurrency
            ));
        }

        // Validate rate: must be numeric first, then check if positive
        if (!is_numeric($rate)) {
            throw new \InvalidArgumentException('Exchange rate must be a numeric value');
        }
        if (bccomp($rate, '0', 10) <= 0) {
            throw new \InvalidArgumentException('Exchange rate must be a positive number');
        }
    }

    /**
     * Create a snapshot from a rate.
     */
    public static function capture(
        string $sourceCurrency,
        string $targetCurrency,
        string $rate,
        ?string $provider = null,
        ?string $rateType = null,
    ): self {
        return new self(
            sourceCurrency: $sourceCurrency,
            targetCurrency: $targetCurrency,
            rate: $rate,
            capturedAt: new DateTimeImmutable(),
            provider: $provider,
            rateType: $rateType,
        );
    }

    /**
     * Create a snapshot for same-currency (rate = 1).
     */
    public static function sameCurrency(string $currency): self
    {
        return new self(
            sourceCurrency: $currency,
            targetCurrency: $currency,
            rate: '1.000000',
            capturedAt: new DateTimeImmutable(),
            provider: 'system',
            rateType: 'identity',
        );
    }

    /**
     * Get the rate as a float.
     */
    public function getRateAsFloat(): float
    {
        return (float) $this->rate;
    }

    /**
     * Check if this is a same-currency snapshot.
     */
    public function isSameCurrency(): bool
    {
        return $this->sourceCurrency === $this->targetCurrency;
    }

    /**
     * Get the currency pair representation.
     */
    public function getCurrencyPair(): string
    {
        return "{$this->sourceCurrency}/{$this->targetCurrency}";
    }

    /**
     * Convert an amount using this snapshot rate.
     *
     * @param string $amount Amount to convert (in source currency)
     * @return string Converted amount (in target currency)
     */
    public function convert(string $amount): string
    {
        return bcmul($amount, $this->rate, 6);
    }

    /**
     * Convert back an amount using inverse rate.
     *
     * @param string $amount Amount to convert back (in target currency)
     * @return string Converted amount (in source currency)
     */
    public function convertBack(string $amount): string
    {
        // Note: This check is for internal consistency. The constructor validates
        // that rate > 0, so this branch should never execute in normal usage.
        // It exists as a safeguard against future modifications or reflection-based changes.
        if (bccomp($this->rate, '0', 10) === 0) {
            throw new \DivisionByZeroError('Cannot convert back with zero rate');
        }

        return bcdiv($amount, $this->rate, 6);
    }

    /**
     * Get the inverse rate.
     */
    public function getInverseRate(): string
    {
        // Note: This check is for internal consistency. The constructor validates
        // that rate > 0, so this branch should never execute in normal usage.
        // It exists as a safeguard against future modifications or reflection-based changes.
        if (bccomp($this->rate, '0', 10) === 0) {
            throw new \DivisionByZeroError('Cannot get inverse of zero rate');
        }

        return bcdiv('1', $this->rate, 10);
    }

    /**
     * Create inverse snapshot (swap currencies).
     */
    public function inverse(): self
    {
        return new self(
            sourceCurrency: $this->targetCurrency,
            targetCurrency: $this->sourceCurrency,
            rate: $this->getInverseRate(),
            capturedAt: $this->capturedAt,
            provider: $this->provider,
            rateType: $this->rateType,
        );
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_currency' => $this->sourceCurrency,
            'target_currency' => $this->targetCurrency,
            'rate' => $this->rate,
            'captured_at' => $this->capturedAt->format(DateTimeImmutable::ATOM),
            'provider' => $this->provider,
            'rate_type' => $this->rateType,
        ];
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sourceCurrency: $data['source_currency'],
            targetCurrency: $data['target_currency'],
            rate: $data['rate'],
            capturedAt: new DateTimeImmutable($data['captured_at']),
            provider: $data['provider'] ?? null,
            rateType: $data['rate_type'] ?? null,
        );
    }
}
