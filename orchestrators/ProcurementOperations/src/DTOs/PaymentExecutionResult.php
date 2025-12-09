<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\PaymentMethod;

/**
 * Result of payment execution.
 */
final readonly class PaymentExecutionResult
{
    /**
     * @param bool $success Whether payment was executed successfully
     * @param string|null $paymentId Generated payment ID
     * @param string|null $transactionReference External transaction reference
     * @param PaymentMethod|null $methodUsed Actual payment method used
     * @param Money|null $amountPaid Amount that was paid
     * @param Money|null $feeAmount Fee charged for this payment
     * @param \DateTimeImmutable|null $expectedClearingDate Expected clearing date
     * @param string $message Status message
     * @param array<string> $errors List of errors if failed
     * @param array<string, mixed> $metadata Additional response metadata
     */
    public function __construct(
        public bool $success,
        public ?string $paymentId = null,
        public ?string $transactionReference = null,
        public ?PaymentMethod $methodUsed = null,
        public ?Money $amountPaid = null,
        public ?Money $feeAmount = null,
        public ?\DateTimeImmutable $expectedClearingDate = null,
        public string $message = '',
        public array $errors = [],
        public array $metadata = [],
    ) {}

    /**
     * Create successful result.
     */
    public static function success(
        string $paymentId,
        string $transactionReference,
        PaymentMethod $methodUsed,
        Money $amountPaid,
        Money $feeAmount,
        \DateTimeImmutable $expectedClearingDate,
        string $message = 'Payment executed successfully',
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            paymentId: $paymentId,
            transactionReference: $transactionReference,
            methodUsed: $methodUsed,
            amountPaid: $amountPaid,
            feeAmount: $feeAmount,
            expectedClearingDate: $expectedClearingDate,
            message: $message,
            metadata: $metadata,
        );
    }

    /**
     * Create failed result.
     *
     * @param array<string> $errors
     */
    public static function failure(string $message, array $errors = []): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors,
        );
    }
}
