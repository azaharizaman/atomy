<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\MultiEntityPaymentBatch;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentExecutionResult;

/**
 * Contract for multi-entity payment execution.
 *
 * Handles payment processing across multiple legal entities
 * with proper bank account selection and currency handling.
 */
interface MultiEntityPaymentServiceInterface
{
    /**
     * Get available payment banks for entity.
     *
     * @param string $entityId Legal entity ID
     * @param string $currency Payment currency
     * @return array<array{bank_id: string, account_number: string, balance: Money, is_primary: bool}>
     */
    public function getEntityPaymentBanks(string $entityId, string $currency): array;

    /**
     * Select optimal bank for payment.
     *
     * @param string $entityId Legal entity
     * @param Money $amount Payment amount
     * @param string $paymentMethod Method (ACH, WIRE, CHECK)
     * @return array{bank_id: string, account_number: string}
     */
    public function selectOptimalBank(
        string $entityId,
        Money $amount,
        string $paymentMethod,
    ): array;

    /**
     * Execute payment batch for entity.
     *
     * @param MultiEntityPaymentBatch $batch Payment batch
     * @return PaymentExecutionResult Execution result
     */
    public function executePaymentBatch(MultiEntityPaymentBatch $batch): PaymentExecutionResult;

    /**
     * Get cross-entity payment authorization chain.
     *
     * @param string $entityId Entity initiating payment
     * @param Money $amount Payment amount
     * @return array<array{level: int, approver_type: string, entity_id: string}>
     */
    public function getAuthorizationChain(string $entityId, Money $amount): array;

    /**
     * Validate entity has payment permissions.
     *
     * @param string $entityId Entity to validate
     * @param string $vendorId Vendor to pay
     * @return array{valid: bool, reason: ?string}
     */
    public function validatePaymentPermission(string $entityId, string $vendorId): array;

    /**
     * Get entity payment statistics.
     *
     * @param string $entityId Legal entity
     * @param \DateTimeImmutable $periodStart Start date
     * @param \DateTimeImmutable $periodEnd End date
     * @return array{total_paid: Money, payment_count: int, by_method: array, by_vendor: array}
     */
    public function getEntityPaymentStats(
        string $entityId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): array;
}
