<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when a BOM cannot be found.
 */
class BomNotFoundException extends \RuntimeException
{
    public function __construct(
        string $message = 'Bill of Materials not found',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withId(string $id): self
    {
        return new self("Bill of Materials with ID '{$id}' not found");
    }

    public static function withProductId(string $productId): self
    {
        return new self("No BOM found for product '{$productId}'");
    }

    public static function noEffectiveBom(string $productId, string $date): self
    {
        return new self("No effective BOM found for product '{$productId}' on {$date}");
    }
}
