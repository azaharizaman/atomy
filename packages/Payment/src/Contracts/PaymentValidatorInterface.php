<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;

/**
 * Contract for payment validation operations.
 *
 * Provides validation methods for payment creation, execution,
 * cancellation, and reversal operations.
 */
interface PaymentValidatorInterface
{
    /**
     * Validate payment creation data.
     *
     * @param array<string, mixed> $data Payment creation data
     *
     * @throws \Nexus\Payment\Exceptions\PaymentValidationException If validation fails
     */
    public function validateCreate(array $data): void;

    /**
     * Validate payment amount.
     *
     * @throws \Nexus\Payment\Exceptions\PaymentValidationException If amount is invalid
     */
    public function validateAmount(Money $amount): void;

    /**
     * Validate payment reference.
     *
     * @throws \Nexus\Payment\Exceptions\PaymentValidationException If reference is invalid
     */
    public function validateReference(PaymentReference $reference): void;

    /**
     * Validate payment method.
     *
     * @throws \Nexus\Payment\Exceptions\PaymentValidationException If payment method is invalid
     */
    public function validatePaymentMethod(
        PaymentMethodInterface $paymentMethod,
        Money $amount
    ): void;

    /**
     * Validate payment is ready for execution.
     *
     * @throws \Nexus\Payment\Exceptions\PaymentStateException If payment cannot be executed
     */
    public function validateForExecution(PaymentTransactionInterface $payment): void;

    /**
     * Validate payment is ready for cancellation.
     *
     * @throws \Nexus\Payment\Exceptions\PaymentStateException If payment cannot be cancelled
     */
    public function validateForCancellation(PaymentTransactionInterface $payment): void;

    /**
     * Validate payment is ready for reversal.
     *
     * @throws \Nexus\Payment\Exceptions\PaymentStateException If payment cannot be reversed
     */
    public function validateForReversal(PaymentTransactionInterface $payment): void;

    /**
     * Validate sufficient funds for payment.
     *
     * @throws \Nexus\Payment\Exceptions\PaymentValidationException If insufficient funds
     */
    public function validateSufficientFunds(
        Money $requested,
        Money $available
    ): void;

    /**
     * Validate idempotency key format.
     *
     * @throws \Nexus\Payment\Exceptions\PaymentValidationException If key is invalid
     */
    public function validateIdempotencyKey(IdempotencyKey $key): void;
}
