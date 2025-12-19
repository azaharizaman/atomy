<?php

declare(strict_types=1);

namespace Nexus\Payment\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;
use Nexus\Payment\Contracts\PaymentMethodInterface;
use Nexus\Payment\Contracts\PaymentValidatorInterface;
use Nexus\Payment\Contracts\PaymentTransactionInterface;
use Nexus\Payment\Exceptions\InsufficientFundsException;
use Nexus\Payment\Exceptions\PaymentValidationException;
use Nexus\Payment\Exceptions\InvalidPaymentMethodException;
use Nexus\Payment\Exceptions\InvalidPaymentStatusException;

/**
 * Payment Validator - Validates payment transactions.
 *
 * This service encapsulates all payment validation rules.
 */
final readonly class PaymentValidator implements PaymentValidatorInterface
{
    /**
     * Minimum payment amount.
     */
    private const string MIN_AMOUNT = '0.01';

    /**
     * Maximum payment amount (10 million).
     */
    private const string MAX_AMOUNT = '10000000.00';

    /**
     * Validate payment creation.
     *
     * @param array<string, mixed> $data
     * @throws PaymentValidationException
     */
    public function validateCreate(array $data): void
    {
        $errors = [];

        // Required fields
        $requiredFields = ['tenant_id', 'direction', 'amount', 'method_type'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[] = "Field '{$field}' is required";
            }
        }

        if (!empty($errors)) {
            throw new PaymentValidationException(implode('; ', $errors));
        }

        // Validate amount
        $this->validateAmount($data['amount']);

        // Validate direction
        if (!$data['direction'] instanceof PaymentDirection) {
            throw new PaymentValidationException(
                'Direction must be a PaymentDirection enum'
            );
        }

        // Validate method type
        if (!$data['method_type'] instanceof PaymentMethodType) {
            throw new PaymentValidationException(
                'Method type must be a PaymentMethodType enum'
            );
        }

        // Validate payer/payee based on direction
        $this->validateParties($data);
    }

    /**
     * Validate payment amount.
     *
     * @throws PaymentValidationException
     */
    public function validateAmount(Money $amount): void
    {
        if (!$amount->isPositive()) {
            throw new PaymentValidationException(
                'Payment amount must be positive'
            );
        }

        $minAmount = Money::of(self::MIN_AMOUNT, $amount->getCurrency());
        if ($amount->lessThan($minAmount)) {
            throw new PaymentValidationException(
                "Payment amount must be at least {$minAmount->format()}"
            );
        }

        $maxAmount = Money::of(self::MAX_AMOUNT, $amount->getCurrency());
        if ($amount->greaterThan($maxAmount)) {
            throw new PaymentValidationException(
                "Payment amount cannot exceed {$maxAmount->format()}"
            );
        }
    }

    /**
     * Validate payment reference.
     *
     * @throws PaymentValidationException
     */
    public function validateReference(PaymentReference $reference): void
    {
        if (mb_strlen($reference->value) < 3) {
            throw new PaymentValidationException(
                'Payment reference must be at least 3 characters'
            );
        }

        if (mb_strlen($reference->value) > 50) {
            throw new PaymentValidationException(
                'Payment reference cannot exceed 50 characters'
            );
        }
    }

    /**
     * Validate payment method can be used.
     *
     * @throws InvalidPaymentMethodException
     */
    public function validatePaymentMethod(
        PaymentMethodInterface $method,
        Money $amount,
    ): void {
        if (!$method->isActive() || $method->isExpired()) {
            throw new InvalidPaymentMethodException(
                'Payment method is not active or has expired'
            );
        }

        if (!$method->isVerified()) {
            throw new InvalidPaymentMethodException(
                'Payment method has not been verified'
            );
        }

        // Check if method type supports the transaction
        if (!$method->getType()->supportsRefund() && $amount->isNegative()) {
            throw new InvalidPaymentMethodException(
                "Payment method type {$method->getType()->value} does not support refunds"
            );
        }
    }

    /**
     * Validate payment can be executed.
     *
     * @throws InvalidPaymentStatusException
     */
    public function validateForExecution(PaymentTransactionInterface $payment): void
    {
        if ($payment->getStatus() !== PaymentStatus::PENDING) {
            throw new InvalidPaymentStatusException(
                $payment->getStatus(),
                PaymentStatus::PENDING,
                'Payment must be in PENDING status to execute'
            );
        }
    }

    /**
     * Validate payment can be cancelled.
     *
     * @throws InvalidPaymentStatusException
     */
    public function validateForCancellation(PaymentTransactionInterface $payment): void
    {
        if (!$payment->canBeCancelled()) {
            throw new InvalidPaymentStatusException(
                $payment->getStatus(),
                PaymentStatus::CANCELLED,
                "Payment in status {$payment->getStatus()->value} cannot be cancelled"
            );
        }
    }

    /**
     * Validate payment can be reversed.
     *
     * @throws InvalidPaymentStatusException
     */
    public function validateForReversal(PaymentTransactionInterface $payment): void
    {
        if (!$payment->canBeReversed()) {
            throw new InvalidPaymentStatusException(
                $payment->getStatus(),
                PaymentStatus::REVERSED,
                "Payment in status {$payment->getStatus()->value} cannot be reversed"
            );
        }

        // Check if method type supports reversal/refund
        if (!$payment->getMethodType()->supportsRefund()) {
            throw new InvalidPaymentMethodException(
                "Payment method type {$payment->getMethodType()->value} does not support reversals"
            );
        }
    }

    /**
     * Validate sufficient funds for payment.
     *
     * @throws InsufficientFundsException
     */
    public function validateSufficientFunds(
        Money $requiredAmount,
        Money $availableAmount,
    ): void {
        if ($requiredAmount->greaterThan($availableAmount)) {
            throw new InsufficientFundsException(
                $requiredAmount,
                $availableAmount
            );
        }
    }

    /**
     * Validate idempotency key.
     *
     * @throws PaymentValidationException
     */
    public function validateIdempotencyKey(IdempotencyKey $key): void
    {
        if ($key->isExpired()) {
            throw new PaymentValidationException(
                'Idempotency key has expired'
            );
        }
    }

    /**
     * Validate parties (payer/payee) based on direction.
     *
     * @param array<string, mixed> $data
     * @throws PaymentValidationException
     */
    private function validateParties(array $data): void
    {
        $direction = $data['direction'];

        if ($direction === PaymentDirection::INBOUND) {
            // For inbound payments, payer is required
            if (!isset($data['payer_id']) || $data['payer_id'] === '') {
                throw new PaymentValidationException(
                    'Payer ID is required for inbound payments'
                );
            }
        } elseif ($direction === PaymentDirection::OUTBOUND) {
            // For outbound payments, payee is required
            if (!isset($data['payee_id']) || $data['payee_id'] === '') {
                throw new PaymentValidationException(
                    'Payee ID is required for outbound payments'
                );
            }
        }
    }
}
