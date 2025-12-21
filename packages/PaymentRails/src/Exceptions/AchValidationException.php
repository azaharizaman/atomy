<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

use Nexus\PaymentRails\Enums\SecCode;

/**
 * Exception thrown when ACH validation fails.
 */
final class AchValidationException extends PaymentRailException
{
    /**
     * @param string $message
     * @param array<string> $errors
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            railType: 'ACH',
            context: ['errors' => $this->errors],
            previous: $previous,
        );
    }

    /**
     * Get the validation errors.
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create for multiple validation errors.
     *
     * @param array<string> $errors
     */
    public static function multipleErrors(array $errors): self
    {
        $count = count($errors);
        return new self(
            message: "ACH validation failed with {$count} error(s)",
            errors: $errors,
        );
    }

    /**
     * Create for unbalanced batch.
     */
    public static function unbalancedBatch(int $totalDebits, int $totalCredits): self
    {
        $debitFormatted = number_format($totalDebits / 100, 2);
        $creditFormatted = number_format($totalCredits / 100, 2);

        return new self(
            message: "Batch is unbalanced: debits ({$debitFormatted}) do not equal credits ({$creditFormatted})",
            errors: ["Debits: {$debitFormatted}", "Credits: {$creditFormatted}"],
        );
    }

    /**
     * Create for invalid SEC code usage.
     */
    public static function invalidSecCode(SecCode $secCode, string $reason): self
    {
        return new self(
            message: "SEC code {$secCode->value} cannot be used: {$reason}",
            errors: [$reason],
        );
    }

    /**
     * Create for exceeds batch limit.
     */
    public static function exceedsBatchLimit(int $count, int $maximum): self
    {
        return new self(
            message: "Batch contains {$count} entries, exceeding maximum of {$maximum}",
            errors: ["Entry count: {$count}", "Maximum allowed: {$maximum}"],
        );
    }

    /**
     * Create for missing required field.
     */
    public static function missingRequiredField(string $fieldName): self
    {
        return new self(
            message: "Required ACH field '{$fieldName}' is missing",
            errors: ["Missing: {$fieldName}"],
        );
    }

    /**
     * Create for invalid field length.
     */
    public static function invalidFieldLength(string $fieldName, int $expected, int $actual): self
    {
        return new self(
            message: "ACH field '{$fieldName}' has invalid length: expected {$expected}, got {$actual}",
            errors: ["Field: {$fieldName}", "Expected: {$expected}", "Actual: {$actual}"],
        );
    }

    /**
     * Create for effective date in past.
     */
    public static function effectiveDateInPast(\DateTimeImmutable $date): self
    {
        $formatted = $date->format('Y-m-d');
        return new self(
            message: "Effective entry date {$formatted} is in the past",
            errors: ["Date: {$formatted}"],
        );
    }

    /**
     * Create for effective date too far in future.
     */
    public static function effectiveDateTooFar(\DateTimeImmutable $date, int $maxDays): self
    {
        $formatted = $date->format('Y-m-d');
        return new self(
            message: "Effective entry date {$formatted} is more than {$maxDays} days in the future",
            errors: ["Date: {$formatted}", "Maximum days ahead: {$maxDays}"],
        );
    }
}
