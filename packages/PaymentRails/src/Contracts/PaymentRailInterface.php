<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;

/**
 * Contract for all payment rails.
 *
 * This interface defines the common operations that every payment rail
 * implementation must support, regardless of the underlying rail type
 * (ACH, Wire, Check, RTGS, Virtual Card).
 */
interface PaymentRailInterface
{
    /**
     * Get the rail type.
     */
    public function getRailType(): RailType;

    /**
     * Get the capabilities of this rail.
     */
    public function getCapabilities(): RailCapabilities;

    /**
     * Check if the rail is available for transactions.
     */
    public function isAvailable(): bool;

    /**
     * Check if a specific amount is supported.
     */
    public function supportsAmount(Money $amount): bool;

    /**
     * Check if a specific currency is supported.
     */
    public function supportsCurrency(string $currencyCode): bool;

    /**
     * Get the estimated settlement time for a transaction.
     */
    public function getEstimatedSettlementDays(): int;

    /**
     * Check if the rail supports real-time processing.
     */
    public function isRealTime(): bool;

    /**
     * Validate that a transaction can be processed.
     *
     * @param array<string, mixed> $transactionData
     * @return array<string> Validation errors
     */
    public function validateTransaction(array $transactionData): array;

    /**
     * Get the status of a transaction.
     */
    public function getTransactionStatus(string $transactionId): RailTransactionResult;

    /**
     * Cancel a pending transaction if possible.
     */
    public function cancelTransaction(string $transactionId, string $reason): bool;
}
