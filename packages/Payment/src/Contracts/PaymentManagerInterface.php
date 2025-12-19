<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\ValueObjects\ExecutionContext;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;
use Nexus\Payment\ValueObjects\PaymentResult;

/**
 * Contract for payment manager service.
 *
 * Core service for creating, managing, and processing payments.
 */
interface PaymentManagerInterface
{
    /**
     * Create a new payment transaction.
     *
     * @param string $tenantId Tenant ID
     * @param PaymentReference $reference Payment reference
     * @param PaymentDirection $direction Payment direction
     * @param Money $amount Payment amount
     * @param PaymentMethodType $methodType Payment method type
     * @param string|null $payerId Payer party ID
     * @param string|null $payeeId Payee party ID
     * @param IdempotencyKey|null $idempotencyKey Idempotency key for deduplication
     * @param ExecutionContext|null $context Execution context
     * @param array<string, mixed>|null $metadata Additional metadata
     * @return PaymentTransactionInterface The created payment
     */
    public function create(
        string $tenantId,
        PaymentReference $reference,
        PaymentDirection $direction,
        Money $amount,
        PaymentMethodType $methodType,
        ?string $payerId = null,
        ?string $payeeId = null,
        ?IdempotencyKey $idempotencyKey = null,
        ?ExecutionContext $context = null,
        ?array $metadata = null,
    ): PaymentTransactionInterface;

    /**
     * Execute a pending payment.
     *
     * @param string $paymentId Payment ID
     * @param PaymentExecutorInterface|null $executor Payment executor to use
     * @return PaymentResult Execution result
     */
    public function execute(
        string $paymentId,
        ?PaymentExecutorInterface $executor = null,
    ): PaymentResult;

    /**
     * Cancel a pending payment.
     *
     * @param string $paymentId Payment ID
     * @param string $reason Cancellation reason
     * @param string|null $cancelledBy User who cancelled
     * @return PaymentTransactionInterface Updated payment
     */
    public function cancel(
        string $paymentId,
        string $reason,
        ?string $cancelledBy = null,
    ): PaymentTransactionInterface;

    /**
     * Reverse a completed payment (full or partial refund).
     *
     * @param string $paymentId Payment ID
     * @param Money|null $amount Reversal amount (null for full reversal)
     * @param string|null $reason Reversal reason
     * @param PaymentExecutorInterface|null $executor Payment executor to use
     * @return PaymentResult Reversal result
     */
    public function reverse(
        string $paymentId,
        ?Money $amount = null,
        ?string $reason = null,
        ?PaymentExecutorInterface $executor = null,
    ): PaymentResult;

    /**
     * Find a payment by ID or throw exception.
     *
     * @param string $paymentId Payment ID
     * @return PaymentTransactionInterface Payment transaction
     * @throws \Nexus\Payment\Exceptions\PaymentNotFoundException
     */
    public function findOrFail(string $paymentId): PaymentTransactionInterface;

    /**
     * Get the status of a payment.
     *
     * @param string $paymentId Payment ID
     * @return PaymentStatus Current payment status
     */
    public function getStatus(string $paymentId): PaymentStatus;

    /**
     * Retry a failed payment.
     *
     * @param string $paymentId Payment ID
     * @param PaymentExecutorInterface|null $executor Payment executor to use
     * @return PaymentResult Retry result
     */
    public function retry(
        string $paymentId,
        ?PaymentExecutorInterface $executor = null,
    ): PaymentResult;
}
