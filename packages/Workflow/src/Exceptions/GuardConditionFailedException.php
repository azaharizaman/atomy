<?php

declare(strict_types=1);

namespace Nexus\Workflow\Exceptions;

/**
 * Thrown when a transition guard condition fails.
 */
class GuardConditionFailedException extends \RuntimeException
{
    public static function forTransition(string $transition, string $reason = ''): self
    {
        $message = "Guard condition failed for transition '{$transition}'.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }
        return new self($message);
    }
}
