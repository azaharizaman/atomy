<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\CheckStatus;
use Nexus\PaymentRails\ValueObjects\Check;
use Nexus\PaymentRails\ValueObjects\CheckNumber;

/**
 * Result DTO for check operations.
 */
final readonly class CheckResult
{
    /**
     * @param string $checkId Unique check identifier
     * @param bool $success Whether the operation was successful
     * @param CheckStatus $status Current check status
     * @param CheckNumber|null $checkNumber Assigned check number
     * @param Money $amount Check amount
     * @param string $payeeName Payee name
     * @param array<string> $errors Any errors encountered
     * @param string|null $positivePayReference Positive Pay file reference
     * @param string|null $trackingNumber Mailing tracking number
     * @param \DateTimeImmutable $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $printedAt When printed
     * @param \DateTimeImmutable|null $mailedAt When mailed
     * @param \DateTimeImmutable|null $expectedDeliveryDate Expected delivery date
     */
    public function __construct(
        public string $checkId,
        public bool $success,
        public CheckStatus $status,
        public ?CheckNumber $checkNumber,
        public Money $amount,
        public string $payeeName,
        public array $errors = [],
        public ?string $positivePayReference = null,
        public ?string $trackingNumber = null,
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $printedAt = null,
        public ?\DateTimeImmutable $mailedAt = null,
        public ?\DateTimeImmutable $expectedDeliveryDate = null,
    ) {}

    /**
     * Create a successful result from a check.
     */
    public static function success(Check $check): self
    {
        return new self(
            checkId: $check->id,
            success: true,
            status: $check->status,
            checkNumber: $check->checkNumber,
            amount: $check->amount,
            payeeName: $check->payeeName,
            createdAt: $check->checkDate,
            printedAt: $check->printedAt,
            mailedAt: $check->mailedAt,
        );
    }

    /**
     * Create a failure result.
     *
     * @param array<string> $errors
     */
    public static function failure(
        string $checkId,
        Money $amount,
        string $payeeName,
        array $errors,
    ): self {
        return new self(
            checkId: $checkId,
            success: false,
            status: CheckStatus::VOIDED,
            checkNumber: null,
            amount: $amount,
            payeeName: $payeeName,
            errors: $errors,
        );
    }

    /**
     * Get the formatted check number.
     */
    public function getFormattedCheckNumber(): string
    {
        return $this->checkNumber?->formatted() ?? '';
    }

    /**
     * Check if the check is mailed.
     */
    public function isMailed(): bool
    {
        return $this->mailedAt !== null;
    }

    /**
     * Check if the check is printed.
     */
    public function isPrinted(): bool
    {
        return $this->printedAt !== null;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
