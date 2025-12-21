<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Rails;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\CheckRailInterface;
use Nexus\PaymentRails\Contracts\RailConfigurationInterface;
use Nexus\PaymentRails\Contracts\RailTransactionPersistInterface;
use Nexus\PaymentRails\Contracts\RailTransactionQueryInterface;
use Nexus\PaymentRails\DTOs\CheckRequest;
use Nexus\PaymentRails\DTOs\CheckResult;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\Enums\CheckStatus;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Exceptions\InvalidCheckNumberException;
use Nexus\PaymentRails\Exceptions\RailUnavailableException;
use Nexus\PaymentRails\ValueObjects\Check;
use Nexus\PaymentRails\ValueObjects\CheckNumber;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Check payment rail implementation.
 *
 * Handles check operations including:
 * - Check issuance
 * - Positive pay file generation
 * - Check status tracking
 * - Stop payment processing
 * - Check void and reissue
 */
final class CheckRail extends AbstractPaymentRail implements CheckRailInterface
{
    /**
     * Maximum check amount in cents (often limited by positive pay systems).
     */
    private const MAXIMUM_AMOUNT_CENTS = 999999999; // $9,999,999.99

    /**
     * Minimum check amount.
     */
    private const MINIMUM_AMOUNT_CENTS = 1; // $0.01

    /**
     * Standard check clearing days.
     */
    private const STANDARD_CLEARING_DAYS = 5;

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
        return RailType::CHECK;
    }

    protected function buildCapabilities(): RailCapabilities
    {
        return new RailCapabilities(
            railType: RailType::CHECK,
            supportedCurrencies: ['USD'],
            minimumAmountCents: self::MINIMUM_AMOUNT_CENTS,
            maximumAmountCents: self::MAXIMUM_AMOUNT_CENTS,
            settlementDays: self::STANDARD_CLEARING_DAYS,
            isRealTime: false,
            supportsRefunds: false, // Checks require void/reissue
            supportsPartialRefunds: false,
            supportsRecurring: false,
            requiresBeneficiaryAddress: true,
        );
    }

    /**
     * Issue a new check.
     */
    public function issueCheck(CheckRequest $request): CheckResult
    {
        $this->ensureAvailable();

        // Validate request
        $errors = $this->validateCheckRequest($request);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode('; ', $errors));
        }

        // Generate or use provided check number
        $checkNumber = $request->checkNumber ?? $this->generateCheckNumber();
        $checkId = $this->generateReference('CHK');

        $check = new Check(
            checkNumber: $checkNumber,
            amountCents: $this->toAmountCents($request->amount),
            currency: $request->amount->getCurrency(),
            payeeName: $request->payeeName,
            payeeAddress: $request->payeeAddress,
            issueDate: new \DateTimeImmutable(),
            memo: $request->memo,
            status: CheckStatus::ISSUED,
        );

        // Persist the check
        $this->transactionPersist->save($checkId, [
            'type' => 'check',
            'check_number' => $checkNumber->getValue(),
            'amount_cents' => $check->amountCents,
            'currency' => $check->currency,
            'payee_name' => $check->payeeName,
            'payee_address' => $check->payeeAddress,
            'issue_date' => $check->issueDate->format(\DateTimeInterface::RFC3339),
            'memo' => $check->memo,
            'status' => CheckStatus::ISSUED->value,
        ]);

        $this->logOperation('Check issued', $checkId, [
            'check_number' => $checkNumber->getMasked(),
            'amount_cents' => $check->amountCents,
            'payee' => $check->payeeName,
        ]);

        return new CheckResult(
            success: true,
            checkId: $checkId,
            checkNumber: $checkNumber,
            status: CheckStatus::ISSUED,
            amountCents: $check->amountCents,
            currency: $check->currency,
            payeeName: $check->payeeName,
            issueDate: $check->issueDate,
        );
    }

    /**
     * Get check status.
     */
    public function getCheckStatus(string $checkId): CheckResult
    {
        $transaction = $this->transactionQuery->findById($checkId);

        if ($transaction === null) {
            return new CheckResult(
                success: false,
                checkId: $checkId,
                status: CheckStatus::VOID,
                amountCents: 0,
                currency: 'USD',
                payeeName: '',
                errorMessage: 'Check not found',
            );
        }

        $metadata = $transaction->metadata;

        return new CheckResult(
            success: true,
            checkId: $checkId,
            checkNumber: CheckNumber::fromString($metadata['check_number']),
            status: CheckStatus::from($metadata['status']),
            amountCents: $metadata['amount_cents'],
            currency: $metadata['currency'],
            payeeName: $metadata['payee_name'],
            issueDate: new \DateTimeImmutable($metadata['issue_date']),
            clearedDate: isset($metadata['cleared_date']) 
                ? new \DateTimeImmutable($metadata['cleared_date']) 
                : null,
        );
    }

    /**
     * Stop payment on a check.
     */
    public function stopPayment(string $checkId, string $reason): bool
    {
        $transaction = $this->transactionQuery->findById($checkId);

        if ($transaction === null) {
            $this->logger->warning('Stop payment requested for unknown check', [
                'check_id' => $checkId,
            ]);
            return false;
        }

        $currentStatus = CheckStatus::from($transaction->metadata['status']);

        // Can only stop issued or printed checks
        if (!in_array($currentStatus, [CheckStatus::ISSUED, CheckStatus::PRINTED, CheckStatus::MAILED])) {
            $this->logger->warning('Cannot stop payment on check with status', [
                'check_id' => $checkId,
                'current_status' => $currentStatus->value,
            ]);
            return false;
        }

        $this->transactionPersist->updateStatus($checkId, CheckStatus::STOP_PAYMENT->value);
        $this->transactionPersist->updateMetadata($checkId, [
            'stop_payment_reason' => $reason,
            'stop_payment_date' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Stop payment issued', $checkId, ['reason' => $reason]);

        return true;
    }

    /**
     * Void a check.
     */
    public function voidCheck(string $checkId, string $reason): bool
    {
        $transaction = $this->transactionQuery->findById($checkId);

        if ($transaction === null) {
            return false;
        }

        $currentStatus = CheckStatus::from($transaction->metadata['status']);

        // Cannot void already cashed or voided checks
        if (in_array($currentStatus, [CheckStatus::CASHED, CheckStatus::CLEARED, CheckStatus::VOID])) {
            $this->logger->warning('Cannot void check with status', [
                'check_id' => $checkId,
                'current_status' => $currentStatus->value,
            ]);
            return false;
        }

        $this->transactionPersist->updateStatus($checkId, CheckStatus::VOID->value);
        $this->transactionPersist->updateMetadata($checkId, [
            'void_reason' => $reason,
            'void_date' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('Check voided', $checkId, ['reason' => $reason]);

        return true;
    }

    /**
     * Reissue a voided or stopped check.
     */
    public function reissueCheck(string $originalCheckId): CheckResult
    {
        $transaction = $this->transactionQuery->findById($originalCheckId);

        if ($transaction === null) {
            return new CheckResult(
                success: false,
                checkId: $originalCheckId,
                status: CheckStatus::VOID,
                amountCents: 0,
                currency: 'USD',
                payeeName: '',
                errorMessage: 'Original check not found',
            );
        }

        $currentStatus = CheckStatus::from($transaction->metadata['status']);

        // Can only reissue voided or stop payment checks
        if (!in_array($currentStatus, [CheckStatus::VOID, CheckStatus::STOP_PAYMENT, CheckStatus::STALE])) {
            return new CheckResult(
                success: false,
                checkId: $originalCheckId,
                status: $currentStatus,
                amountCents: $transaction->metadata['amount_cents'],
                currency: $transaction->metadata['currency'],
                payeeName: $transaction->metadata['payee_name'],
                errorMessage: 'Check cannot be reissued from current status',
            );
        }

        // Create new check request from original
        $request = new CheckRequest(
            payeeName: $transaction->metadata['payee_name'],
            payeeAddress: $transaction->metadata['payee_address'],
            amount: Money::fromCents($transaction->metadata['amount_cents'], $transaction->metadata['currency']),
            memo: $transaction->metadata['memo'] ?? null,
        );

        // Update original to reflect reissue
        $this->transactionPersist->updateMetadata($originalCheckId, [
            'reissued' => true,
            'reissue_date' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ]);

        return $this->issueCheck($request);
    }

    /**
     * Generate positive pay file.
     *
     * @param array<string> $checkIds
     */
    public function generatePositivePayFile(array $checkIds): string
    {
        $lines = [];
        
        // Header
        $lines[] = $this->formatPositivePayHeader();

        foreach ($checkIds as $checkId) {
            $transaction = $this->transactionQuery->findById($checkId);
            
            if ($transaction === null) {
                continue;
            }

            $metadata = $transaction->metadata;
            $lines[] = $this->formatPositivePayLine(
                checkNumber: $metadata['check_number'],
                amountCents: $metadata['amount_cents'],
                issueDate: new \DateTimeImmutable($metadata['issue_date']),
                payeeName: $metadata['payee_name'],
            );
        }

        // Trailer
        $lines[] = $this->formatPositivePayTrailer(count($lines) - 1);

        $this->logOperation('Positive pay file generated', 'BATCH', [
            'check_count' => count($checkIds),
        ]);

        return implode("\n", $lines);
    }

    /**
     * Check if a check number is valid.
     */
    public function validateCheckNumber(string $checkNumber): bool
    {
        try {
            CheckNumber::fromString($checkNumber);
            return true;
        } catch (InvalidCheckNumberException) {
            return false;
        }
    }

    /**
     * Get days until check becomes stale.
     */
    public function getDaysUntilStale(string $checkId): ?int
    {
        $transaction = $this->transactionQuery->findById($checkId);

        if ($transaction === null) {
            return null;
        }

        $issueDate = new \DateTimeImmutable($transaction->metadata['issue_date']);
        $staleDate = $issueDate->modify('+180 days'); // 6 months is typical stale date
        
        $now = new \DateTimeImmutable();
        $diff = $now->diff($staleDate);

        return $diff->invert ? -$diff->days : $diff->days;
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
     * Cancel a transaction (void the check).
     */
    public function cancelTransaction(string $transactionId, string $reason): bool
    {
        return $this->voidCheck($transactionId, $reason);
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
     * Validate a check request.
     *
     * @return array<string>
     */
    private function validateCheckRequest(CheckRequest $request): array
    {
        $errors = [];

        $amountCents = $this->toAmountCents($request->amount);

        if ($amountCents < self::MINIMUM_AMOUNT_CENTS) {
            $errors[] = 'Check amount is below minimum.';
        }

        if ($amountCents > self::MAXIMUM_AMOUNT_CENTS) {
            $errors[] = 'Check amount exceeds maximum.';
        }

        if (empty($request->payeeName)) {
            $errors[] = 'Payee name is required.';
        }

        if (strlen($request->payeeName) > 40) {
            $errors[] = 'Payee name exceeds maximum length (40 characters).';
        }

        if (empty($request->payeeAddress)) {
            $errors[] = 'Payee address is required.';
        }

        return $errors;
    }

    /**
     * Generate a check number.
     */
    private function generateCheckNumber(): CheckNumber
    {
        // In production, this would use a sequence from the configuration
        $sequence = $this->configuration->getNextCheckNumber();
        
        return CheckNumber::fromString($sequence);
    }

    /**
     * Format positive pay header.
     */
    private function formatPositivePayHeader(): string
    {
        $now = new \DateTimeImmutable();
        $accountNumber = $this->configuration->getBankAccountNumber();

        return sprintf(
            'H%s%s%s',
            str_pad($accountNumber, 16, '0', STR_PAD_LEFT),
            $now->format('Ymd'),
            $now->format('His')
        );
    }

    /**
     * Format positive pay detail line.
     */
    private function formatPositivePayLine(
        string $checkNumber,
        int $amountCents,
        \DateTimeImmutable $issueDate,
        string $payeeName,
    ): string {
        return sprintf(
            'D%s%012d%s%-40s',
            str_pad($checkNumber, 10, '0', STR_PAD_LEFT),
            $amountCents,
            $issueDate->format('Ymd'),
            substr($payeeName, 0, 40)
        );
    }

    /**
     * Format positive pay trailer.
     */
    private function formatPositivePayTrailer(int $recordCount): string
    {
        return sprintf('T%010d', $recordCount);
    }

    /**
     * Convert Money to cents.
     */
    private function toAmountCents(Money $money): int
    {
        return (int) ($money->getAmount() * 100);
    }
}
