<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Contracts;

use Nexus\AccountConsolidation\ValueObjects\TranslationAdjustment;

/**
 * Contract for currency translation in consolidation.
 */
interface CurrencyTranslatorInterface
{
    /**
     * Translate financial data from one currency to another.
     *
     * @param array<string, mixed> $financialData
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param \DateTimeImmutable $asOfDate
     * @return array<string, mixed>
     */
    public function translate(
        array $financialData,
        string $fromCurrency,
        string $toCurrency,
        \DateTimeImmutable $asOfDate
    ): array;

    /**
     * Calculate translation adjustment.
     *
     * @param array<string, mixed> $financialData
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param \DateTimeImmutable $asOfDate
     * @return TranslationAdjustment
     */
    public function calculateAdjustment(
        array $financialData,
        string $fromCurrency,
        string $toCurrency,
        \DateTimeImmutable $asOfDate
    ): TranslationAdjustment;
}
