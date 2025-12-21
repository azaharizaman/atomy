<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Rails;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\RailConfigurationInterface;
use Nexus\PaymentRails\Contracts\RailTransactionPersistInterface;
use Nexus\PaymentRails\Contracts\RailTransactionQueryInterface;
use Nexus\PaymentRails\Contracts\RtgsRailInterface;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\RtgsSystem;
use Nexus\PaymentRails\Exceptions\RailUnavailableException;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Real-Time Gross Settlement (RTGS) payment rail implementation.
 *
 * Handles high-value, time-critical payments via RTGS systems including:
 * - Fedwire (United States)
 * - CHAPS (United Kingdom)
 * - TARGET2 (Eurozone)
 * - RTGS India
 */
final class RtgsRail extends AbstractPaymentRail implements RtgsRailInterface
{
    /**
     * Minimum RTGS amounts per system (typically high-value only).
     */
    private const MINIMUM_AMOUNTS = [
        'fedwire' => 100000, // $1,000 USD
        'chaps' => 1000000,  // £10,000 GBP
        'target2' => 100000, // €1,000 EUR
        'rtgs_india' => 20000000, // ₹200,000 INR
    ];

    /**
     * Maximum RTGS amounts per system.
     */
    private const MAXIMUM_AMOUNTS = [
        'fedwire' => 99999999999999, // No practical limit
        'chaps' => 99999999999999,
        'target2' => 99999999999999,
        'rtgs_india' => 99999999999999,
    ];

    /**
     * RTGS operating hours (Eastern Time for Fedwire).
     */
    private const OPERATING_HOURS = [
        'fedwire' => ['open' => '21:00:00', 'close' => '18:30:00'], // Previous day 9PM to 6:30PM
        'chaps' => ['open' => '06:00:00', 'close' => '18:00:00'],
        'target2' => ['open' => '07:00:00', 'close' => '18:00:00'],
        'rtgs_india' => ['open' => '09:00:00', 'close' => '16:30:00'],
    ];

    public function __construct(
        RailConfigurationInterface $configuration,
        private readonly RailTransactionQueryInterface $transactionQuery,
        private readonly RailTransactionPersistInterface $transactionPersist,
        private readonly RtgsSystem $defaultSystem = RtgsSystem::FEDWIRE,
        LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($configuration, $logger);
    }

    public function getRailType(): RailType
    {
        return RailType::RTGS;
    }

    protected function buildCapabilities(): RailCapabilities
    {
        return new RailCapabilities(
            railType: RailType::RTGS,
            supportedCurrencies: $this->getSupportedCurrencies($this->defaultSystem),
            minimumAmountCents: self::MINIMUM_AMOUNTS[$this->defaultSystem->value] ?? 100000,
            maximumAmountCents: self::MAXIMUM_AMOUNTS[$this->defaultSystem->value] ?? null,
            settlementDays: 0, // Real-time
            isRealTime: true,
            supportsRefunds: false, // RTGS is final
            supportsPartialRefunds: false,
            supportsRecurring: false,
            requiresBeneficiaryAddress: true,
        );
    }

    /**
     * Submit an RTGS payment.
     *
     * @param array<string, mixed> $paymentDetails
     */
    public function submitPayment(
        RtgsSystem $system,
        Money $amount,
        string $beneficiaryName,
        string $beneficiaryAccount,
        string $beneficiaryBankId,
        array $paymentDetails = [],
    ): RailTransactionResult {
        $this->ensureSystemAvailable($system);

        // Validate amount against system limits
        $errors = $this->validateAmount($system, $amount);
        if (!empty($errors)) {
            return new RailTransactionResult(
                success: false,
                transactionId: '',
                railType: $this->getRailType(),
                status: 'VALIDATION_FAILED',
                errorMessage: implode('; ', $errors),
            );
        }

        $transactionId = $this->generateReference('RTGS');
        $amountCents = $this->toAmountCents($amount);

        // Persist the transaction
        $this->transactionPersist->save($transactionId, [
            'type' => 'rtgs',
            'rtgs_system' => $system->value,
            'amount_cents' => $amountCents,
            'currency' => $amount->getCurrency(),
            'beneficiary_name' => $beneficiaryName,
            'beneficiary_account' => $beneficiaryAccount,
            'beneficiary_bank_id' => $beneficiaryBankId,
            'status' => 'PENDING',
            'created_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
            'payment_details' => $paymentDetails,
        ]);

        $this->logOperation('RTGS payment submitted', $transactionId, [
            'system' => $system->value,
            'amount_cents' => $amountCents,
            'currency' => $amount->getCurrency(),
        ]);

        return new RailTransactionResult(
            success: true,
            transactionId: $transactionId,
            railType: $this->getRailType(),
            status: 'PENDING',
            metadata: [
                'rtgs_system' => $system->value,
                'estimated_settlement' => 'real-time',
            ],
        );
    }

    /**
     * Get RTGS payment status.
     */
    public function getPaymentStatus(string $transactionId): RailTransactionResult
    {
        return $this->getTransactionStatus($transactionId);
    }

    /**
     * Check if RTGS system is available.
     */
    public function isSystemAvailable(RtgsSystem $system): bool
    {
        if (!$this->configuration->isEnabled($this->getRailType())) {
            return false;
        }

        $hours = self::OPERATING_HOURS[$system->value] ?? null;
        if ($hours === null) {
            return false;
        }

        return $this->isWithinOperatingHours($hours, $system);
    }

    /**
     * Get the next available window for RTGS.
     */
    public function getNextAvailableWindow(RtgsSystem $system): \DateTimeImmutable
    {
        if ($this->isSystemAvailable($system)) {
            return new \DateTimeImmutable();
        }

        $hours = self::OPERATING_HOURS[$system->value];
        $timezone = $this->getTimezoneForSystem($system);
        
        $now = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        $openTime = $hours['open'];

        // Calculate next opening
        $nextOpen = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $now->format('Y-m-d') . ' ' . $openTime,
            new \DateTimeZone($timezone)
        );

        if ($nextOpen <= $now) {
            $nextOpen = $nextOpen->modify('+1 day');
        }

        // Skip weekends
        $dayOfWeek = (int) $nextOpen->format('N');
        if ($dayOfWeek === 6) { // Saturday
            $nextOpen = $nextOpen->modify('+2 days');
        } elseif ($dayOfWeek === 7) { // Sunday
            $nextOpen = $nextOpen->modify('+1 day');
        }

        return $nextOpen;
    }

    /**
     * Get supported currencies for RTGS system.
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(RtgsSystem $system): array
    {
        return match ($system) {
            RtgsSystem::FEDWIRE => ['USD'],
            RtgsSystem::CHAPS => ['GBP'],
            RtgsSystem::TARGET2 => ['EUR'],
            RtgsSystem::RTGS_INDIA => ['INR'],
        };
    }

    /**
     * Get minimum amount for RTGS system.
     */
    public function getMinimumAmount(RtgsSystem $system): int
    {
        return self::MINIMUM_AMOUNTS[$system->value] ?? 100000;
    }

    /**
     * Get transaction status.
     */
    public function getTransactionStatus(string $transactionId): RailTransactionResult
    {
        $transaction = $this->transactionQuery->findById($transactionId);

        if ($transaction === null) {
            return new RailTransactionResult(
                success: false,
                transactionId: $transactionId,
                railType: $this->getRailType(),
                status: 'NOT_FOUND',
                errorMessage: 'Transaction not found',
            );
        }

        return $transaction;
    }

    /**
     * Cancel a transaction (RTGS recall - rarely successful for completed payments).
     */
    public function cancelTransaction(string $transactionId, string $reason): bool
    {
        $transaction = $this->transactionQuery->findById($transactionId);

        if ($transaction === null) {
            return false;
        }

        // RTGS settlements are typically final
        if ($transaction->status !== 'PENDING') {
            $this->logger->warning('Cannot cancel non-pending RTGS transaction', [
                'transaction_id' => $transactionId,
                'current_status' => $transaction->status,
            ]);
            return false;
        }

        $this->transactionPersist->updateStatus($transactionId, 'CANCELLED');
        $this->logOperation('RTGS transaction cancelled', $transactionId, ['reason' => $reason]);

        return true;
    }

    /**
     * Request a payment recall (for settled payments).
     */
    public function requestRecall(string $transactionId, string $reason): bool
    {
        $transaction = $this->transactionQuery->findById($transactionId);

        if ($transaction === null) {
            return false;
        }

        // Can only request recall for completed transactions
        if ($transaction->status !== 'COMPLETED') {
            $this->logger->warning('Cannot recall non-completed RTGS transaction', [
                'transaction_id' => $transactionId,
                'current_status' => $transaction->status,
            ]);
            return false;
        }

        $this->transactionPersist->updateMetadata($transactionId, [
            'recall_requested' => true,
            'recall_reason' => $reason,
            'recall_requested_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('RTGS recall requested', $transactionId, ['reason' => $reason]);

        return true;
    }

    /**
     * Ensure RTGS system is available.
     */
    private function ensureSystemAvailable(RtgsSystem $system): void
    {
        if (!$this->isSystemAvailable($system)) {
            throw RailUnavailableException::outsideOperatingHours($this->getRailType());
        }
    }

    /**
     * Validate amount against system limits.
     *
     * @return array<string>
     */
    private function validateAmount(RtgsSystem $system, Money $amount): array
    {
        $errors = [];
        $amountCents = $this->toAmountCents($amount);

        // Currency validation
        $supportedCurrencies = $this->getSupportedCurrencies($system);
        if (!in_array($amount->getCurrency(), $supportedCurrencies, true)) {
            $errors[] = sprintf(
                '%s only supports: %s',
                $system->getDescription(),
                implode(', ', $supportedCurrencies)
            );
        }

        // Minimum amount
        $minimum = $this->getMinimumAmount($system);
        if ($amountCents < $minimum) {
            $errors[] = sprintf(
                'Minimum amount for %s is %s',
                $system->getDescription(),
                number_format($minimum / 100, 2)
            );
        }

        return $errors;
    }

    /**
     * Check if within operating hours.
     *
     * @param array<string, string> $hours
     */
    private function isWithinOperatingHours(array $hours, RtgsSystem $system): bool
    {
        $timezone = $this->getTimezoneForSystem($system);
        $now = new \DateTimeImmutable('now', new \DateTimeZone($timezone));
        
        $currentTime = $now->format('H:i:s');
        $dayOfWeek = (int) $now->format('N');

        // Weekend check
        if ($dayOfWeek >= 6) {
            return false;
        }

        $openTime = $hours['open'];
        $closeTime = $hours['close'];

        // Handle overnight windows (Fedwire)
        if ($openTime > $closeTime) {
            return $currentTime >= $openTime || $currentTime <= $closeTime;
        }

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    /**
     * Get timezone for RTGS system.
     */
    private function getTimezoneForSystem(RtgsSystem $system): string
    {
        return match ($system) {
            RtgsSystem::FEDWIRE => 'America/New_York',
            RtgsSystem::CHAPS => 'Europe/London',
            RtgsSystem::TARGET2 => 'Europe/Frankfurt',
            RtgsSystem::RTGS_INDIA => 'Asia/Kolkata',
        };
    }

    /**
     * Convert Money to cents.
     */
    private function toAmountCents(Money $money): int
    {
        return (int) ($money->getAmount() * 100);
    }
}
