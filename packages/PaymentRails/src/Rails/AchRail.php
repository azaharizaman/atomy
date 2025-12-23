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
use Nexus\PaymentRails\DTOs\AchEntryRequest;
use Nexus\PaymentRails\DTOs\RailTransactionResult;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\SecCode;
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
            maximumAmountCents: 999999999999,
            settlementDays: 2,
            isRealTime: false,
            supportsRefunds: true,
            supportsPartialRefunds: true,
            supportsRecurring: true,
            requiresBeneficiaryAddress: false,
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
            'total_debits' => $result->totalDebits->getAmountAsFloat(),
            'total_credits' => $result->totalCredits->getAmountAsFloat(),
            'sec_code' => $request->secCode->value,
        ]);

        return $result;
    }

    public function submitFile(AchFile $file): AchBatchResult
    {
        $this->ensureAvailable();

        $fileContents = $this->nachaFormatter->generateFile($file);
        $batchId = $file->batches[0]->id ?? $this->generateReference('BATCH');

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

    public function sendPrenote(array $accountData): AchBatchResult
    {
        $routing = RoutingNumber::fromString((string) ($accountData['routing_number'] ?? ''));
        $accountNumber = (string) ($accountData['account_number'] ?? '');
        $receiverName = (string) ($accountData['receiver_name'] ?? '');
        $isDebit = (bool) ($accountData['is_debit'] ?? false);
        $accountType = $this->normalizeAccountType($accountData['account_type'] ?? null);

        $entryRequest = AchEntryRequest::prenote(
            receivingDfi: $routing,
            accountNumber: $accountNumber,
            accountType: $accountType,
            receiverName: $receiverName,
            isDebit: $isDebit,
        );

        $batchRequest = new AchBatchRequest(
            companyName: $accountData['company_name'] ?? 'Prenote',
            companyId: $accountData['company_id'] ?? $this->configuration->getAchCompanyId(),
            companyEntryDescription: 'PRENOTE',
            originatingDfi: $routing,
            secCode: SecCode::PPD,
            entries: [$entryRequest],
            effectiveEntryDate: $this->getNextEffectiveDate(false),
        );

        return $this->createBatch($batchRequest);
    }

    public function processReturn(AchReturn $return): bool
    {
        $this->logOperation('Return processed', $return->returnCode->value, []);
        return true;
    }

    public function processNoc(AchNotificationOfChange $noc): bool
    {
        $this->logOperation('NOC processed', $noc->changeCode->value, []);
        return true;
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

    private function normalizeAccountType(mixed $value): AccountType
    {
        if ($value instanceof AccountType) {
            return $value;
        }

        if (is_string($value)) {
            try {
                return AccountType::from($value);
            } catch (\ValueError) {
                // Fall through to default
            }
        }

        return AccountType::CHECKING;
    }
}
