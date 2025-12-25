<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Rails;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\DTOs\AchBatchResult;
use Nexus\PaymentRails\DTOs\AchBatchRequest;
use Nexus\PaymentRails\DTOs\AchEntryRequest;
use Nexus\PaymentRails\ValueObjects\AchFile;
use Nexus\PaymentRails\ValueObjects\AchBatch;
use Nexus\PaymentRails\ValueObjects\AchEntry;
use Nexus\PaymentRails\DTOs\AchPrenoteRequest;
use Nexus\PaymentRails\ValueObjects\AchReturn;
use Nexus\PaymentRails\Contracts\AchRailInterface;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Nexus\PaymentRails\Contracts\NachaFormatterInterface;
use Nexus\PaymentRails\Exceptions\RailUnavailableException;
use Nexus\PaymentRails\Contracts\RailConfigurationInterface;
use Nexus\PaymentRails\ValueObjects\AchNotificationOfChange;
use Nexus\PaymentRails\Contracts\RailTransactionQueryInterface;
use Nexus\PaymentRails\Contracts\RailTransactionPersistInterface;

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
            minimumAmount: new Money(1, 'USD'),
            maximumAmount: new Money(999999999999, 'USD'),
            supportsScheduledPayments: true,
            supportsRecurring: true,
            supportsBatchProcessing: true,
            requiresPrenotification: false,
            typicalSettlementDays: 2,
            requiredFields: ['routing_number', 'account_number', 'account_type'],
            additionalCapabilities: [
                'supports_refunds' => true,
                'supports_partial_refunds' => true,
                'requires_beneficiary_address' => false,
                'supports_same_day' => true,
            ],
        );
    }

    public function createBatch(AchBatchRequest $request): AchBatchResult
    {
        $this->ensureAvailable();

        $batchId = $request->batchId ?? $this->generateReference('BATCH');
        $errors = $request->validate();

        if ($request->isSameDay && !$this->isSameDayAvailable()) {
            $errors[] = 'Same-day ACH cutoff has passed.';
        }

        if (!empty($errors)) {
            return AchBatchResult::failure($batchId, $errors);
        }

        $effectiveDate = $request->effectiveEntryDate ?? $this->getNextEffectiveDate($request->isSameDay);
        [$entries, $traceNumbers] = $this->buildEntries($request->entries);

        $batch = new AchBatch(
            id: $batchId,
            secCode: $request->secCode,
            companyName: $request->companyName,
            companyId: $request->companyId,
            companyEntryDescription: $request->companyEntryDescription,
            originatingDfi: $request->originatingDfi,
            effectiveEntryDate: $effectiveDate,
            entries: $entries,
            companyDiscretionaryData: $request->companyDiscretionaryData,
        );

        $file = $this->buildFileFromRequest($request, $batch);
        $fileContents = $this->nachaFormatter->generateFile($file);

        $result = AchBatchResult::success(
            file: $file,
            batchId: $batchId,
            fileContents: $fileContents,
            traceNumbers: $traceNumbers,
        );

        $this->transactionPersist->save(RailTransactionResult::fromAchResult($result));
        $this->logOperation('Batch created', $batchId, [
            'entry_count' => $result->entryCount,
            'total_debits' => $result->totalDebits->getAmount(),
            'total_credits' => $result->totalCredits->getAmount(),
            'sec_code' => $request->secCode->value,
        ]);

        return $result;
    }

    public function submitFile(AchFile $file): AchBatchResult
    {
        $this->ensureAvailable();

        $fileContents = $this->nachaFormatter->generateFile($file);
        $firstBatch = $file->batches[0] ?? null;
        $batchId = $firstBatch?->id ?? $this->generateReference('BATCH');

        $result = AchBatchResult::success($file, $batchId, $fileContents);
        $this->transactionPersist->save(RailTransactionResult::fromAchResult($result));

        $this->logOperation('File submitted', $file->id, [
            'batch_count' => $file->getBatchCount(),
            'entry_count' => $file->getEntryCount(),
        ]);

        return $result;
    }

    public function generateNachaFile(AchBatchRequest $request): string
    {
        $file = $this->buildFileFromRequest($request, null);
        return $this->nachaFormatter->generateFile($file);
    }

    public function parseNachaFile(string $content): AchFile
    {
        return $this->nachaFormatter->parseFile($content);
    }

    public function sendPrenote(AchPrenoteRequest $request): AchBatchResult
    {
        $accountNumber = trim($request->accountNumber);
        if ($accountNumber === '') {
            throw new \InvalidArgumentException('Missing accountNumber provided for ACH prenote.');
        }

        $receiverName = trim($request->receiverName);
        if ($receiverName === '') {
            throw new \InvalidArgumentException('Missing receiverName provided for ACH prenote.');
        }

        $entryRequest = AchEntryRequest::prenote(
            receivingDfi: $request->routingNumber,
            accountNumber: $accountNumber,
            accountType: $request->accountType,
            receiverName: $receiverName,
            isDebit: $request->isDebit,
        );

        $batchRequest = new AchBatchRequest(
            companyName: $request->companyName ?? 'Prenote',
            companyId: $request->companyId ?? $this->configuration->getAchCompanyId(),
            companyEntryDescription: 'PRENOTE',
            originatingDfi: $request->routingNumber,
            secCode: SecCode::PPD,
            entries: [$entryRequest],
            effectiveEntryDate: $this->getNextEffectiveDate(false),
        );

        return $this->createBatch($batchRequest);
    }

    public function processReturn(AchReturn $return): bool
    {
        $transaction = null;

        if ($return->originalEntryId !== null) {
            $transaction = $this->transactionQuery->findById($return->originalEntryId);
        }

        if ($transaction === null) {
            $transaction = $this->transactionQuery->findByReference($return->originalTraceNumber);
        }

        if ($transaction === null) {
            $this->logOperation('Return received for unknown transaction', $return->originalTraceNumber, [
                'return_code' => $return->returnCode->value,
                'return_date' => $return->returnDate->format(DATE_ATOM),
                'retriable' => $return->isRetriable(),
            ]);
            return false;
        }

        $message = sprintf(
            'ACH return %s: %s',
            $return->returnCode->value,
            $return->getDescription()
        );

        $errors = [$message];
        if ($return->addendaInformation !== null && $return->addendaInformation !== '') {
            $errors[] = sprintf('Addenda: %s', $return->addendaInformation);
        }

        $errors[] = sprintf('Suggested action: %s', $return->getSuggestedAction());
        $errors[] = sprintf('Retriable: %s', $return->isRetriable() ? 'yes' : 'no');

        $this->transactionPersist->markFailed($transaction->transactionId, $errors);

        $this->logOperation('Return processed', $transaction->transactionId, [
            'return_code' => $return->returnCode->value,
            'return_date' => $return->returnDate->format(DATE_ATOM),
            'retriable' => $return->isRetriable(),
            'dishonored' => $return->dishonored,
            'contested' => $return->contested,
        ]);

        return true;
    }

    public function processNoc(AchNotificationOfChange $noc): bool
    {
        $transaction = null;

        if ($noc->originalEntryId !== null) {
            $transaction = $this->transactionQuery->findById($noc->originalEntryId);
        }

        if ($transaction === null) {
            $transaction = $this->transactionQuery->findByReference($noc->originalTraceNumber);
        }

        $corrections = $noc->parseCorrectionsData();

        if ($transaction !== null) {
            $this->transactionPersist->updateStatus($transaction->transactionId, 'noc_received');
        }

        $this->logOperation('NOC processed', $transaction?->transactionId ?? $noc->originalTraceNumber, [
            'noc_code' => $noc->nocCode->value,
            'noc_date' => $noc->nocDate->format(DATE_ATOM),
            'within_update_window' => $noc->isWithinUpdateWindow(),
            'fields_to_update' => $noc->getFieldsToUpdate(),
            'corrections' => $corrections,
            'corrected_data' => $noc->correctedData,
        ]);

        return $transaction !== null;
    }

    public function isSameDayAvailable(): bool
    {
        $cutoff = $this->getSameDayCutoffTime();
        return $cutoff !== null && (new \DateTimeImmutable()) <= $cutoff;
    }

    public function getCutoffTime(): \DateTimeImmutable
    {
        $cutoffTimes = $this->configuration->getCutoffTimes($this->getRailType());
        $close = $cutoffTimes['close'] ?? '23:59:59';

        if ($close instanceof \DateTimeImmutable) {
            return $close;
        }

        $today = new \DateTimeImmutable();
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $today->format('Y-m-d') . ' ' . (string) $close) ?: $today;
    }

    public function getNextEffectiveDate(bool $isSameDay = false): \DateTimeImmutable
    {
        if ($isSameDay && $this->isSameDayAvailable()) {
            return (new \DateTimeImmutable())->setTime(0, 0);
        }

        $date = new \DateTimeImmutable('+1 day');
        while ((int) $date->format('N') >= 6) {
            $date = $date->modify('+1 day');
        }

        return $date->setTime(0, 0);
    }

    public function validateRoutingNumber(string $routingNumber): bool
    {
        return RoutingNumber::tryFromString($routingNumber) !== null;
    }

    public function getTransactionStatus(string $transactionId): RailTransactionResult
    {
        return $this->transactionQuery->get($transactionId);
    }

    public function cancelTransaction(string $transactionId, string $reason): bool
    {
        $this->transactionPersist->markFailed($transactionId, [$reason]);
        $this->logOperation('Transaction cancelled', $transactionId, ['reason' => $reason]);
        return true;
    }

    private function buildEntries(array $entryRequests): array
    {
        $entries = [];
        $traceNumbers = [];

        foreach ($entryRequests as $index => $entryRequest) {
            if (!$entryRequest instanceof AchEntryRequest) {
                continue;
            }

            $entryId = $entryRequest->externalId ?? $this->generateReference('ENTRY');
            $trace = $this->generateTraceNumber($index);

            $entry = new AchEntry(
                id: $entryId,
                transactionCode: $entryRequest->getTransactionCode(),
                routingNumber: $entryRequest->receivingDfi,
                accountNumber: $entryRequest->accountNumber,
                accountType: $entryRequest->accountType,
                amount: $entryRequest->amount,
                individualName: $entryRequest->receiverName,
                individualId: $entryRequest->receiverId,
                discretionaryData: null,
                addenda: $entryRequest->addendaRecord,
                traceNumber: $trace,
            );

            $entries[] = $entry;
            $traceNumbers[$entryId] = $trace;
        }

        return [$entries, $traceNumbers];
    }

    private function buildFileFromRequest(AchBatchRequest $request, ?AchBatch $batch): AchFile
    {
        $effectiveDate = $request->effectiveEntryDate ?? $this->getNextEffectiveDate($request->isSameDay);
        [$entries] = $batch === null ? $this->buildEntries($request->entries) : [$batch->entries];
        $batchToUse = $batch ?? new AchBatch(
            id: $request->batchId ?? $this->generateReference('BATCH'),
            secCode: $request->secCode,
            companyName: $request->companyName,
            companyId: $request->companyId,
            companyEntryDescription: $request->companyEntryDescription,
            originatingDfi: $request->originatingDfi,
            effectiveEntryDate: $effectiveDate,
            entries: $entries,
            companyDiscretionaryData: $request->companyDiscretionaryData,
        );

        $fileId = $this->generateReference('FILE');
        $originatorInfo = $this->configuration->getOriginatorInfo();

        $file = AchFile::create(
            id: $fileId,
            immediateDestination: RoutingNumber::fromString($this->configuration->getImmediateDestination()),
            immediateOrigin: RoutingNumber::fromString($this->configuration->getImmediateOrigin()),
            immediateDestinationName: $originatorInfo['immediate_destination_name'] ?? 'DESTINATION',
            immediateOriginName: $originatorInfo['immediate_origin_name'] ?? 'ORIGIN',
            fileCreationDateTime: new \DateTimeImmutable(),
        );

        return $file->addBatch($batchToUse);
    }

    private function ensureAvailable(): void
    {
        if (!$this->isAvailable()) {
            throw RailUnavailableException::outsideOperatingHours($this->getRailType());
        }
    }

    private function getSameDayCutoffTime(): ?\DateTimeImmutable
    {
        $cutoffTimes = $this->configuration->getCutoffTimes($this->getRailType());

        if (!isset($cutoffTimes['same_day'])) {
            return null;
        }

        $sameDay = $cutoffTimes['same_day'];

        if ($sameDay instanceof \DateTimeImmutable) {
            return $sameDay;
        }

        $today = new \DateTimeImmutable();
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $today->format('Y-m-d') . ' ' . (string) $sameDay
        ) ?: null;
    }

    private function generateTraceNumber(int $sequence): string
    {
        $odfiId = substr($this->configuration->getImmediateOrigin(), 0, 8);
        $sequenceNumber = str_pad((string) ($sequence + 1), 7, '0', STR_PAD_LEFT);

        return $odfiId . $sequenceNumber;
    }

}
