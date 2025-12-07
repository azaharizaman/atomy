<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\PaymentResult;
use Nexus\ProcurementOperations\DTOs\ProcessPaymentRequest;

/**
 * Contract for payment processing coordination.
 *
 * Handles scheduling, batching, and execution of vendor payments
 * with integration to GL for payment journal entries.
 */
interface PaymentProcessingCoordinatorInterface
{
    /**
     * Process payment for matched invoices.
     *
     * This operation:
     * 1. Validates invoices are matched and approved
     * 2. Checks for duplicate payments
     * 3. Creates payment batch
     * 4. Schedules or executes payment
     * 5. Posts payment journal entry (DR AP, CR Bank)
     * 6. Dispatches PaymentExecutedEvent or PaymentScheduledEvent
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PaymentException
     * @throws \Nexus\ProcurementOperations\Exceptions\DuplicatePaymentException
     */
    public function process(ProcessPaymentRequest $request): PaymentResult;

    /**
     * Schedule payment for future execution.
     *
     * @param array<string> $vendorBillIds
     * @throws \Nexus\ProcurementOperations\Exceptions\PaymentException
     */
    public function schedule(
        string $tenantId,
        array $vendorBillIds,
        \DateTimeImmutable $scheduledDate,
        string $paymentMethod,
        string $bankAccountId,
        string $scheduledBy
    ): PaymentResult;

    /**
     * Execute a scheduled payment batch.
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PaymentException
     */
    public function executeBatch(
        string $tenantId,
        string $paymentBatchId,
        string $executedBy
    ): PaymentResult;

    /**
     * Cancel a scheduled payment.
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PaymentException
     */
    public function cancel(
        string $tenantId,
        string $paymentId,
        string $cancelledBy,
        string $reason
    ): PaymentResult;

    /**
     * Void an executed payment (creates reversal).
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\PaymentException
     */
    public function void(
        string $tenantId,
        string $paymentId,
        string $voidedBy,
        string $reason
    ): PaymentResult;

    /**
     * Get payment status for vendor bills.
     *
     * @param array<string> $vendorBillIds
     * @return array<string, array{
     *     status: string,
     *     paymentId: ?string,
     *     scheduledDate: ?\DateTimeImmutable,
     *     executedDate: ?\DateTimeImmutable,
     *     amountCents: int
     * }>
     */
    public function getPaymentStatus(string $tenantId, array $vendorBillIds): array;
}
