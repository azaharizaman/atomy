<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when a work order cannot be found.
 */
class WorkOrderNotFoundException extends \RuntimeException
{
    public function __construct(
        string $message = 'Work order not found',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withId(string $id): self
    {
        return new self("Work order with ID '{$id}' not found");
    }

    public static function withNumber(string $number): self
    {
        return new self("Work order with number '{$number}' not found");
    }
}
