<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\Enums\RtgsSystem;

/**
 * Contract for RTGS (Real-Time Gross Settlement) rail operations.
 *
 * RTGS systems process high-value, time-critical payments in real-time.
 * Examples include Fedwire (US), CHAPS (UK), TARGET2 (EU).
 */
interface RtgsRailInterface extends PaymentRailInterface
{
    /**
     * Get the RTGS system used by this rail.
     */
    public function getRtgsSystem(): RtgsSystem;

    /**
     * Initiate an RTGS payment.
     *
     * @param array<string, mixed> $paymentData
     */
    public function initiatePayment(array $paymentData): RailTransactionResult;

    /**
     * Get the operating hours for the RTGS system.
     *
     * @return array{open: \DateTimeImmutable, close: \DateTimeImmutable}
     */
    public function getOperatingHours(): array;

    /**
     * Check if the RTGS system is currently open.
     */
    public function isSystemOpen(): bool;

    /**
     * Get the minimum transaction amount.
     */
    public function getMinimumAmount(): Money;

    /**
     * Get the maximum transaction amount per day.
     */
    public function getDailyLimit(): Money;

    /**
     * Get remaining daily limit.
     */
    public function getRemainingDailyLimit(): Money;

    /**
     * Confirm a pending RTGS payment.
     */
    public function confirmPayment(string $transactionId, string $confirmationCode): RailTransactionResult;

    /**
     * Recall a payment if possible.
     */
    public function recallPayment(string $transactionId, string $reason): bool;

    /**
     * Get the system's current queue depth.
     *
     * @return int Number of pending transactions
     */
    public function getQueueDepth(): int;
}
