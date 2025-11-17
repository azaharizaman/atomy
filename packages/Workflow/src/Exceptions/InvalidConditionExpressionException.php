<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when condition expression is invalid.
 */
class InvalidConditionExpressionException extends \RuntimeException
{
    public static function syntax(string $expression, string $reason = ''): self
    {
        $message = "Invalid condition expression: '{$expression}'.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        return new self($message);
    }

    public static function unsupportedOperator(string $operator): self
    {
        return new self("Unsupported operator '{$operator}' in condition expression.");
    }
}
