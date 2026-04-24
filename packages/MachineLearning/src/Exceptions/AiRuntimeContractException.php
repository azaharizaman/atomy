<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Exceptions;

final class AiRuntimeContractException extends MachineLearningException
{
    public static function unsupportedMode(string $subject): self
    {
        return new self(sprintf('Unsupported %s configuration.', $subject));
    }

    /**
     * @param list<string> $missingFields
     */
    public static function missingFields(string $subject, array $missingFields): self
    {
        if ($missingFields === []) {
            return new self(sprintf('%s is missing required fields.', $subject));
        }

        sort($missingFields);

        return new self(sprintf(
            '%s is missing required fields: %s.',
            $subject,
            implode(', ', $missingFields)
        ));
    }

    public static function invalidValue(string $subject): self
    {
        return new self(sprintf('Invalid %s configuration.', $subject));
    }
}
