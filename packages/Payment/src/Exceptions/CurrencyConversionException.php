<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

/**
 * Thrown when currency conversion fails.
 */
class CurrencyConversionException extends PaymentException
{
    public function __construct(
        public readonly string $sourceCurrency,
        public readonly string $targetCurrency,
        ?string $message = null,
        ?\Throwable $previous = null
    ) {
        $message ??= sprintf(
            'Failed to convert from %s to %s',
            $sourceCurrency,
            $targetCurrency
        );

        parent::__construct($message, 0, $previous);
    }

    /**
     * Create exception for unsupported currency pair.
     */
    public static function unsupportedPair(string $source, string $target): self
    {
        return new self(
            $source,
            $target,
            sprintf('Currency pair %s/%s is not supported', $source, $target)
        );
    }

    /**
     * Create exception for unavailable rate.
     */
    public static function rateUnavailable(string $source, string $target): self
    {
        return new self(
            $source,
            $target,
            sprintf('Exchange rate for %s/%s is not available', $source, $target)
        );
    }
}
