<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when all configured providers fail or are unavailable
 * 
 * This exception is raised after the fallback chain is exhausted:
 * - Primary provider failed
 * - Secondary provider (if configured) failed
 * - Rule-based provider failed
 * 
 * The exception includes the list of attempted providers and their failure reasons.
 * 
 * Example:
 * ```php
 * throw AllProvidersUnavailableException::forAttempts($attemptedProviders);
 * ```
 */
final class AllProvidersUnavailableException extends MachineLearningException
{
    /** @var array<string, string> Provider name => failure reason */
    private array $attemptedProviders;

    public function __construct(string $message, array $attemptedProviders)
    {
        parent::__construct($message);
        $this->attemptedProviders = $attemptedProviders;
    }

    public static function forAttempts(array $attemptedProviders): self
    {
        $providerList = implode(', ', array_keys($attemptedProviders));
        $message = sprintf(
            "All ML providers failed. Attempted: %s",
            $providerList
        );

        return new self($message, $attemptedProviders);
    }

    /**
     * @return array<string, string>
     */
    public function getAttemptedProviders(): array
    {
        return $this->attemptedProviders;
    }
}
