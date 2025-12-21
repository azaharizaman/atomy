<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Rails;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\RailConfigurationInterface;
use Nexus\PaymentRails\Contracts\RailTransactionPersistInterface;
use Nexus\PaymentRails\Contracts\RailTransactionQueryInterface;
use Nexus\PaymentRails\Contracts\WireRailInterface;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\DTOs\WireTransferRequest;
use Nexus\PaymentRails\DTOs\WireTransferResult;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\WireType;
use Nexus\PaymentRails\Exceptions\InvalidIbanException;
use Nexus\PaymentRails\Exceptions\InvalidSwiftCodeException;
use Nexus\PaymentRails\Exceptions\RailUnavailableException;
use Nexus\PaymentRails\Exceptions\WireValidationException;
use Nexus\PaymentRails\ValueObjects\Iban;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Nexus\PaymentRails\ValueObjects\SwiftCode;
use Nexus\PaymentRails\ValueObjects\WireInstruction;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Wire transfer payment rail implementation.
 *
 * Handles domestic and international wire transfers including:
 * - Fedwire (domestic US)
 * - SWIFT (international)
 * - CHIPS (high-value domestic)
 */
final class WireRail extends AbstractPaymentRail implements WireRailInterface
{
    /**
     * Minimum wire amount in cents (typically $1,000 or more for cost-efficiency).
     */
    private const DOMESTIC_MINIMUM_CENTS = 100; // $1.00 technical minimum

    /**
     * Maximum wire amount in cents per transaction.
     */
    private const DOMESTIC_MAXIMUM_CENTS = 99999999999; // $999,999,999.99

    /**
     * Minimum international wire amount.
     */
    private const INTERNATIONAL_MINIMUM_CENTS = 10000; // $100.00

    /**
     * Maximum international wire amount.
     */
    private const INTERNATIONAL_MAXIMUM_CENTS = 99999999999; // $999,999,999.99

    /**
     * Standard domestic wire fee in cents.
     */
    private const DOMESTIC_FEE_CENTS = 2500; // $25.00

    /**
     * Standard international wire fee in cents.
     */
    private const INTERNATIONAL_FEE_CENTS = 4500; // $45.00

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
        return RailType::WIRE;
    }

    protected function buildCapabilities(): RailCapabilities
    {
        return new RailCapabilities(
            railType: RailType::WIRE,
            supportedCurrencies: $this->getSupportedCurrencies(),
            minimumAmountCents: self::DOMESTIC_MINIMUM_CENTS,
            maximumAmountCents: self::DOMESTIC_MAXIMUM_CENTS,
            settlementDays: 0, // Same-day domestic
            isRealTime: true,
            supportsRefunds: false, // Wire recalls are complex
            supportsPartialRefunds: false,
            supportsRecurring: false,
            requiresBeneficiaryAddress: true,
        );
    }

    /**
     * Initiate a wire transfer.
     */
    public function initiateTransfer(WireTransferRequest $request): WireTransferResult
    {
        $this->ensureAvailable();

        // Validate the request
        $errors = $this->validateWireRequest($request);
        if (!empty($errors)) {
            throw WireValidationException::multipleErrors($errors);
        }

        $wireId = $this->generateReference('WIRE');
        $estimatedFee = $this->getEstimatedFee($request->wireType, $request->amount);

        // Create wire instruction
        $instruction = $this->createWireInstruction(
            $request->beneficiaryName,
            $request->beneficiaryAccountNumber,
            $request->beneficiarySwiftCode,
            $request->beneficiaryIban,
            $request->beneficiaryAddress,
            $request->intermediarySwiftCode,
            $request->intermediaryName,
            $request->purpose,
        );

        // Persist transaction
        $this->transactionPersist->save($wireId, [
            'type' => 'wire_transfer',
            'wire_type' => $request->wireType->value,
            'amount_cents' => $this->toAmountCents($request->amount),
            'currency' => $request->amount->getCurrency(),
            'beneficiary_name' => $request->beneficiaryName,
            'status' => 'PENDING',
            'fee_cents' => $estimatedFee,
            'created_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Wire transfer initiated', $wireId, [
            'wire_type' => $request->wireType->value,
            'amount' => $request->amount->getAmount(),
            'currency' => $request->amount->getCurrency(),
            'beneficiary' => $request->beneficiaryName,
        ]);

        return new WireTransferResult(
            success: true,
            wireId: $wireId,
            wireType: $request->wireType,
            status: 'PENDING',
            amountCents: $this->toAmountCents($request->amount),
            currency: $request->amount->getCurrency(),
            estimatedFeeCents: $estimatedFee,
            estimatedSettlement: $this->getEstimatedSettlement($request->wireType),
        );
    }

    /**
     * Create a wire instruction.
     */
    public function createWireInstruction(
        string $beneficiaryName,
        string $accountNumber,
        ?string $swiftCode = null,
        ?string $iban = null,
        ?string $address = null,
        ?string $intermediarySwiftCode = null,
        ?string $intermediaryName = null,
        ?string $purpose = null,
    ): WireInstruction {
        $parsedSwift = $swiftCode !== null ? SwiftCode::fromString($swiftCode) : null;
        $parsedIban = $iban !== null ? Iban::fromString($iban) : null;
        $parsedIntermediary = $intermediarySwiftCode !== null 
            ? SwiftCode::fromString($intermediarySwiftCode) 
            : null;

        return new WireInstruction(
            beneficiaryName: $beneficiaryName,
            beneficiaryAccountNumber: $accountNumber,
            beneficiarySwiftCode: $parsedSwift,
            beneficiaryIban: $parsedIban,
            beneficiaryAddress: $address,
            intermediaryBankSwiftCode: $parsedIntermediary,
            intermediaryBankName: $intermediaryName,
            purposeOfPayment: $purpose,
        );
    }

    /**
     * Get wire transfer status.
     */
    public function getWireStatus(string $wireId): WireTransferResult
    {
        $transaction = $this->transactionQuery->findById($wireId);

        if ($transaction === null) {
            return new WireTransferResult(
                success: false,
                wireId: $wireId,
                wireType: WireType::DOMESTIC,
                status: 'NOT_FOUND',
                amountCents: 0,
                currency: 'USD',
                errorMessage: 'Wire transfer not found',
            );
        }

        return new WireTransferResult(
            success: true,
            wireId: $wireId,
            wireType: WireType::from($transaction->metadata['wire_type'] ?? 'domestic'),
            status: $transaction->status,
            amountCents: $transaction->metadata['amount_cents'] ?? 0,
            currency: $transaction->metadata['currency'] ?? 'USD',
            confirmationNumber: $transaction->metadata['confirmation_number'] ?? null,
        );
    }

    /**
     * Cancel a wire transfer (request recall).
     */
    public function cancelWire(string $wireId, string $reason): bool
    {
        return $this->cancelTransaction($wireId, $reason);
    }

    /**
     * Get the wire cutoff time.
     */
    public function getWireCutoffTime(WireType $wireType): \DateTimeImmutable
    {
        $cutoffTimes = $this->configuration->getCutoffTimes($this->getRailType());
        
        $cutoffKey = match ($wireType) {
            WireType::DOMESTIC => 'domestic',
            WireType::INTERNATIONAL => 'international',
            WireType::URGENT => 'urgent',
        };

        $closeTime = $cutoffTimes[$cutoffKey] ?? '17:00:00';

        $today = new \DateTimeImmutable();
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $today->format('Y-m-d') . ' ' . $closeTime
        );
    }

    /**
     * Check if same-day processing is available.
     */
    public function canProcessSameDay(WireType $wireType): bool
    {
        $now = new \DateTimeImmutable();
        $cutoff = $this->getWireCutoffTime($wireType);

        // Check if before cutoff and it's a business day
        $dayOfWeek = (int) $now->format('N');
        if ($dayOfWeek >= 6) { // Weekend
            return false;
        }

        return $now < $cutoff;
    }

    /**
     * Validate a SWIFT/BIC code.
     */
    public function validateSwiftCode(string $swiftCode): bool
    {
        try {
            SwiftCode::fromString($swiftCode);
            return true;
        } catch (InvalidSwiftCodeException) {
            return false;
        }
    }

    /**
     * Validate an IBAN.
     */
    public function validateIban(string $iban): bool
    {
        try {
            Iban::fromString($iban);
            return true;
        } catch (InvalidIbanException) {
            return false;
        }
    }

    /**
     * Get estimated fee for a wire transfer.
     */
    public function getEstimatedFee(WireType $wireType, Money $amount): int
    {
        return match ($wireType) {
            WireType::DOMESTIC => self::DOMESTIC_FEE_CENTS,
            WireType::INTERNATIONAL => self::INTERNATIONAL_FEE_CENTS,
            WireType::URGENT => self::DOMESTIC_FEE_CENTS * 2, // Premium for urgent
        };
    }

    /**
     * Get supported currencies.
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array
    {
        return [
            'USD', 'EUR', 'GBP', 'JPY', 'CHF', 'AUD', 'CAD', 'NZD',
            'SGD', 'HKD', 'NOK', 'SEK', 'DKK', 'ZAR', 'MXN', 'BRL',
            'INR', 'CNY', 'KRW', 'TWD', 'THB', 'MYR', 'PHP', 'IDR',
        ];
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
     * Cancel a transaction (request recall).
     */
    public function cancelTransaction(string $transactionId, string $reason): bool
    {
        $transaction = $this->transactionQuery->findById($transactionId);

        if ($transaction === null) {
            return false;
        }

        // Wire recalls are complex - can only request, not guarantee
        if (!in_array($transaction->status, ['PENDING', 'PROCESSING'])) {
            $this->logger->warning('Cannot recall completed or failed wire', [
                'transaction_id' => $transactionId,
                'current_status' => $transaction->status,
            ]);
            return false;
        }

        $this->transactionPersist->updateStatus($transactionId, 'RECALL_REQUESTED');
        $this->logOperation('Wire recall requested', $transactionId, ['reason' => $reason]);

        return true;
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
     * Validate a wire transfer request.
     *
     * @return array<string>
     */
    private function validateWireRequest(WireTransferRequest $request): array
    {
        $errors = [];

        // Amount validation
        $amountCents = $this->toAmountCents($request->amount);
        
        if ($request->wireType === WireType::INTERNATIONAL) {
            if ($amountCents < self::INTERNATIONAL_MINIMUM_CENTS) {
                $errors[] = sprintf(
                    'International wire minimum is $%.2f',
                    self::INTERNATIONAL_MINIMUM_CENTS / 100
                );
            }
        } else {
            if ($amountCents < self::DOMESTIC_MINIMUM_CENTS) {
                $errors[] = sprintf(
                    'Domestic wire minimum is $%.2f',
                    self::DOMESTIC_MINIMUM_CENTS / 100
                );
            }
        }

        // Beneficiary validation
        if (empty($request->beneficiaryName)) {
            $errors[] = 'Beneficiary name is required.';
        }

        if (empty($request->beneficiaryAccountNumber)) {
            $errors[] = 'Beneficiary account number is required.';
        }

        // International wire specific validation
        if ($request->wireType === WireType::INTERNATIONAL) {
            if (empty($request->beneficiarySwiftCode)) {
                $errors[] = 'SWIFT/BIC code is required for international wires.';
            } elseif (!$this->validateSwiftCode($request->beneficiarySwiftCode)) {
                $errors[] = 'Invalid SWIFT/BIC code format.';
            }

            if (empty($request->beneficiaryAddress)) {
                $errors[] = 'Beneficiary address is required for international wires.';
            }
        }

        // Currency validation
        if (!$this->supportsCurrency($request->amount->getCurrency())) {
            $errors[] = sprintf('Currency %s is not supported.', $request->amount->getCurrency());
        }

        return $errors;
    }

    /**
     * Get estimated settlement date/time.
     */
    private function getEstimatedSettlement(WireType $wireType): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable();

        if ($this->canProcessSameDay($wireType)) {
            return match ($wireType) {
                WireType::DOMESTIC => $now->modify('+2 hours'),
                WireType::URGENT => $now->modify('+30 minutes'),
                WireType::INTERNATIONAL => $now->modify('+24 hours'),
            };
        }

        // Next business day
        return $this->getNextBusinessDay();
    }

    /**
     * Get next business day.
     */
    private function getNextBusinessDay(): \DateTimeImmutable
    {
        $date = new \DateTimeImmutable();
        $dayOfWeek = (int) $date->format('N');

        if ($dayOfWeek === 5) { // Friday
            return $date->modify('+3 days');
        }
        if ($dayOfWeek === 6) { // Saturday
            return $date->modify('+2 days');
        }

        return $date->modify('+1 day');
    }

    /**
     * Convert Money to cents.
     */
    private function toAmountCents(Money $money): int
    {
        return (int) ($money->getAmount() * 100);
    }
}
