<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when no provider is configured for a specific domain/task combination
 * 
 * This exception is raised by ProviderStrategyInterface::selectProvider() when attempting
 * to get a provider for a domain that has no configured provider in settings.
 * 
 * Example:
 * ```php
 * throw new ProviderNotFoundException(
 *     "No ML provider configured for domain 'procurement' and task 'anomaly_detection'"
 * );
 * ```
 */
final class ProviderNotFoundException extends MachineLearningException
{
    public static function forDomainAndTask(string $domain, string $taskType): self
    {
        return new self(
            sprintf(
                "No ML provider configured for domain '%s' and task '%s'. " .
                "Configure via setting 'ml.provider.%s' or 'ml.provider.fallback'",
                $domain,
                $taskType,
                $domain
            )
        );
    }

    public static function forDomain(string $domain, string $providerName): self
    {
        return new self(
            sprintf(
                "Provider '%s' is configured for domain '%s' but is not available. " .
                "Ensure the provider is registered in the available providers map.",
                $providerName,
                $domain
            )
        );
    }
}
