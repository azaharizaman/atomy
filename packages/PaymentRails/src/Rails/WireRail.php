<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Rails;

use Nexus\Common\Contracts\ClockInterface;
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
final readonly class WireRail extends AbstractPaymentRail implements WireRailInterface
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
        private RailTransactionQueryInterface $transactionQuery,
        private RailTransactionPersistInterface $transactionPersist,
        LoggerInterface $logger = new NullLogger(),
        ?ClockInterface $clock = null,
    ) {
        parent::__construct($configuration, $logger, $clock);
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
            minimumAmount: new Money(self::DOMESTIC_MINIMUM_CENTS, 'USD'),
            maximumAmount: new Money(self::DOMESTIC_MAXIMUM_CENTS, 'USD'),
            supportsCredit: true,
            supportsDebit: false,
            supportsScheduledPayments: true,
            supportsRecurring: false,
            supportsBatchProcessing: false,
            requiresPrenotification: false,
            typicalSettlementDays: 0,
            requiredFields: ['beneficiary_name', 'beneficiary_bank_name'],
            additionalCapabilities: [
                'is_real_time' => true,
                'supports_refunds' => false,
                'supports_partial_refunds' => false,
                'requires_beneficiary_address' => true,
            ],
        );
    }

    /**
     * Initiate a wire transfer.
     */
    public function initiateTransfer(WireTransferRequest $request): WireTransferResult
    {
        $this->ensureAvailable();

        $errors = $this->validateWireRequest($request);
        if (!empty($errors)) {
            throw WireValidationException::multipleErrors($errors);
        }

        $wireId = $this->generateReference('WIRE');
        $fee = $this->getEstimatedFee($request);
        $expectedSettlement = $this->getEstimatedSettlement($request->wireType);

        $wireResult = WireTransferResult::pending(
            transferId: $wireId,
            amount: $request->amount,
            wireType: $request->wireType,
            confirmationNumber: null,
            expectedSettlementDate: $expectedSettlement,
        );

        $transactionResult = new RailTransactionResult(
            transactionId: $wireResult->transferId,
            success: $wireResult->success,
            status: $wireResult->status,
            railType: $this->getRailType(),
            amount: $wireResult->amount,
            referenceNumber: $wireResult->getReferenceNumber(),
            errors: $wireResult->errors,
            metadata: [
                'wire_type' => $request->wireType->value,
                'beneficiary_name' => $request->beneficiaryName,
                'beneficiary_bank_name' => $request->beneficiaryBankName,
            ],
            fees: $fee,
            expectedSettlementDate: $expectedSettlement,
        );

        $this->transactionPersist->save($transactionResult);

        $this->logOperation('Wire transfer initiated', $wireId, [
            'wire_type' => $request->wireType->value,
            'amount_minor' => $request->amount->getAmountInMinorUnits(),
            'currency' => $request->amount->getCurrency(),
            'beneficiary' => $request->beneficiaryName,
        ]);

        return $wireResult;
    }

    /**
     * Create a wire instruction.
     */
    public function createWireInstruction(WireTransferRequest $request): WireInstruction
    {
        return new WireInstruction(
            wireType: $request->wireType,
            amount: $request->amount,
            beneficiaryName: $request->beneficiaryName,
            beneficiaryAccountNumber: $request->beneficiaryAccountNumber,
            beneficiaryBankName: $request->beneficiaryBankName,
            beneficiarySwiftCode: $request->beneficiarySwiftCode,
            beneficiaryIban: $request->beneficiaryIban,
            beneficiaryAddress: $request->beneficiaryAddress,
            beneficiaryRoutingNumber: $request->beneficiaryRoutingNumber,
            intermediaryBankName: $request->intermediaryBankName,
            intermediarySwiftCode: $request->intermediarySwiftCode,
            intermediaryAccountNumber: null,
            purposeOfPayment: $request->purposeOfPayment,
            additionalInstructions: null,
            originatorToBeneficiaryInfo: null,
            referenceForBeneficiary: $request->paymentReference,
        );
    }

    /**
     * Get wire transfer status.
     */
    public function getWireStatus(string $transferId): WireTransferResult
    {
        $transaction = $this->transactionQuery->findById($transferId);

        if ($transaction === null) {
            return WireTransferResult::failure(
                transferId: $transferId,
                amount: Money::zero('USD'),
                wireType: WireType::DOMESTIC,
                errors: ['Wire transfer not found'],
            );
        }

        $wireType = WireType::from($transaction->metadata['wire_type'] ?? WireType::DOMESTIC->value);

        return new WireTransferResult(
            transferId: $transferId,
            success: $transaction->success,
            status: $transaction->status,
            amount: $transaction->amount,
            wireType: $wireType,
            confirmationNumber: $transaction->metadata['confirmation_number'] ?? null,
            federalReferenceNumber: $transaction->metadata['federal_reference_number'] ?? null,
            swiftReferenceNumber: $transaction->metadata['swift_reference_number'] ?? null,
            fee: $transaction->fees,
            initiatedAt: $transaction->processedAt,
            completedAt: $transaction->settledAt,
            expectedSettlementDate: $transaction->expectedSettlementDate,
            errors: $transaction->errors,
        );
    }

    /**
     * Cancel a wire transfer (request recall).
     */
    public function cancelWire(string $transferId, string $reason): bool
    {
        return $this->cancelTransaction($transferId, $reason);
    }

    /**
     * Get the wire cutoff time.
     */
    public function getWireCutoffTime(WireType $wireType): \DateTimeImmutable
    {
        $cutoffTimes = $this->configuration->getCutoffTimes($this->getRailType());

        $cutoffKey = $wireType->value;

        if (isset($cutoffTimes[$cutoffKey]) && $cutoffTimes[$cutoffKey] instanceof \DateTimeImmutable) {
            return $cutoffTimes[$cutoffKey];
        }

        if (isset($cutoffTimes['close']) && $cutoffTimes['close'] instanceof \DateTimeImmutable) {
            return $cutoffTimes['close'];
        }

        return $this->clock->now()->setTime(17, 0);
    }

    /**
     * Check if same-day processing is available.
     */
    public function canProcessSameDay(WireType $wireType): bool
    {
        $now = $this->clock->now();
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
    public function getEstimatedFee(WireTransferRequest $request): Money
    {
        $baseFeeCents = match ($request->wireType) {
            WireType::INTERNATIONAL => self::INTERNATIONAL_FEE_CENTS,
            WireType::DOMESTIC,
            WireType::BOOK_TRANSFER,
            WireType::DRAWDOWN => self::DOMESTIC_FEE_CENTS,
        };

        $feeCents = $request->isUrgent ? $baseFeeCents * 2 : $baseFeeCents;

        return new Money($feeCents, $request->amount->getCurrency());
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
            return RailTransactionResult::failure(
                transactionId: $transactionId,
                railType: $this->getRailType(),
                amount: Money::zero('USD'),
                errors: ['Transaction not found'],
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
            throw RailUnavailableException::outsideOperatingHours(
                railType: $this->getRailType()->value,
                nextOpenTime: $this->getNextOpenTime(),
            );
        }
    }

    /**
     * Validate a wire transfer request.
     *
     * @return array<string>
     */
    private function validateWireRequest(WireTransferRequest $request): array
    {
        $errors = $request->validate();

        $amountMinor = $request->amount->getAmountInMinorUnits();

        if ($request->wireType === WireType::INTERNATIONAL) {
            if ($amountMinor < self::INTERNATIONAL_MINIMUM_CENTS) {
                $errors[] = sprintf(
                    'International wire minimum is $%.2f',
                    self::INTERNATIONAL_MINIMUM_CENTS / 100
                );
            }
        } else {
            if ($amountMinor < self::DOMESTIC_MINIMUM_CENTS) {
                $errors[] = sprintf(
                    'Domestic wire minimum is $%.2f',
                    self::DOMESTIC_MINIMUM_CENTS / 100
                );
            }
        }

        if (!$this->supportsCurrency($request->amount->getCurrency())) {
            $errors[] = sprintf('Currency %s is not supported.', $request->amount->getCurrency());
        }

        if ($request->wireType === WireType::INTERNATIONAL && $request->beneficiaryAddress === null) {
            $errors[] = 'Beneficiary address is required for international wires.';
        }

        return $errors;
    }

    /**
     * Get estimated settlement date/time.
     */
    private function getEstimatedSettlement(WireType $wireType): \DateTimeImmutable
    {
        $now = $this->clock->now();

        if ($this->canProcessSameDay($wireType)) {
            return match ($wireType) {
                WireType::BOOK_TRANSFER => $now->modify('+5 minutes'),
                WireType::DOMESTIC,
                WireType::DRAWDOWN => $now->modify('+2 hours'),
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
        $date = $this->clock->now();
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
        return $money->getAmountInMinorUnits();
    }

    private function getNextOpenTime(): \DateTimeImmutable
    {
        $cutoffTimes = $this->configuration->getCutoffTimes($this->getRailType());

        if (isset($cutoffTimes['open']) && $cutoffTimes['open'] instanceof \DateTimeImmutable) {
            $open = $cutoffTimes['open'];
            $now = $this->clock->now();

            if ($open > $now) {
                return $open;
            }

            // If today's open time has passed, use next business day at the same time.
            $next = $this->getNextBusinessDay();
            return $next->setTime(
                (int) $open->format('H'),
                (int) $open->format('i'),
                (int) $open->format('s'),
            );
        }

        return $this->clock->now()->modify('+1 hour');
    }
}
