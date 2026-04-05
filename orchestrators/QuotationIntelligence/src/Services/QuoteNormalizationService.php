<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\QuoteNormalizationServiceInterface;
use Nexus\QuotationIntelligence\Exceptions\UomNormalizationException;
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Currency\Contracts\ExchangeRateProviderInterface;
use Nexus\Currency\ValueObjects\CurrencyPair;
use Nexus\Finance\ValueObjects\ExchangeRate;
use Psr\Log\LoggerInterface;

if (!class_exists(ExchangeRate::class)) {
    require_once __DIR__ . '/../Support/ExchangeRate.php';
}

/**
 * Service for deep normalization of Units of Measure (UoM) and Currencies.
 */
final readonly class QuoteNormalizationService implements QuoteNormalizationServiceInterface
{
    public function __construct(
        private UomConversionEngine $uomEngine,
        private ExchangeRateProviderInterface $rateProvider,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function normalizeQuantity(float $quantity, string $fromUnit, string $toUnit): float
    {
        if ($fromUnit === $toUnit) {
            return $quantity;
        }

        $this->logger->debug('Normalizing quantity', [
            'quantity' => $quantity,
            'from' => $fromUnit,
            'to' => $toUnit,
        ]);

        try {
            // Use Nexus\Uom to convert units (e.g., 'Box of 12' -> 'Each')
            return $this->uomEngine->convert($quantity, $fromUnit, $toUnit);
        } catch (\Exception $e) {
            $this->logger->error('UoM normalization failed', [
                'from' => $fromUnit,
                'to' => $toUnit,
                'error' => $e->getMessage(),
            ]);
            throw new UomNormalizationException("Cannot convert {$fromUnit} to {$toUnit}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function normalizePrice(
        float $unitPrice,
        string $fromCurrency,
        string $toCurrency,
        ?\DateTimeImmutable $lockDate = null
    ): float {
        if ($fromCurrency === $toCurrency) {
            return $unitPrice;
        }

        $this->logger->debug('Normalizing price', [
            'price' => $unitPrice,
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'lock_date' => $lockDate?->format('Y-m-d'),
        ]);

        // 1. Get exchange rate
        $pair = new CurrencyPair($fromCurrency, $toCurrency);
        $rate = $this->rateProvider->getRate($pair, $lockDate);

        // 2. Calculate normalized price
        $normalizedPrice = $unitPrice * $rate->getRate();

        return (float)$normalizedPrice;
    }
}
