<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when a work center cannot be found.
 */
class WorkCenterNotFoundException extends \RuntimeException
{
    public function __construct(
        string $message = 'Work center not found',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withId(string $id): self
    {
        return new self("Work center with ID '{$id}' not found");
    }

    public static function withCode(string $code): self
    {
        return new self("Work center with code '{$code}' not found");
    }
}
