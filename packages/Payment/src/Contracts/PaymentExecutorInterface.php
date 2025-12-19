<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Payment\ValueObjects\ExecutionContext;
use Nexus\Payment\ValueObjects\PaymentResult;

/**
 * Contract for payment execution.
 *
 * Defines the interface that payment gateway adapters must implement.
 * This is an abstraction for the actual payment processing - concrete
 * implementations live in extension packages (PaymentGateway, PaymentBank, etc.).
 */
interface PaymentExecutorInterface
{
    /**
     * Execute a payment transaction.
     *
     * @param PaymentTransactionInterface $payment The payment to execute
     * @param ExecutionContext|null $context Execution context for auditing
     * @return PaymentResult The execution result
     */
    public function execute(
        PaymentTransactionInterface $payment,
        ?ExecutionContext $context = null,
    ): PaymentResult;

    /**
     * Capture a previously authorized payment.
     *
     * @param string $paymentId The payment ID to capture
     * @param float|null $amount Optional partial capture amount
     * @return PaymentResult The capture result
     */
    public function capture(string $paymentId, ?float $amount = null): PaymentResult;

    /**
     * Cancel/void a pending payment.
     *
     * @param string $paymentId The payment ID to cancel
     * @param string|null $reason Cancellation reason
     * @return PaymentResult The cancellation result
     */
    public function cancel(string $paymentId, ?string $reason = null): PaymentResult;

    /**
     * Refund a completed payment.
     *
     * @param string $paymentId The payment ID to refund
     * @param float|null $amount Optional partial refund amount
     * @param string|null $reason Refund reason
     * @return PaymentResult The refund result
     */
    public function refund(string $paymentId, ?float $amount = null, ?string $reason = null): PaymentResult;

    /**
     * Get the current status of a payment from the provider.
     *
     * @param string $paymentId The payment ID to check
     * @return PaymentResult Current status information
     */
    public function getStatus(string $paymentId): PaymentResult;

    /**
     * Check if this executor supports the given payment method type.
     */
    public function supportsMethodType(string $methodType): bool;

    /**
     * Get the executor's identifier/name.
     */
    public function getName(): string;
}
