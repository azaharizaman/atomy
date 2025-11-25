<?php

declare(strict_types=1);

namespace Nexus\Manufacturing\Exceptions;

/**
 * Exception thrown when a change order cannot be found.
 */
class ChangeOrderNotFoundException extends \RuntimeException
{
    public function __construct(
        string $message = 'Change order not found',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withId(string $id): self
    {
        return new self("Change order with ID '{$id}' not found");
    }

    public static function withNumber(string $number): self
    {
        return new self("Change order with number '{$number}' not found");
    }
}
