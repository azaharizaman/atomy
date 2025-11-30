<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when forecast generation fails.
 */
class ForecastUnavailableException extends \RuntimeException
{
    public function __construct(
        string $message = 'Forecast unavailable',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function mlServiceUnavailable(): self
    {
        return new self('ML forecast service is unavailable');
    }

    public static function noProviderAvailable(string $productId): self
    {
        return new self("No forecast provider available for product '{$productId}'");
    }

    public static function insufficientHistory(string $productId, int $available, int $required): self
    {
        return new self(
            "Insufficient historical data for product '{$productId}'. " .
            "Available: {$available} periods, Required: {$required} periods"
        );
    }

    public static function productNotFound(string $productId): self
    {
        return new self("Cannot generate forecast: Product '{$productId}' not found");
    }

    public static function modelNotTrained(string $productId): self
    {
        return new self("No trained model available for product '{$productId}'");
    }

    public static function configurationError(string $message): self
    {
        return new self("Forecast configuration error: {$message}");
    }
}
