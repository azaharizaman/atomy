<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

final class NormalizationIssueCode
{
    public const MISSING_PRICE = 'missing_price';
    public const MISSING_CURRENCY = 'missing_currency';
    public const AMBIGUOUS_MAPPING = 'ambiguous_mapping';
    public const INVALID_UOM = 'invalid_uom';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::MISSING_PRICE,
            self::MISSING_CURRENCY,
            self::AMBIGUOUS_MAPPING,
            self::INVALID_UOM,
        ];
    }
}
