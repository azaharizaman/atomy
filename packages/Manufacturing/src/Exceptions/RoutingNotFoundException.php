<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when a routing cannot be found.
 */
class RoutingNotFoundException extends \RuntimeException
{
    public function __construct(
        string $message = 'Routing not found',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withId(string $id): self
    {
        return new self("Routing with ID '{$id}' not found");
    }

    public static function withProductId(string $productId): self
    {
        return new self("No routing found for product '{$productId}'");
    }

    public static function noEffectiveRouting(string $productId, string $date): self
    {
        return new self("No effective routing found for product '{$productId}' on {$date}");
    }
}
