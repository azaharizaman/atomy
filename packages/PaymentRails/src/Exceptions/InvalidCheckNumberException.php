<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Exceptions;

/**
 * Exception thrown when a check number is invalid.
 */
final class InvalidCheckNumberException extends PaymentRailException
{
    /**
     * @param string $checkNumber
     * @param string $reason
     * @param \Throwable|null $previous
     */
    public function __construct(
        private readonly string $checkNumber,
        string $reason = 'Invalid format',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Invalid check number '{$this->checkNumber}': {$reason}",
            railType: 'CHECK',
            context: [
                'check_number' => $this->checkNumber,
                'reason' => $reason,
            ],
            previous: $previous,
        );
    }

    /**
     * Get the check number.
     */
    public function getCheckNumber(): string
    {
        return $this->checkNumber;
    }

    /**
     * Create for non-numeric value.
     */
    public static function nonNumeric(string $checkNumber): self
    {
        return new self($checkNumber, 'Check number must contain only digits');
    }

    /**
     * Create for value too large.
     */
    public static function exceedsMaximum(string $checkNumber, int $maximum): self
    {
        return new self($checkNumber, "Check number exceeds maximum value of {$maximum}");
    }

    /**
     * Create for negative value.
     */
    public static function negativeValue(string $checkNumber): self
    {
        return new self($checkNumber, 'Check number cannot be negative');
    }

    /**
     * Create for duplicate check number.
     */
    public static function duplicate(string $checkNumber): self
    {
        return new self($checkNumber, 'Check number has already been used');
    }

    /**
     * Create for out of sequence.
     */
    public static function outOfSequence(string $checkNumber, string $expectedRange): self
    {
        return new self(
            $checkNumber,
            "Check number is out of expected sequence. Expected: {$expectedRange}"
        );
    }
}
