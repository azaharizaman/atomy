<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when an invalid routing version operation is attempted.
 */
class InvalidRoutingVersionException extends \RuntimeException
{
    public function __construct(
        string $message = 'Invalid routing version operation',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function versionExists(string $productId, string $version): self
    {
        return new self("Routing version '{$version}' already exists for product '{$productId}'");
    }

    public static function cannotModify(string $routingId, string $status): self
    {
        return new self("Cannot modify routing '{$routingId}' in '{$status}' status");
    }

    public static function cannotRelease(string $routingId, string $reason): self
    {
        return new self("Cannot release routing '{$routingId}': {$reason}");
    }
}
