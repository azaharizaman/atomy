<?php

declare(strict_types=1);

namespace Nexus\Payment\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\PaymentExecutorInterface;
use Nexus\Payment\Contracts\PaymentManagerInterface;
use Nexus\Payment\Contracts\PaymentMethodQueryInterface;
use Nexus\Payment\Contracts\PaymentPersistInterface;
use Nexus\Payment\Contracts\PaymentQueryInterface;
use Nexus\Payment\Contracts\PaymentTransactionInterface;
use Nexus\Payment\Contracts\PaymentValidatorInterface;
use Nexus\Payment\Entities\PaymentTransaction;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\Events\PaymentCancelledEvent;
use Nexus\Payment\Events\PaymentCompletedEvent;
use Nexus\Payment\Events\PaymentCreatedEvent;
use Nexus\Payment\Events\PaymentFailedEvent;
use Nexus\Payment\Events\PaymentProcessingEvent;
use Nexus\Payment\Events\PaymentReversedEvent;
use Nexus\Payment\Exceptions\DuplicatePaymentException;
use Nexus\Payment\Exceptions\InvalidPaymentStatusException;
use Nexus\Payment\Exceptions\PaymentExecutionException;
use Nexus\Payment\Exceptions\PaymentNotFoundException;
use Nexus\Payment\Exceptions\PaymentValidationException;
use Nexus\Payment\ValueObjects\ExecutionContext;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;
use Nexus\Payment\ValueObjects\PaymentResult;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Payment Manager - Orchestrates payment lifecycle.
 *
 * This is the primary entry point for creating and managing payments.
 */
final class PaymentManager implements PaymentManagerInterface
{
    public function __construct(
        private readonly PaymentQueryInterface $paymentQuery,
        private readonly PaymentPersistInterface $paymentPersist,
        private readonly PaymentMethodQueryInterface $methodQuery,
        private readonly PaymentValidatorInterface $validator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly ?PaymentExecutorInterface $defaultExecutor = null,
    ) {}

    /**
     * Create a new payment transaction.
     *
     * @param array<string, mixed>|null $metadata
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
    ): PaymentTransactionInterface {
        // Validate creation parameters
        $this->validator->validateCreate([
            'tenant_id' => $tenantId,
            'direction' => $direction,
            'amount' => $amount,
            'method_type' => $methodType,
            'payer_id' => $payerId,
            'payee_id' => $payeeId,
        ]);

        $this->validator->validateReference($reference);
        $this->validator->validateAmount($amount);

        // Check idempotency
        if ($idempotencyKey !== null) {
            $this->validator->validateIdempotencyKey($idempotencyKey);

            $existing = $this->paymentQuery->findByIdempotencyKey(
                $idempotencyKey->getValue()
            );

            if ($existing !== null) {
                // Verify tenant matches for extra safety; cross-tenant collisions are treated as security issues
                if ($existing->getTenantId() === $tenantId) {
                    throw new DuplicatePaymentException(
                        $idempotencyKey->getValue(),
                        $existing->getId()
                    );
                }

                $this->logger->warning(
                    'Idempotency key collision across tenants detected in PaymentManager::create',
                    [
                        'idempotencyKey'   => $idempotencyKey->getValue(),
                        'currentTenantId'  => $tenantId,
                        'existingTenantId' => $existing->getTenantId(),
                        'existingPaymentId'=> $existing->getId(),
                    ]
                );

                throw new PaymentExecutionException(
                    'Idempotency key collision across tenants detected.',
                    null,
                    'SECURITY_IDEMPOTENCY_TENANT_COLLISION',
                    sprintf(
                        'idempotencyKey=%s; currentTenantId=%s; existingTenantId=%s',
                        $idempotencyKey->getValue(),
                        $tenantId,
                        $existing->getTenantId()
                    )
                );
            }
        }

        // Create payment entity
        $payment = PaymentTransaction::create(
            id: $this->generateId(),
            tenantId: $tenantId,
            reference: $reference,
            direction: $direction,
            amount: $amount,
            methodType: $methodType,
            payerId: $payerId,
            payeeId: $payeeId,
            idempotencyKey: $idempotencyKey,
            executionContext: $context,
        );

        // Add metadata if provided
        if ($metadata !== null && $metadata !== []) {
            $payment->addMetadata($metadata);
        }

        // Persist
        $this->paymentPersist->save($payment);

        // Register idempotency key
        // Store idempotency key mapping
        if ($idempotencyKey !== null) {
            $this->paymentPersist->storeIdempotencyKey(
                $idempotencyKey->getValue(),
                $payment->getId(),
                $idempotencyKey->expiresAt
            );
        }

        // Dispatch event
        $this->eventDispatcher->dispatch(new PaymentCreatedEvent(
            paymentId: $payment->getId(),
            tenantId: $tenantId,
            reference: $reference->getValue(),
            direction: $direction,
            amount: $amount,
            methodType: $methodType,
            payerId: $payerId,
            payeeId: $payeeId,
            idempotencyKey: $idempotencyKey?->getValue(),
            occurredAt: new \DateTimeImmutable(),
        ));

        $this->logger->info('Payment created', [
            'payment_id' => $payment->getId(),
            'reference' => $reference->getValue(),
            'amount' => $amount->format(),
        ]);

        return $payment;
    }

    /**
     * Execute a pending payment.
     */
    public function execute(
        string $paymentId,
        ?PaymentExecutorInterface $executor = null,
    ): PaymentResult {
        $payment = $this->findOrFail($paymentId);
        $executor ??= $this->defaultExecutor;

        if ($executor === null) {
            throw new PaymentExecutionException(
                paymentId: $paymentId,
                message: 'No payment executor configured'
            );
        }

        // Validate payment can be executed
        $this->validator->validateForExecution($payment);

        // Validate payment method if using tokenized method
        if ($payment->getMethodType()->requiresTokenization()) {
            $methodId = $payment->getMetadata()['payment_method_id'] ?? null;
            if ($methodId !== null) {
                $method = $this->methodQuery->findById($methodId);
                if ($method !== null) {
                    $this->validator->validatePaymentMethod($method, $payment->getAmount());
                }
            }
        }

        // Mark as processing
        $payment->markAsProcessing($executor::class);
        $this->paymentPersist->save($payment);

        $this->eventDispatcher->dispatch(new PaymentProcessingEvent(
            paymentId: $payment->getId(),
            tenantId: $payment->getTenantId(),
            amount: $payment->getAmount(),
            executorName: $executor::class,
            providerTransactionId: null,
            occurredAt: new \DateTimeImmutable(),
        ));

        try {
            // Execute payment
            $result = $executor->execute($payment);

            if ($result->isSuccess()) {
                $settledAmount = $result->getSettledAmount() ?? $payment->getAmount();
                $payment->markAsCompleted(
                    $settledAmount,
                    $result->getProviderTransactionId()
                );

                $this->eventDispatcher->dispatch(new PaymentCompletedEvent(
                    paymentId: $payment->getId(),
                    tenantId: $payment->getTenantId(),
                    amount: $payment->getAmount(),
                    settledAmount: $settledAmount,
                    providerTransactionId: $result->getProviderTransactionId(),
                    completedAt: $payment->getCompletedAt() ?? new \DateTimeImmutable(),
                    occurredAt: new \DateTimeImmutable(),
                ));

                $this->logger->info('Payment completed', [
                    'payment_id' => $payment->getId(),
                    'provider_txn_id' => $result->getProviderTransactionId(),
                ]);
            } else {
                $payment->markAsFailed(
                    $result->getFailureCode() ?? 'UNKNOWN',
                    $result->getFailureMessage() ?? 'Unknown error'
                );

                $this->eventDispatcher->dispatch(new PaymentFailedEvent(
                    paymentId: $payment->getId(),
                    tenantId: $payment->getTenantId(),
                    amount: $payment->getAmount(),
                    failureCode: $payment->getFailureCode() ?? 'UNKNOWN',
                    failureMessage: $payment->getFailureMessage() ?? 'Unknown error',
                    isRetryable: true,
                    attemptCount: $payment->getAttemptCount(),
                    nextRetryAt: null,
                    occurredAt: new \DateTimeImmutable(),
                ));

                $this->logger->warning('Payment failed', [
                    'payment_id' => $payment->getId(),
                    'failure_code' => $result->getFailureCode(),
                ]);
            }

            $this->paymentPersist->save($payment);

            return $result;
        } catch (\Throwable $e) {
            $payment->markAsFailed('EXCEPTION', $e->getMessage());
            $this->paymentPersist->save($payment);

            $this->eventDispatcher->dispatch(new PaymentFailedEvent(
                paymentId: $payment->getId(),
                tenantId: $payment->getTenantId(),
                amount: $payment->getAmount(),
                failureCode: 'EXCEPTION',
                failureMessage: $e->getMessage(),
                isRetryable: false,
                attemptCount: $payment->getAttemptCount(),
                nextRetryAt: null,
                occurredAt: new \DateTimeImmutable(),
            ));

            throw new PaymentExecutionException(
                paymentId: $payment->getId(),
                message: $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Cancel a pending payment.
     */
    public function cancel(
        string $paymentId,
        string $reason,
        ?string $cancelledBy = null,
    ): PaymentTransactionInterface {
        $payment = $this->findOrFail($paymentId);

        $this->validator->validateForCancellation($payment);

        $payment->markAsCancelled($reason);
        $this->paymentPersist->save($payment);

        $this->eventDispatcher->dispatch(new PaymentCancelledEvent(
            paymentId: $payment->getId(),
            tenantId: $payment->getTenantId(),
            amount: $payment->getAmount(),
            reason: $reason,
            cancelledBy: $cancelledBy ?? 'system',
            occurredAt: new \DateTimeImmutable(),
        ));

        $this->logger->info('Payment cancelled', [
            'payment_id' => $payment->getId(),
            'reason' => $reason,
        ]);

        return $payment;
    }

    /**
     * Reverse a completed payment.
     */
    public function reverse(
        string $paymentId,
        ?Money $amount = null,
        ?string $reason = null,
        ?PaymentExecutorInterface $executor = null,
    ): PaymentResult {
        $payment = $this->findOrFail($paymentId);
        $executor ??= $this->defaultExecutor;

        $this->validator->validateForReversal($payment);

        $reversalAmount = $amount ?? $payment->getAmount();

        // Validate reversal amount
        if ($reversalAmount->greaterThan($payment->getAmount())) {
            throw new PaymentValidationException(
                "Reversal amount cannot exceed original payment amount"
            );
        }

        if ($executor === null) {
            throw new PaymentExecutionException(
                paymentId: $paymentId,
                message: 'No payment executor configured for reversal'
            );
        }

        try {
            $result = $executor->refund(
                paymentId: $payment->getId(),
                amount: $reversalAmount->getAmount(),
                reason: $reason
            );

            if ($result->isSuccess()) {
                $payment->markAsReversed($reason, $result->getProviderTransactionId());
                $this->paymentPersist->save($payment);

                $this->eventDispatcher->dispatch(new PaymentReversedEvent(
                    paymentId: $payment->getId(),
                    tenantId: $payment->getTenantId(),
                    originalAmount: $payment->getAmount(),
                    reversedAmount: $reversalAmount,
                    reason: $reason,
                    reversalTransactionId: $result->getProviderTransactionId(),
                    reversedBy: 'system',
                    occurredAt: new \DateTimeImmutable(),
                ));

                $this->logger->info('Payment reversed', [
                    'payment_id' => $payment->getId(),
                    'reversed_amount' => $reversalAmount->format(),
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            throw new PaymentExecutionException(
                paymentId: $payment->getId(),
                message: "Reversal failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Find a payment by ID or throw.
     */
    public function findOrFail(string $paymentId): PaymentTransactionInterface
    {
        // Repository auto-scopes by tenant via TenantContextInterface
        $payment = $this->paymentQuery->findById($paymentId);

        if ($payment === null) {
            throw PaymentNotFoundException::forId($paymentId);
        }

        return $payment;
    }

    /**
     * Get the status of a payment.
     */
    public function getStatus(string $paymentId): PaymentStatus
    {
        return $this->findOrFail($paymentId)->getStatus();
    }

    /**
     * Retry a failed payment.
     */
    public function retry(
        string $paymentId,
        ?PaymentExecutorInterface $executor = null,
    ): PaymentResult {
        $payment = $this->findOrFail($paymentId);

        if ($payment->getStatus() !== PaymentStatus::FAILED) {
            throw new InvalidPaymentStatusException(
                $payment->getStatus(),
                PaymentStatus::FAILED,
                'Only failed payments can be retried'
            );
        }

        // Execute handles status transitions, so we just call execute directly
        return $this->execute($paymentId, $executor);
    }

    /**
     * Generate a unique payment ID.
     */
    private function generateId(): string
    {
        return 'pay_' . bin2hex(random_bytes(16));
    }
}
