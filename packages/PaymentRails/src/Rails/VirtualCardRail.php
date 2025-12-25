<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Rails;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\RailConfigurationInterface;
use Nexus\PaymentRails\Contracts\RailTransactionPersistInterface;
use Nexus\PaymentRails\Contracts\RailTransactionQueryInterface;
use Nexus\PaymentRails\Contracts\VirtualCardRailInterface;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\DTOs\VirtualCardRequest;
use Nexus\PaymentRails\DTOs\VirtualCardResult;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\VirtualCardStatus;
use Nexus\PaymentRails\Enums\VirtualCardType;
use Nexus\PaymentRails\Exceptions\InvalidVirtualCardNumberException;
use Nexus\PaymentRails\Exceptions\RailUnavailableException;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Nexus\PaymentRails\ValueObjects\VirtualCard;
use Nexus\PaymentRails\ValueObjects\VirtualCardNumber;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Virtual card payment rail implementation.
 *
 * Handles virtual card operations including:
 * - Single-use cards for vendor payments
 * - Multi-use cards for recurring vendor relationships
 * - Supplier cards for purchase orders
 * - Card lifecycle management
 * - Transaction tracking and reconciliation
 */
final class VirtualCardRail extends AbstractPaymentRail implements VirtualCardRailInterface
{
    /**
     * Default card validity period in days.
     */
    private const DEFAULT_VALIDITY_DAYS = 30;

    /**
     * Maximum card validity period in days.
     */
    private const MAXIMUM_VALIDITY_DAYS = 365;

    /**
     * Minimum credit limit in cents.
     */
    private const MINIMUM_CREDIT_CENTS = 100; // $1.00

    /**
     * Maximum credit limit in cents.
     */
    private const MAXIMUM_CREDIT_CENTS = 99999999; // $999,999.99

    public function __construct(
        RailConfigurationInterface $configuration,
        private readonly RailTransactionQueryInterface $transactionQuery,
        private readonly RailTransactionPersistInterface $transactionPersist,
        LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($configuration, $logger);
    }

    public function getRailType(): RailType
    {
        return RailType::VIRTUAL_CARD;
    }

    protected function buildCapabilities(): RailCapabilities
    {
        return new RailCapabilities(
            railType: RailType::VIRTUAL_CARD,
            supportedCurrencies: ['USD', 'EUR', 'GBP', 'CAD', 'AUD'],
            minimumAmount: new Money(self::MINIMUM_CREDIT_CENTS, 'USD'),
            maximumAmount: new Money(self::MAXIMUM_CREDIT_CENTS, 'USD'),
            supportsCredit: true,
            supportsDebit: false,
            supportsScheduledPayments: true,
            supportsRecurring: true,
            supportsBatchProcessing: true,
            requiresPrenotification: false,
            typicalSettlementDays: 1,
            requiredFields: ['vendor_id', 'vendor_name'],
            additionalCapabilities: [
                'supports_refunds' => true,
                'supports_partial_refunds' => true,
                'requires_beneficiary_address' => false,
                'max_card_validity_days' => self::MAXIMUM_VALIDITY_DAYS,
                'default_validity_days' => self::DEFAULT_VALIDITY_DAYS,
            ],
        );
    }

    /**
     * Create a virtual card.
     */
    public function createCard(VirtualCardRequest $request): VirtualCardResult
    {
        $this->ensureAvailable();

        // Validate request
        $errors = $this->validateCardRequest($request);
        if (!empty($errors)) {
            return new VirtualCardResult(
                success: false,
                cardId: '',
                status: VirtualCardStatus::CANCELLED,
                errorMessage: implode('; ', $errors),
            );
        }

        $cardId = $this->generateReference('VCARD');
        $cardNumber = $this->generateCardNumber();
        $cvv = $this->generateCvv();
        $expiresAt = $this->calculateExpiration($request->validityDays);

        $creditLimitCents = $this->toAmountCents($request->creditLimit);

        $card = new VirtualCard(
            cardNumber: $cardNumber,
            cardId: $cardId,
            cardType: $request->cardType,
            creditLimitCents: $creditLimitCents,
            remainingCreditCents: $creditLimitCents,
            currency: $request->creditLimit->getCurrency(),
            expiresAt: $expiresAt,
            status: VirtualCardStatus::ACTIVE,
            vendorId: $request->vendorId,
            vendorName: $request->vendorName,
            purchaseOrderNumber: $request->purchaseOrderNumber,
        );

        // Persist the card
        $this->transactionPersist->save($cardId, [
            'type' => 'virtual_card',
            'card_number_hash' => hash('sha256', $cardNumber->getValue()),
            'card_last_four' => $cardNumber->getLastFour(),
            'card_type' => $request->cardType->value,
            'credit_limit_cents' => $creditLimitCents,
            'remaining_credit_cents' => $creditLimitCents,
            'currency' => $request->creditLimit->getCurrency(),
            'expires_at' => $expiresAt->format(\DateTimeInterface::RFC3339),
            'status' => VirtualCardStatus::ACTIVE->value,
            'vendor_id' => $request->vendorId,
            'vendor_name' => $request->vendorName,
            'purchase_order_number' => $request->purchaseOrderNumber,
            'created_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Virtual card created', $cardId, [
            'card_type' => $request->cardType->value,
            'credit_limit_cents' => $creditLimitCents,
            'vendor' => $request->vendorName,
        ]);

        return new VirtualCardResult(
            success: true,
            cardId: $cardId,
            cardNumber: $cardNumber,
            cvv: $cvv,
            expiresAt: $expiresAt,
            cardType: $request->cardType,
            status: VirtualCardStatus::ACTIVE,
            creditLimitCents: $creditLimitCents,
            remainingCreditCents: $creditLimitCents,
            currency: $request->creditLimit->getCurrency(),
            vendorId: $request->vendorId,
            vendorName: $request->vendorName,
        );
    }

    /**
     * Get card status.
     */
    public function getCardStatus(string $cardId): VirtualCardResult
    {
        $transaction = $this->transactionQuery->findById($cardId);

        if ($transaction === null) {
            return new VirtualCardResult(
                success: false,
                cardId: $cardId,
                status: VirtualCardStatus::CANCELLED,
                errorMessage: 'Card not found',
            );
        }

        $metadata = $transaction->metadata;

        return new VirtualCardResult(
            success: true,
            cardId: $cardId,
            cardType: VirtualCardType::from($metadata['card_type']),
            status: VirtualCardStatus::from($metadata['status']),
            creditLimitCents: $metadata['credit_limit_cents'],
            remainingCreditCents: $metadata['remaining_credit_cents'],
            currency: $metadata['currency'],
            vendorId: $metadata['vendor_id'] ?? null,
            vendorName: $metadata['vendor_name'] ?? null,
            expiresAt: new \DateTimeImmutable($metadata['expires_at']),
        );
    }

    /**
     * Update credit limit on a card.
     */
    public function updateCreditLimit(string $cardId, Money $newLimit): bool
    {
        $transaction = $this->transactionQuery->findById($cardId);

        if ($transaction === null) {
            return false;
        }

        $currentStatus = VirtualCardStatus::from($transaction->metadata['status']);

        if (!$currentStatus->isUsable()) {
            $this->logger->warning('Cannot update limit on non-usable card', [
                'card_id' => $cardId,
                'current_status' => $currentStatus->value,
            ]);
            return false;
        }

        $newLimitCents = $this->toAmountCents($newLimit);
        $currentRemaining = $transaction->metadata['remaining_credit_cents'];
        $currentLimit = $transaction->metadata['credit_limit_cents'];
        $usedAmount = $currentLimit - $currentRemaining;

        // Calculate new remaining (preserve used amount)
        $newRemaining = max(0, $newLimitCents - $usedAmount);

        $this->transactionPersist->updateMetadata($cardId, [
            'credit_limit_cents' => $newLimitCents,
            'remaining_credit_cents' => $newRemaining,
            'limit_updated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Credit limit updated', $cardId, [
            'old_limit_cents' => $currentLimit,
            'new_limit_cents' => $newLimitCents,
        ]);

        return true;
    }

    /**
     * Close a virtual card.
     */
    public function closeCard(string $cardId, string $reason): bool
    {
        $transaction = $this->transactionQuery->findById($cardId);

        if ($transaction === null) {
            return false;
        }

        $currentStatus = VirtualCardStatus::from($transaction->metadata['status']);

        if (in_array($currentStatus, [VirtualCardStatus::CLOSED, VirtualCardStatus::CANCELLED])) {
            return true; // Already closed
        }

        $this->transactionPersist->updateStatus($cardId, VirtualCardStatus::CLOSED->value);
        $this->transactionPersist->updateMetadata($cardId, [
            'close_reason' => $reason,
            'closed_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Card closed', $cardId, ['reason' => $reason]);

        return true;
    }

    /**
     * Freeze a virtual card temporarily.
     */
    public function freezeCard(string $cardId, string $reason): bool
    {
        $transaction = $this->transactionQuery->findById($cardId);

        if ($transaction === null) {
            return false;
        }

        $currentStatus = VirtualCardStatus::from($transaction->metadata['status']);

        if ($currentStatus !== VirtualCardStatus::ACTIVE) {
            $this->logger->warning('Can only freeze active cards', [
                'card_id' => $cardId,
                'current_status' => $currentStatus->value,
            ]);
            return false;
        }

        $this->transactionPersist->updateStatus($cardId, VirtualCardStatus::FROZEN->value);
        $this->transactionPersist->updateMetadata($cardId, [
            'freeze_reason' => $reason,
            'frozen_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Card frozen', $cardId, ['reason' => $reason]);

        return true;
    }

    /**
     * Unfreeze a virtual card.
     */
    public function unfreezeCard(string $cardId): bool
    {
        $transaction = $this->transactionQuery->findById($cardId);

        if ($transaction === null) {
            return false;
        }

        $currentStatus = VirtualCardStatus::from($transaction->metadata['status']);

        if ($currentStatus !== VirtualCardStatus::FROZEN) {
            $this->logger->warning('Can only unfreeze frozen cards', [
                'card_id' => $cardId,
                'current_status' => $currentStatus->value,
            ]);
            return false;
        }

        $this->transactionPersist->updateStatus($cardId, VirtualCardStatus::ACTIVE->value);
        $this->transactionPersist->updateMetadata($cardId, [
            'unfrozen_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Card unfrozen', $cardId);

        return true;
    }

    /**
     * Record a charge on the card.
     */
    public function recordCharge(
        string $cardId,
        Money $amount,
        string $merchantName,
        ?string $merchantId = null,
    ): bool {
        $transaction = $this->transactionQuery->findById($cardId);

        if ($transaction === null) {
            return false;
        }

        $status = VirtualCardStatus::from($transaction->metadata['status']);
        if (!$status->isUsable()) {
            $this->logger->warning('Charge attempted on non-usable card', [
                'card_id' => $cardId,
                'status' => $status->value,
            ]);
            return false;
        }

        $amountCents = $this->toAmountCents($amount);
        $remainingCents = $transaction->metadata['remaining_credit_cents'];

        if ($amountCents > $remainingCents) {
            $this->logger->warning('Charge exceeds remaining credit', [
                'card_id' => $cardId,
                'charge_cents' => $amountCents,
                'remaining_cents' => $remainingCents,
            ]);
            return false;
        }

        $newRemaining = $remainingCents - $amountCents;

        // Update remaining credit
        $this->transactionPersist->updateMetadata($cardId, [
            'remaining_credit_cents' => $newRemaining,
        ]);

        // Log charge as child transaction
        $chargeId = $this->generateReference('CHRG');
        $this->transactionPersist->save($chargeId, [
            'type' => 'virtual_card_charge',
            'parent_card_id' => $cardId,
            'amount_cents' => $amountCents,
            'currency' => $amount->getCurrency(),
            'merchant_name' => $merchantName,
            'merchant_id' => $merchantId,
            'charged_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        // Auto-close single-use cards when fully used
        $cardType = VirtualCardType::from($transaction->metadata['card_type']);
        if ($cardType === VirtualCardType::SINGLE_USE && $newRemaining === 0) {
            $this->closeCard($cardId, 'Credit limit exhausted');
        }

        $this->logOperation('Charge recorded', $cardId, [
            'charge_cents' => $amountCents,
            'remaining_cents' => $newRemaining,
            'merchant' => $merchantName,
        ]);

        return true;
    }

    /**
     * Record a refund on the card.
     */
    public function recordRefund(string $cardId, Money $amount, string $originalChargeId): bool
    {
        $transaction = $this->transactionQuery->findById($cardId);

        if ($transaction === null) {
            return false;
        }

        $amountCents = $this->toAmountCents($amount);
        $remainingCents = $transaction->metadata['remaining_credit_cents'];
        $limitCents = $transaction->metadata['credit_limit_cents'];

        // Cannot refund more than was used
        $usedAmount = $limitCents - $remainingCents;
        if ($amountCents > $usedAmount) {
            $this->logger->warning('Refund exceeds used amount', [
                'card_id' => $cardId,
                'refund_cents' => $amountCents,
                'used_cents' => $usedAmount,
            ]);
            return false;
        }

        $newRemaining = $remainingCents + $amountCents;

        $this->transactionPersist->updateMetadata($cardId, [
            'remaining_credit_cents' => $newRemaining,
        ]);

        // Log refund
        $refundId = $this->generateReference('RFND');
        $this->transactionPersist->save($refundId, [
            'type' => 'virtual_card_refund',
            'parent_card_id' => $cardId,
            'original_charge_id' => $originalChargeId,
            'amount_cents' => $amountCents,
            'refunded_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Refund recorded', $cardId, [
            'refund_cents' => $amountCents,
            'new_remaining_cents' => $newRemaining,
        ]);

        return true;
    }

    /**
     * Get all charges for a card.
     *
     * @return array<RailTransactionResult>
     */
    public function getCardCharges(string $cardId): array
    {
        return $this->transactionQuery->findByMetadataField(
            'parent_card_id',
            $cardId,
            'virtual_card_charge'
        );
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
     * Cancel a transaction (close the card).
     */
    public function cancelTransaction(string $transactionId, string $reason): bool
    {
        return $this->closeCard($transactionId, $reason);
    }

    /**
     * Ensure the rail is available.
     */
    private function ensureAvailable(): void
    {
        if (!$this->isAvailable()) {
            throw RailUnavailableException::outsideOperatingHours($this->getRailType());
        }
    }

    /**
     * Validate a card request.
     *
     * @return array<string>
     */
    private function validateCardRequest(VirtualCardRequest $request): array
    {
        $errors = [];

        $creditCents = $this->toAmountCents($request->creditLimit);

        if ($creditCents < self::MINIMUM_CREDIT_CENTS) {
            $errors[] = sprintf('Minimum credit limit is $%.2f', self::MINIMUM_CREDIT_CENTS / 100);
        }

        if ($creditCents > self::MAXIMUM_CREDIT_CENTS) {
            $errors[] = sprintf('Maximum credit limit is $%.2f', self::MAXIMUM_CREDIT_CENTS / 100);
        }

        if ($request->validityDays !== null && $request->validityDays > self::MAXIMUM_VALIDITY_DAYS) {
            $errors[] = sprintf('Maximum validity period is %d days', self::MAXIMUM_VALIDITY_DAYS);
        }

        if (!$this->supportsCurrency($request->creditLimit->getCurrency())) {
            $errors[] = sprintf('Currency %s is not supported', $request->creditLimit->getCurrency());
        }

        if (empty($request->vendorName)) {
            $errors[] = 'Vendor name is required.';
        }

        return $errors;
    }

    /**
     * Generate a virtual card number.
     */
    private function generateCardNumber(): VirtualCardNumber
    {
        // Generate a Visa-like virtual card number (starts with 4)
        $bin = '4' . str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $accountNumber = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        
        $partialNumber = $bin . $accountNumber;
        $checkDigit = $this->calculateLuhnCheckDigit($partialNumber);

        return VirtualCardNumber::fromString($partialNumber . $checkDigit);
    }

    /**
     * Generate a CVV.
     */
    private function generateCvv(): string
    {
        return str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate expiration date.
     */
    private function calculateExpiration(?int $validityDays): \DateTimeImmutable
    {
        $days = $validityDays ?? self::DEFAULT_VALIDITY_DAYS;
        $days = min($days, self::MAXIMUM_VALIDITY_DAYS);

        return (new \DateTimeImmutable())->modify("+{$days} days");
    }

    /**
     * Calculate Luhn check digit.
     */
    private function calculateLuhnCheckDigit(string $number): string
    {
        $sum = 0;
        $shouldDouble = true;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($shouldDouble) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $shouldDouble = !$shouldDouble;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }

    /**
     * Convert Money to cents.
     */
    private function toAmountCents(Money $money): int
    {
        return (int) ($money->getAmount() * 100);
    }
}
