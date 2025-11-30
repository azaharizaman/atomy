<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

/**
 * Exception thrown when attempting fine-tuning on a provider that doesn't support it
 * 
 * This exception is raised when calling submitFineTuningJob() on a provider
 * that has supportsFineTuning() returning false.
 * 
 * Example:
 * ```php
 * throw FineTuningNotSupportedException::forProvider('rule_based');
 * ```
 */
final class FineTuningNotSupportedException extends MachineLearningException
{
    public static function forProvider(string $providerName): self
    {
        return new self(
            sprintf(
                "Provider '%s' does not support fine-tuning. " .
                "Fine-tuning is only available on external AI providers (OpenAI, Anthropic, Gemini).",
                $providerName
            )
        );
    }
}
