<?php

declare(strict_types=1);

namespace App\Adapters\Ai\Exceptions;

use RuntimeException;

final class ComparisonAwardAiException extends RuntimeException
{
    /**
     * @param array<string, mixed> $context
     */
    public static function fromThrowable(\Throwable $previous, string $action, array $context = []): self
    {
        $message = sprintf('ComparisonAward AI operation "%s" failed.', $action);
        if ($context !== []) {
            $message .= ' Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return new self($message, 0, $previous);
    }
}
