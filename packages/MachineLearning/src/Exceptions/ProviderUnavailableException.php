<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when a provider is configured but unavailable (API down, network error, auth failure)
 * 
 * This exception is raised when attempting to use a provider that fails due to:
 * - API endpoint unreachable (network error, DNS failure)
 * - API authentication failure (invalid API key, expired token)
 * - API rate limiting (quota exceeded)
 * - Service unavailable (503 errors, maintenance)
 * 
 * The exception includes the provider name and underlying error for debugging.
 * 
 * Example:
 * ```php
 * throw ProviderUnavailableException::forProvider('openai', $previousException);
 * ```
 */
final class ProviderUnavailableException extends MachineLearningException
{
    private string $providerName;

    public function __construct(string $message, string $providerName, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->providerName = $providerName;
    }

    public static function forProvider(string $providerName, \Throwable $cause): self
    {
        return new self(
            sprintf(
                "ML provider '%s' is currently unavailable: %s",
                $providerName,
                $cause->getMessage()
            ),
            $providerName,
            $cause
        );
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }
}
