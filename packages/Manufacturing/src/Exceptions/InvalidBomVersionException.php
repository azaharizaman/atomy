<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when an invalid BOM version operation is attempted.
 */
class InvalidBomVersionException extends \RuntimeException
{
    public function __construct(
        string $message = 'Invalid BOM version operation',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function versionExists(string $productId, string $version): self
    {
        return new self("BOM version '{$version}' already exists for product '{$productId}'");
    }

    public static function cannotModify(string $bomId, string $status): self
    {
        return new self("Cannot modify BOM '{$bomId}' in '{$status}' status");
    }

    public static function cannotRelease(string $bomId, string $reason): self
    {
        return new self("Cannot release BOM '{$bomId}': {$reason}");
    }
}
