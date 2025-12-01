<?php

declare(strict_types=1);

namespace Nexus\FinancialRatios\Exceptions;

/**
 * Exception thrown when there is insufficient data to calculate a ratio
 */
final class InsufficientDataException extends \RuntimeException
{
    /**
     * @param string $ratioName The name of the ratio that couldn't be calculated
     * @param array<string> $missingFields The fields that are missing
     */
    public function __construct(
        public readonly string $ratioName,
        public readonly array $missingFields = [],
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        if ($message === '') {
            $fieldsStr = implode(', ', $missingFields);
            $message = "Insufficient data to calculate {$ratioName}. Missing fields: {$fieldsStr}";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for missing required field
     */
    public static function forMissingField(string $ratioName, string $fieldName): self
    {
        return new self(
            ratioName: $ratioName,
            missingFields: [$fieldName],
            message: "Cannot calculate {$ratioName}: {$fieldName} is required but not provided"
        );
    }

    /**
     * Create exception for multiple missing fields
     *
     * @param array<string> $fields
     */
    public static function forMissingFields(string $ratioName, array $fields): self
    {
        return new self(
            ratioName: $ratioName,
            missingFields: $fields
        );
    }

    /**
     * Create exception for insufficient historical data
     */
    public static function forInsufficientHistory(string $ratioName, int $required, int $provided): self
    {
        return new self(
            ratioName: $ratioName,
            missingFields: [],
            message: "Cannot calculate {$ratioName}: requires {$required} periods of data but only {$provided} provided"
        );
    }
}
