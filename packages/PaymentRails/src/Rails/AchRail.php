<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Rails;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\AchRailInterface;
use Nexus\PaymentRails\Contracts\NachaFormatterInterface;
use Nexus\PaymentRails\Contracts\RailConfigurationInterface;
use Nexus\PaymentRails\Contracts\RailTransactionPersistInterface;
use Nexus\PaymentRails\Contracts\RailTransactionQueryInterface;
use Nexus\PaymentRails\DTOs\AchBatchRequest;
use Nexus\PaymentRails\DTOs\AchBatchResult;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\Enums\AchReturnCode;
use Nexus\PaymentRails\Enums\EntryStatus;
use Nexus\PaymentRails\Enums\FileStatus;
use Nexus\PaymentRails\Enums\NocCode;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\Enums\TransactionCode;
use Nexus\PaymentRails\Exceptions\AchValidationException;
use Nexus\PaymentRails\Exceptions\InvalidRoutingNumberException;
use Nexus\PaymentRails\Exceptions\RailUnavailableException;
use Nexus\PaymentRails\ValueObjects\AchBatch;
use Nexus\PaymentRails\ValueObjects\AchEntry;
use Nexus\PaymentRails\ValueObjects\AchFile;
use Nexus\PaymentRails\ValueObjects\AchNotificationOfChange;
use Nexus\PaymentRails\ValueObjects\AchReturn;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * ACH (Automated Clearing House) payment rail implementation.
 *
 * Handles ACH transactions including:
 * - Direct deposits (payroll, vendor payments)
 * - Direct debits (collections, recurring payments)
 * - Same-day ACH
 * - NACHA file generation and parsing
 * - Returns and NOC processing
 */
final class AchRail extends AbstractPaymentRail implements AchRailInterface
{
    /**
     * @var array<string, AchBatch>
     */
    private array $pendingBatches = [];

    public function __construct(
        RailConfigurationInterface $configuration,
        private readonly NachaFormatterInterface $nachaFormatter,
        private readonly RailTransactionQueryInterface $transactionQuery,
        private readonly RailTransactionPersistInterface $transactionPersist,
        LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($configuration, $logger);
    }

    public function getRailType(): RailType
    {
        return RailType::ACH;
    }

    protected function buildCapabilities(): RailCapabilities
    {
        return new RailCapabilities(
            railType: RailType::ACH,
            supportedCurrencies: ['USD'],
            minimumAmountCents: 1,
            maximumAmountCents: 999999999999, // $9.99 billion (practical limit)
            settlementDays: 2, // Standard ACH T+2
            isRealTime: false,
            supportsRefunds: true,
            supportsPartialRefunds: true,
            supportsRecurring: true,
            requiresBeneficiaryAddress: false,
        );
    }

    /**
     * Create an ACH batch from a request.
     */
    public function createBatch(AchBatchRequest $request): AchBatchResult
    {
        $this->ensureAvailable();

        // Validate the batch request
        $errors = $this->validateBatchRequest($request);
        if (!empty($errors)) {
            throw AchValidationException::multipleErrors($errors);
        }

        $batchId = $this->generateReference('BATCH');
        $entries = [];
        $totalDebitCents = 0;
        $totalCreditCents = 0;

        foreach ($request->entries as $index => $entryRequest) {
            $traceNumber = $this->generateTraceNumber($index);
            
            $entry = new AchEntry(
                traceNumber: $traceNumber,
                transactionCode: $entryRequest->transactionCode,
                routingNumber: $entryRequest->routingNumber,
                accountNumber: $entryRequest->accountNumber,
                amountCents: $entryRequest->amountCents,
                individualId: $entryRequest->individualId,
                individualName: $entryRequest->individualName,
                discretionaryData: $entryRequest->discretionaryData,
                addendaRecord: $entryRequest->addendaRecord,
                status: EntryStatus::PENDING,
            );

            $entries[] = $entry;

            if ($entryRequest->transactionCode->isDebit()) {
                $totalDebitCents += $entryRequest->amountCents;
            } else {
                $totalCreditCents += $entryRequest->amountCents;
            }
        }

        $batch = new AchBatch(
            batchNumber: 1,
            companyName: $request->companyName,
            companyDiscretionaryData: $request->companyDiscretionaryData,
            companyId: $request->companyId,
            secCode: $request->secCode,
            companyEntryDescription: $request->entryDescription,
            effectiveEntryDate: $request->effectiveDate,
            entries: $entries,
            originatorStatusCode: '1',
            originatingDfiId: substr($this->configuration->getImmediateOrigin(), 0, 8),
        );

        $this->pendingBatches[$batchId] = $batch;

        $this->logOperation('Batch created', $batchId, [
            'entry_count' => count($entries),
            'total_debit_cents' => $totalDebitCents,
            'total_credit_cents' => $totalCreditCents,
            'sec_code' => $request->secCode->value,
        ]);

        return new AchBatchResult(
            success: true,
            batchId: $batchId,
            entryCount: count($entries),
            totalDebitCents: $totalDebitCents,
            totalCreditCents: $totalCreditCents,
            effectiveDate: $request->effectiveDate,
            traceNumbers: array_map(fn(AchEntry $e) => $e->traceNumber, $entries),
        );
    }

    /**
     * Submit an ACH file for processing.
     *
     * @param array<AchBatch> $batches
     */
    public function submitFile(array $batches): AchFile
    {
        $this->ensureAvailable();

        if (empty($batches)) {
            throw AchValidationException::missingRequiredField('batches');
        }

        $fileId = $this->generateReference('FILE');
        $now = new \DateTimeImmutable();

        $file = new AchFile(
            fileId: $fileId,
            immediateDestination: $this->configuration->getImmediateDestination(),
            immediateOrigin: $this->configuration->getImmediateOrigin(),
            fileCreationDate: $now,
            fileCreationTime: $now,
            fileIdModifier: 'A',
            batches: $batches,
            status: FileStatus::PENDING,
        );

        // Persist the file
        $this->transactionPersist->save($file->fileId, [
            'type' => 'ach_file',
            'status' => $file->status->value,
            'batch_count' => count($batches),
            'created_at' => $now->format(\DateTimeInterface::RFC3339),
        ]);

        $this->logOperation('File submitted', $fileId, [
            'batch_count' => count($batches),
            'total_entries' => $file->getTotalEntryCount(),
        ]);

        return $file;
    }

    /**
     * Generate NACHA formatted file content.
     */
    public function generateNachaFile(AchFile $file): string
    {
        return $this->nachaFormatter->generateFile($file);
    }

    /**
     * Parse a NACHA formatted file.
     */
    public function parseNachaFile(string $content): AchFile
    {
        return $this->nachaFormatter->parseFile($content);
    }

    /**
     * Send a prenote (zero-dollar test transaction).
     */
    public function sendPrenote(
        RoutingNumber $routingNumber,
        string $accountNumber,
        TransactionCode $accountType,
        string $individualName,
        string $individualId,
    ): string {
        $this->ensureAvailable();

        // Prenotes are always $0.00
        $prenoteCode = $accountType->isDebit() 
            ? TransactionCode::CHECKING_PRENOTE_DEBIT 
            : TransactionCode::CHECKING_PRENOTE_CREDIT;

        $traceNumber = $this->generateTraceNumber(0);

        $entry = new AchEntry(
            traceNumber: $traceNumber,
            transactionCode: $prenoteCode,
            routingNumber: $routingNumber,
            accountNumber: $accountNumber,
            amountCents: 0,
            individualId: $individualId,
            individualName: $individualName,
            status: EntryStatus::PENDING,
        );

        $this->transactionPersist->save($traceNumber, [
            'type' => 'prenote',
            'routing_number' => $routingNumber->getMasked(),
            'individual_name' => $individualName,
            'status' => EntryStatus::PENDING->value,
        ]);

        $this->logOperation('Prenote sent', $traceNumber, [
            'routing_number' => $routingNumber->getMasked(),
        ]);

        return $traceNumber;
    }

    /**
     * Process an ACH return.
     */
    public function processReturn(AchReturn $return): void
    {
        $existingTransaction = $this->transactionQuery->findByReference($return->originalTraceNumber);

        if ($existingTransaction === null) {
            $this->logger->warning('Return received for unknown transaction', [
                'trace_number' => $return->originalTraceNumber,
                'return_code' => $return->returnCode->value,
            ]);
            return;
        }

        $this->transactionPersist->markFailed(
            $return->originalTraceNumber,
            sprintf('ACH Return: %s - %s', $return->returnCode->value, $return->returnCode->getDescription())
        );

        $this->logOperation('Return processed', $return->originalTraceNumber, [
            'return_code' => $return->returnCode->value,
            'is_retriable' => $return->returnCode->isRetriable(),
        ]);
    }

    /**
     * Process a Notification of Change (NOC).
     */
    public function processNoc(AchNotificationOfChange $noc): void
    {
        $this->logOperation('NOC received', $noc->originalTraceNumber, [
            'noc_code' => $noc->nocCode->value,
            'corrected_data' => $noc->correctedData,
        ]);

        // NOCs require merchant to update records within 6 banking days
        // Log for follow-up but don't automatically update
    }

    /**
     * Check if same-day ACH is available.
     */
    public function isSameDayAvailable(): bool
    {
        $now = new \DateTimeImmutable();
        $cutoff = $this->getSameDayCutoffTime();

        if ($cutoff === null) {
            return false;
        }

        return $now <= $cutoff;
    }

    /**
     * Get the cutoff time for today.
     */
    public function getCutoffTime(): \DateTimeImmutable
    {
        $cutoffTimes = $this->configuration->getCutoffTimes($this->getRailType());
        $closeTime = $cutoffTimes['close'] ?? '17:00:00';

        $today = new \DateTimeImmutable();
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $today->format('Y-m-d') . ' ' . $closeTime
        );
    }

    /**
     * Get the next effective entry date.
     */
    public function getNextEffectiveDate(bool $sameDayRequested = false): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable();

        if ($sameDayRequested && $this->isSameDayAvailable()) {
            return $now;
        }

        // Standard ACH: next business day
        $nextDay = $now->modify('+1 business day');
        
        // Adjust for weekends
        $dayOfWeek = (int) $nextDay->format('N');
        if ($dayOfWeek === 6) { // Saturday
            $nextDay = $nextDay->modify('+2 days');
        } elseif ($dayOfWeek === 7) { // Sunday
            $nextDay = $nextDay->modify('+1 day');
        }

        return $nextDay;
    }

    /**
     * Validate a routing number.
     */
    public function validateRoutingNumber(string $routingNumber): bool
    {
        try {
            RoutingNumber::fromString($routingNumber);
            return true;
        } catch (InvalidRoutingNumberException) {
            return false;
        }
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
     * Cancel a transaction.
     */
    public function cancelTransaction(string $transactionId, string $reason): bool
    {
        $transaction = $this->transactionQuery->findById($transactionId);

        if ($transaction === null) {
            return false;
        }

        // Can only cancel pending transactions
        if ($transaction->status !== 'PENDING') {
            $this->logger->warning('Cannot cancel non-pending ACH transaction', [
                'transaction_id' => $transactionId,
                'current_status' => $transaction->status,
            ]);
            return false;
        }

        $this->transactionPersist->updateStatus($transactionId, 'CANCELLED');
        $this->logOperation('Transaction cancelled', $transactionId, ['reason' => $reason]);

        return true;
    }

    /**
     * Ensure the rail is available for processing.
     */
    private function ensureAvailable(): void
    {
        if (!$this->isAvailable()) {
            throw RailUnavailableException::outsideOperatingHours($this->getRailType());
        }
    }

    /**
     * Get same-day ACH cutoff time.
     */
    private function getSameDayCutoffTime(): ?\DateTimeImmutable
    {
        $cutoffTimes = $this->configuration->getCutoffTimes($this->getRailType());
        
        if (!isset($cutoffTimes['same_day'])) {
            return null;
        }

        $today = new \DateTimeImmutable();
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $today->format('Y-m-d') . ' ' . $cutoffTimes['same_day']
        );
    }

    /**
     * Validate a batch request.
     *
     * @return array<string>
     */
    private function validateBatchRequest(AchBatchRequest $request): array
    {
        $errors = [];

        if (empty($request->entries)) {
            $errors[] = 'Batch must contain at least one entry.';
        }

        if (count($request->entries) > 9999999) {
            $errors[] = 'Batch exceeds maximum entry count (9,999,999).';
        }

        // Validate effective date
        $now = new \DateTimeImmutable();
        $maxFutureDate = $now->modify('+365 days');

        if ($request->effectiveDate < $now->setTime(0, 0)) {
            $errors[] = 'Effective date cannot be in the past.';
        }

        if ($request->effectiveDate > $maxFutureDate) {
            $errors[] = 'Effective date cannot be more than 365 days in the future.';
        }

        // Validate SEC code compatibility with entries
        foreach ($request->entries as $index => $entry) {
            $secCodeErrors = $this->validateSecCodeForEntry($request->secCode, $entry);
            foreach ($secCodeErrors as $error) {
                $errors[] = sprintf('Entry %d: %s', $index + 1, $error);
            }
        }

        return $errors;
    }

    /**
     * Validate SEC code compatibility with entry.
     *
     * @return array<string>
     */
    private function validateSecCodeForEntry(SecCode $secCode, object $entry): array
    {
        $errors = [];

        // Consumer vs Corporate validation
        if ($secCode->isConsumer() && $entry->transactionCode->isCorporate()) {
            $errors[] = 'Consumer SEC code cannot be used with corporate transaction code.';
        }

        // PPD requires individual ID
        if ($secCode === SecCode::PPD && empty($entry->individualId)) {
            $errors[] = 'PPD requires individual ID.';
        }

        // WEB requires authorization
        if ($secCode === SecCode::WEB && empty($entry->discretionaryData)) {
            $errors[] = 'WEB requires authorization reference in discretionary data.';
        }

        return $errors;
    }

    /**
     * Generate a trace number.
     */
    private function generateTraceNumber(int $sequence): string
    {
        $odfiId = substr($this->configuration->getImmediateOrigin(), 0, 8);
        $sequenceNumber = str_pad((string) ($sequence + 1), 7, '0', STR_PAD_LEFT);

        return $odfiId . $sequenceNumber;
    }
}
