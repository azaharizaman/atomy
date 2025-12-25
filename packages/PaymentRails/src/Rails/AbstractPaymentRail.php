<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Rails;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\Common\Contracts\ClockInterface;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Nexus\PaymentRails\Contracts\PaymentRailInterface;
use Nexus\PaymentRails\Contracts\RailConfigurationInterface;

/**
 * Abstract base class for all payment rails.
 *
 * Provides common functionality and enforces consistent behavior
 * across all rail implementations.
 */
abstract class AbstractPaymentRail implements PaymentRailInterface
{
    protected readonly RailCapabilities $capabilities;

    protected readonly ClockInterface $clock;

    public function __construct(
        protected readonly RailConfigurationInterface $configuration,
        protected LoggerInterface $logger = new NullLogger(),
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        };

        $this->capabilities = $this->buildCapabilities();
    }

    /**
     * Build capabilities for this rail type.
     */
    abstract protected function buildCapabilities(): RailCapabilities;

    /**
     * Get the rail type.
     */
    abstract public function getRailType(): RailType;

    /**
     * Get the capabilities of this rail.
     */
    public function getCapabilities(): RailCapabilities
    {
        return $this->capabilities;
    }

    /**
     * Check if this rail is available.
     */
    public function isAvailable(): bool
    {
        if (!$this->configuration->isEnabled($this->getRailType())) {
            return false;
        }

        return $this->checkOperatingHours();
    }

    /**
     * Check if current time is within operating hours.
     */
    protected function checkOperatingHours(): bool
    {
        $cutoffTimes = $this->configuration->getCutoffTimes($this->getRailType());

        if ($cutoffTimes === []) {
            return true; // No cutoff defined, always available (weekday)
        }

        $now = $this->clock->now();
        $currentTime = $now->format('H:i:s');
        $dayOfWeek = (int) $now->format('N');

        // Weekend check (Saturday = 6, Sunday = 7)
        if ($dayOfWeek >= 6) {
            return false;
        }

        $openTime = $cutoffTimes['open'] ?? null;
        $closeTime = $cutoffTimes['close'] ?? null;

        $openTimeString = $openTime instanceof \DateTimeImmutable
            ? $openTime->format('H:i:s')
            : (is_string($openTime) ? $openTime : '00:00:00');

        $closeTimeString = $closeTime instanceof \DateTimeImmutable
            ? $closeTime->format('H:i:s')
            : (is_string($closeTime) ? $closeTime : '23:59:59');

        return $currentTime >= $openTimeString && $currentTime <= $closeTimeString;
    }

    /**
     * Check if a given amount is supported.
     */
    public function supportsAmount(Money $amount): bool
    {
        return $this->capabilities->isAmountWithinLimits($amount);
    }

    /**
     * Check if a currency is supported.
     */
    public function supportsCurrency(string $currency): bool
    {
        return $this->capabilities->supportsCurrency($currency);
    }

    /**
     * Get estimated settlement days.
     */
    public function getEstimatedSettlementDays(): int
    {
        return $this->capabilities->typicalSettlementDays;
    }

    /**
     * Check if this rail supports real-time settlement.
     */
    public function isRealTime(): bool
    {
        return $this->capabilities->isRealTime();
    }

    /**
     * Validate a transaction before processing.
     *
     * @param array<string, mixed> $transactionData
     * @return array<string> Validation errors, empty if valid
     */
    public function validateTransaction(array $transactionData): array
    {
        $errors = [];

        // Amount validation
        if (isset($transactionData['amount']) && $transactionData['amount'] instanceof Money) {
            if (!$this->supportsAmount($transactionData['amount'])) {
                $errors[] = 'Amount is outside the supported range for this rail.';
            }

            if (!$this->supportsCurrency($transactionData['amount']->getCurrency())) {
                $errors[] = sprintf(
                    'Currency %s is not supported by this rail.',
                    $transactionData['amount']->getCurrency()
                );
            }
        }

        // Availability check
        if (!$this->isAvailable()) {
            $errors[] = 'This payment rail is currently unavailable.';
        }

        return $errors;
    }

    /**
     * Get the transaction status.
     *
     * Must be implemented by concrete rail classes.
     */
    abstract public function getTransactionStatus(string $transactionId): RailTransactionResult;

    /**
     * Cancel a transaction.
     *
     * Must be implemented by concrete rail classes.
     *
     * @return bool True if cancellation was successful
     */
    abstract public function cancelTransaction(string $transactionId, string $reason): bool;

    /**
     * Generate a unique transaction reference.
     */
    protected function generateReference(string $prefix = ''): string
    {
        $reference = $prefix . strtoupper(bin2hex(random_bytes(8)));
        
        return substr($reference, 0, 20);
    }

    /**
     * Log a rail operation.
     *
     * @param array<string, mixed> $context
     */
    protected function logOperation(string $operation, string $transactionId, array $context = []): void
    {
        $this->logger->info(sprintf(
            '[%s] %s for transaction %s',
            $this->getRailType()->value,
            $operation,
            $transactionId
        ), array_merge(['rail' => $this->getRailType()->value], $context));
    }
}
