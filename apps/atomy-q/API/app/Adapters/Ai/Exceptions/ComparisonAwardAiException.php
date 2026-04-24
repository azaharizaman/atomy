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
            $encodedContext = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($encodedContext === false) {
                $message .= sprintf(
                    ' Context encode failed (%s): %s',
                    json_last_error_msg(),
                    var_export($context, true),
                );
            } else {
                $message .= ' Context: ' . $encodedContext;
            }
        }

        return new self($message, 0, $previous);
    }
}
