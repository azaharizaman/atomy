<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Services;

use Nexus\AccountConsolidation\Contracts\CurrencyTranslatorInterface;
use Nexus\AccountConsolidation\Enums\TranslationMethod;
use Nexus\AccountConsolidation\ValueObjects\TranslationAdjustment;

/**
 * Pure logic for currency translation.
 */
final readonly class CurrencyTranslator implements CurrencyTranslatorInterface
{
    public function translate(
        array $financialData,
        string $fromCurrency,
        string $toCurrency,
        \DateTimeImmutable $asOfDate
    ): array {
        // This is a stub - actual rate would come from data provider
        $rate = 1.0;
        
        $translated = [];
        foreach ($financialData as $key => $value) {
            if (is_numeric($value)) {
                $translated[$key] = (float) $value * $rate;
            } else {
                $translated[$key] = $value;
            }
        }

        return $translated;
    }

    public function calculateAdjustment(
        array $financialData,
        string $fromCurrency,
        string $toCurrency,
        \DateTimeImmutable $asOfDate
    ): TranslationAdjustment {
        $originalTotal = $this->calculateTotal($financialData);
        $translatedData = $this->translate($financialData, $fromCurrency, $toCurrency, $asOfDate);
        $translatedTotal = $this->calculateTotal($translatedData);

        $adjustmentAmount = $translatedTotal - $originalTotal;

        return new TranslationAdjustment(
            entityId: $financialData['entity_id'] ?? 'unknown',
            fromCurrency: $fromCurrency,
            toCurrency: $toCurrency,
            amount: $adjustmentAmount,
            asOfDate: $asOfDate
        );
    }

    private function calculateTotal(array $data): float
    {
        $total = 0.0;
        foreach ($data as $value) {
            if (is_numeric($value)) {
                $total += (float) $value;
            }
        }
        return $total;
    }
}
