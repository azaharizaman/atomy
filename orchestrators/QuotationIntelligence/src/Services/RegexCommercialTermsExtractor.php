<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\CommercialTermsExtractorInterface;

/**
 * Regex-based commercial terms extractor for common procurement phrasing.
 */
final readonly class RegexCommercialTermsExtractor implements CommercialTermsExtractorInterface
{
    /**
     * @inheritDoc
     */
    public function extract(string $text): array
    {
        $upper = strtoupper($text);

        return [
            'incoterm' => $this->extractIncoterm($upper),
            'payment_days' => $this->extractPaymentDays($upper),
            'lead_time_days' => $this->extractLeadTimeDays($upper),
            'warranty_months' => $this->extractWarrantyMonths($upper),
        ];
    }

    private function extractIncoterm(string $text): ?string
    {
        if (preg_match('/\b(EXW|FOB|CIF|DAP|DDP|FCA|CFR)\b/', $text, $match) === 1) {
            return $match[1];
        }

        if (str_contains($text, 'EX-WORKS')) {
            return 'EXW';
        }

        return null;
    }

    private function extractPaymentDays(string $text): ?int
    {
        if (preg_match('/\bNET[\s\-]?(\d{1,3})\b/', $text, $match) === 1) {
            return (int)$match[1];
        }

        if (preg_match('/\bPAYMENT\s+(\d{1,3})\s+DAYS\b/', $text, $match) === 1) {
            return (int)$match[1];
        }

        return null;
    }

    private function extractLeadTimeDays(string $text): ?int
    {
        if (preg_match('/\bLEAD\s*TIME[:\s]+(\d{1,3})\s*DAYS?\b/', $text, $match) === 1) {
            return (int)$match[1];
        }

        return null;
    }

    private function extractWarrantyMonths(string $text): ?int
    {
        if (preg_match('/\bWARRANTY[:\s]+(\d{1,3})\s*MONTHS?\b/', $text, $match) === 1) {
            return (int)$match[1];
        }

        if (preg_match('/\bWARRANTY[:\s]+(\d{1,2})\s*YEARS?\b/', $text, $match) === 1) {
            return (int)$match[1] * 12;
        }

        return null;
    }
}

