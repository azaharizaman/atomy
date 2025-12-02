<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Exceptions;

/**
 * Exception for currency translation errors.
 */
final class CurrencyTranslationException extends ConsolidationException
{
    public function __construct(
        string $fromCurrency,
        string $toCurrency,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            "Currency translation error: {$fromCurrency} to {$toCurrency}",
            $code,
            $previous
        );
    }
}
