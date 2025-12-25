<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\NachaFormatterInterface;
use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\Enums\FileStatus;
use Nexus\PaymentRails\Enums\SecCode;
use Nexus\PaymentRails\Enums\TransactionCode;
use Nexus\PaymentRails\ValueObjects\AchBatch;
use Nexus\PaymentRails\ValueObjects\AchEntry;
use Nexus\PaymentRails\ValueObjects\AchFile;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;

/**
 * NACHA (ACH) formatter.
 *
 * This file previously contained corrupted/duplicated legacy content. The
 * authoritative implementation is the class below. Any remaining content
 * after `__halt_compiler()` is ignored by PHP.
 */
final readonly class NachaFormatter implements NachaFormatterInterface
{
    private const int RECORD_LENGTH = 94;

    private const string RECORD_TYPE_FILE_HEADER = '1';
    private const string RECORD_TYPE_BATCH_HEADER = '5';
    private const string RECORD_TYPE_ENTRY_DETAIL = '6';
    private const string RECORD_TYPE_ADDENDA = '7';
    private const string RECORD_TYPE_BATCH_CONTROL = '8';
    private const string RECORD_TYPE_FILE_CONTROL = '9';

    private const string PRIORITY_CODE = '01';
    private const string RECORD_SIZE = '094';
    private const string BLOCKING_FACTOR = '10';
    private const string FORMAT_CODE = '1';
    private const string ORIGINATOR_STATUS_CODE = '1';
    private const string ADDENDA_TYPE_CODE = '05';

    public function generateFile(AchFile $file): string
    {
        $lines = [];

        $lines[] = $this->generateFileHeader([
            'immediate_destination' => $file->getFormattedImmediateDestination(),
            'immediate_origin' => $file->getFormattedImmediateOrigin(),
            'file_creation_date' => $file->getFileCreationDate(),
            'file_creation_time' => $file->getFileCreationTime(),
            'file_id_modifier' => $file->fileIdModifier,
            'immediate_destination_name' => $file->getFormattedDestinationName(),
            'immediate_origin_name' => $file->getFormattedOriginName(),
            'reference_code' => $file->referenceCode,
        ]);

        foreach ($file->batches as $batchIndex => $batch) {
            $batchNumber = $batch->batchNumber > 0 ? $batch->batchNumber : ($batchIndex + 1);

            $lines[] = $this->generateBatchHeader([
                'service_class_code' => $batch->getServiceClassCode(),
                'company_name' => $batch->getFormattedCompanyName(),
                'company_discretionary_data' => $batch->companyDiscretionaryData,
                'company_id' => $batch->getFormattedCompanyId(),
                'sec_code' => $batch->secCode->value,
                'company_entry_description' => $batch->getFormattedEntryDescription(),
                'company_descriptive_date' => $batch->companyDescriptiveDate?->format('ymd'),
                'effective_entry_date' => $batch->effectiveEntryDate->format('ymd'),
                'originating_dfi_identification' => mb_substr($batch->originatingDfi->value, 0, 8),
                'batch_number' => $batchNumber,
            ]);

            $entrySequence = 0;
            foreach ($batch->entries as $entry) {
                $entrySequence++;

                $traceNumber = $entry->traceNumber;
                if ($traceNumber === null || $traceNumber === '') {
                    $traceNumber = $this->buildTraceNumber(
                        originatingDfiIdentification: mb_substr($batch->originatingDfi->value, 0, 8),
                        sequence: $entrySequence
                    );
                }

                $lines[] = $this->generateEntryDetail([
                    'transaction_code' => $entry->transactionCode->value,
                    'receiving_dfi_identification' => mb_substr($entry->routingNumber->value, 0, 8),
                    'check_digit' => (string) $entry->routingNumber->getCheckDigit(),
                    'dfi_account_number' => $entry->getFormattedAccountNumber(),
                    'amount_cents' => $entry->getAmountInCents(),
                    'individual_id' => $entry->getFormattedIndividualId(),
                    'individual_name' => $entry->getFormattedIndividualName(),
                    'discretionary_data' => $entry->discretionaryData,
                    'addenda_indicator' => $entry->getAddendaIndicator(),
                    'trace_number' => $traceNumber,
                ]);

                if ($entry->hasAddenda()) {
                    $lines[] = $this->generateAddenda([
                        'payment_related_information' => $entry->addenda,
                        'addenda_sequence_number' => 1,
                        'entry_detail_sequence_number' => $entrySequence,
                    ]);
                }
            }

            $lines[] = $this->generateBatchControl([
                'service_class_code' => $batch->getServiceClassCode(),
                'entry_addenda_count' => $batch->getEntryCount() + $batch->getAddendaCount(),
                'entry_hash' => $batch->getEntryHash(),
                'total_debit_cents' => $batch->getTotalDebits()->getAmountInMinorUnits(),
                'total_credit_cents' => $batch->getTotalCredits()->getAmountInMinorUnits(),
                'company_id' => $batch->getFormattedCompanyId(),
                'originating_dfi_identification' => mb_substr($batch->originatingDfi->value, 0, 8),
                'batch_number' => $batchNumber,
            ]);
        }

        $lines[] = $this->generateFileControl([
            'batch_count' => $file->getBatchCount(),
            'block_count' => $file->getBlockCount(),
            'entry_addenda_count' => $file->getEntryCount() + $file->getAddendaCount(),
            'entry_hash' => $file->getEntryHash(),
            'total_debit_cents' => $file->getTotalDebits()->getAmountInMinorUnits(),
            'total_credit_cents' => $file->getTotalCredits()->getAmountInMinorUnits(),
        ]);

        $lines = $this->addBlockingRecords($lines);

        return implode("\n", $lines);
    }

    public function generateFileHeader(array $headerData): string
    {
        $record = self::RECORD_TYPE_FILE_HEADER;
        $record .= self::PRIORITY_CODE;
        $record .= $this->formatField($headerData['immediate_destination'] ?? '', 10, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($headerData['immediate_origin'] ?? '', 10, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($headerData['file_creation_date'] ?? '', 6, 'numeric', 'right', '0');
        $record .= $this->formatField($headerData['file_creation_time'] ?? '', 4, 'numeric', 'right', '0');
        $record .= $this->formatField($headerData['file_id_modifier'] ?? 'A', 1, 'alphanumeric', 'left', ' ');
        $record .= self::RECORD_SIZE;
        $record .= self::BLOCKING_FACTOR;
        $record .= self::FORMAT_CODE;
        $record .= $this->formatField($headerData['immediate_destination_name'] ?? '', 23, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($headerData['immediate_origin_name'] ?? '', 23, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($headerData['reference_code'] ?? '', 8, 'alphanumeric', 'left', ' ');

        return $this->ensureRecordLength($record);
    }

    public function generateFileControl(array $controlData): string
    {
        $record = self::RECORD_TYPE_FILE_CONTROL;
        $record .= $this->formatField((string) ($controlData['batch_count'] ?? 0), 6, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($controlData['block_count'] ?? 0), 6, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($controlData['entry_addenda_count'] ?? 0), 8, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($controlData['entry_hash'] ?? 0), 10, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($controlData['total_debit_cents'] ?? 0), 12, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($controlData['total_credit_cents'] ?? 0), 12, 'numeric', 'right', '0');
        $record .= str_repeat(' ', 39);

        return $this->ensureRecordLength($record);
    }

    public function generateBatchHeader(array $batchData): string
    {
        $record = self::RECORD_TYPE_BATCH_HEADER;
        $record .= $this->formatField((string) ($batchData['service_class_code'] ?? 200), 3, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['company_name'] ?? '', 16, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['company_discretionary_data'] ?? '', 20, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['company_id'] ?? '', 10, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['sec_code'] ?? '', 3, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['company_entry_description'] ?? '', 10, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['company_descriptive_date'] ?? '', 6, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['effective_entry_date'] ?? '', 6, 'numeric', 'right', '0');
        $record .= '   '; // settlement date (julian) - blank
        $record .= self::ORIGINATOR_STATUS_CODE;
        $record .= $this->formatField($batchData['originating_dfi_identification'] ?? '', 8, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($batchData['batch_number'] ?? 1), 7, 'numeric', 'right', '0');

        return $this->ensureRecordLength($record);
    }

    public function generateBatchControl(array $batchData): string
    {
        $record = self::RECORD_TYPE_BATCH_CONTROL;
        $record .= $this->formatField((string) ($batchData['service_class_code'] ?? 200), 3, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($batchData['entry_addenda_count'] ?? 0), 6, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($batchData['entry_hash'] ?? 0), 10, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($batchData['total_debit_cents'] ?? 0), 12, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($batchData['total_credit_cents'] ?? 0), 12, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['company_id'] ?? '', 10, 'alphanumeric', 'left', ' ');
        $record .= str_repeat(' ', 19); // message authentication code
        $record .= str_repeat(' ', 6); // reserved
        $record .= $this->formatField($batchData['originating_dfi_identification'] ?? '', 8, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($batchData['batch_number'] ?? 1), 7, 'numeric', 'right', '0');

        return $this->ensureRecordLength($record);
    }

    public function generateEntryDetail(array $entryData): string
    {
        $record = self::RECORD_TYPE_ENTRY_DETAIL;
        $record .= $this->formatField($entryData['transaction_code'] ?? '', 2, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['receiving_dfi_identification'] ?? '', 8, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['check_digit'] ?? '', 1, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['dfi_account_number'] ?? '', 17, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField((string) ($entryData['amount_cents'] ?? 0), 10, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['individual_id'] ?? '', 15, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($entryData['individual_name'] ?? '', 22, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($entryData['discretionary_data'] ?? '', 2, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField((string) ($entryData['addenda_indicator'] ?? 0), 1, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['trace_number'] ?? '', 15, 'numeric', 'right', '0');

        return $this->ensureRecordLength($record);
    }

    public function generateAddenda(array $addendaData): string
    {
        $record = self::RECORD_TYPE_ADDENDA;
        $record .= self::ADDENDA_TYPE_CODE;
        $record .= $this->formatField($addendaData['payment_related_information'] ?? '', 80, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField((string) ($addendaData['addenda_sequence_number'] ?? 1), 4, 'numeric', 'right', '0');
        $record .= $this->formatField((string) ($addendaData['entry_detail_sequence_number'] ?? 1), 7, 'numeric', 'right', '0');

        return $this->ensureRecordLength($record);
    }

    public function parseFile(string $content): AchFile
    {
        $lines = array_values(array_filter(explode("\n", $content), static fn (string $l) => $l !== ''));
        $lines = array_values(array_filter($lines, fn (string $l) => $l !== str_repeat('9', self::RECORD_LENGTH)));

        if ($lines === []) {
            throw new \InvalidArgumentException('NACHA content is empty.');
        }

        $header = $lines[0];
        if ($header[0] !== self::RECORD_TYPE_FILE_HEADER) {
            throw new \InvalidArgumentException('Missing file header record.');
        }

        $immediateDestination = trim(mb_substr($header, 3, 10));
        $immediateOrigin = trim(mb_substr($header, 13, 10));
        $date = mb_substr($header, 23, 6);
        $time = mb_substr($header, 29, 4);
        $modifier = mb_substr($header, 33, 1);
        $destName = rtrim(mb_substr($header, 40, 23));
        $origName = rtrim(mb_substr($header, 63, 23));

        $fileOriginRouting = new RoutingNumber($immediateOrigin);

        $fileCreationDateTime = \DateTimeImmutable::createFromFormat('ymdHi', $date . $time)
            ?: new \DateTimeImmutable();

        $batches = [];
        $currentBatch = null;
        $currentEntries = [];
        $pendingAddenda = null;

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            $type = $line[0];

            if ($type === self::RECORD_TYPE_BATCH_HEADER) {
                if ($currentBatch !== null) {
                    $batches[] = $this->finalizeParsedBatch($currentBatch, $currentEntries, $fileOriginRouting);
                }

                $currentBatch = $this->parseBatchHeader($line);
                $currentEntries = [];
                $pendingAddenda = null;
                continue;
            }

            if ($type === self::RECORD_TYPE_ENTRY_DETAIL) {
                $pendingAddenda = null;
                $currentEntries[] = $this->parseEntryDetail($line);
                $pendingAddenda = count($currentEntries) - 1;
                continue;
            }

            if ($type === self::RECORD_TYPE_ADDENDA) {
                if ($pendingAddenda !== null && isset($currentEntries[$pendingAddenda])) {
                    $addenda = rtrim(mb_substr($line, 3, 80));
                    $entry = $currentEntries[$pendingAddenda];
                    $currentEntries[$pendingAddenda] = new AchEntry(
                        id: $entry->id,
                        transactionCode: $entry->transactionCode,
                        routingNumber: $entry->routingNumber,
                        accountNumber: $entry->accountNumber,
                        accountType: $entry->accountType,
                        amount: $entry->amount,
                        individualName: $entry->individualName,
                        individualId: $entry->individualId,
                        discretionaryData: $entry->discretionaryData,
                        addenda: $addenda,
                        traceNumber: $entry->traceNumber,
                    );
                }
                continue;
            }

            if ($type === self::RECORD_TYPE_BATCH_CONTROL) {
                if ($currentBatch !== null) {
                    $batches[] = $this->finalizeParsedBatch($currentBatch, $currentEntries, $fileOriginRouting);
                    $currentBatch = null;
                    $currentEntries = [];
                    $pendingAddenda = null;
                }
                continue;
            }

            if ($type === self::RECORD_TYPE_FILE_CONTROL) {
                break;
            }
        }

        if ($currentBatch !== null) {
            $batches[] = $this->finalizeParsedBatch($currentBatch, $currentEntries, $fileOriginRouting);
        }

        return new AchFile(
            id: 'parsed-file',
            immediateDestination: new RoutingNumber($immediateDestination),
            immediateOrigin: $fileOriginRouting,
            immediateDestinationName: $destName,
            immediateOriginName: $origName,
            fileCreationDateTime: $fileCreationDateTime,
            batches: $batches,
            fileIdModifier: $modifier !== '' ? $modifier : 'A',
            status: FileStatus::GENERATED,
            referenceCode: null,
        );
    }

    public function validateFormat(string $content): array
    {
        $errors = [];
        $lines = array_values(array_filter(explode("\n", $content), static fn (string $l) => $l !== ''));

        if ($lines === []) {
            return ['NACHA content is empty.'];
        }

        $hasFileControl = false;

        foreach ($lines as $index => $line) {
            if (strlen($line) !== self::RECORD_LENGTH) {
                $errors[] = sprintf('Record %d is not %d characters.', $index + 1, self::RECORD_LENGTH);
                continue;
            }

            $type = $line[0];
            if (!in_array($type, [
                self::RECORD_TYPE_FILE_HEADER,
                self::RECORD_TYPE_BATCH_HEADER,
                self::RECORD_TYPE_ENTRY_DETAIL,
                self::RECORD_TYPE_ADDENDA,
                self::RECORD_TYPE_BATCH_CONTROL,
                self::RECORD_TYPE_FILE_CONTROL,
            ], true)) {
                $errors[] = sprintf('Record %d has invalid record type: %s', $index + 1, $type);
            }

            if ($type === self::RECORD_TYPE_FILE_CONTROL && $line !== str_repeat('9', self::RECORD_LENGTH)) {
                $hasFileControl = true;
            }
        }

        if (!$hasFileControl) {
            $errors[] = 'Missing file control record.';
        }

        return $errors;
    }

    public function calculateEntryHash(array $routingNumbers): string
    {
        $hash = 0;
        foreach ($routingNumbers as $routingNumber) {
            $hash += (int) mb_substr($routingNumber, 0, 8);
        }
        $hash = $hash % 10000000000;
        return str_pad((string) $hash, 10, '0', STR_PAD_LEFT);
    }

    public function formatField(
        mixed $value,
        int $length,
        string $type = 'alphanumeric',
        string $align = 'left',
        string $padChar = ' ',
    ): string {
        $stringValue = (string) ($value ?? '');

        if ($type === 'numeric') {
            $stringValue = preg_replace('/\D+/', '', $stringValue) ?? '';
        }

        $stringValue = mb_substr($stringValue, 0, $length);

        return $align === 'right'
            ? str_pad($stringValue, $length, $padChar, STR_PAD_LEFT)
            : str_pad($stringValue, $length, $padChar, STR_PAD_RIGHT);
    }

    /** @param array<string> $lines */
    private function addBlockingRecords(array $lines): array
    {
        $remainder = count($lines) % 10;
        if ($remainder === 0) {
            return $lines;
        }

        $needed = 10 - $remainder;
        for ($i = 0; $i < $needed; $i++) {
            $lines[] = str_repeat('9', self::RECORD_LENGTH);
        }

        return $lines;
    }

    private function ensureRecordLength(string $record): string
    {
        $record = mb_substr($record, 0, self::RECORD_LENGTH);
        return str_pad($record, self::RECORD_LENGTH, ' ', STR_PAD_RIGHT);
    }

    private function buildTraceNumber(string $originatingDfiIdentification, int $sequence): string
    {
        return str_pad($originatingDfiIdentification, 8, '0', STR_PAD_LEFT)
            . str_pad((string) $sequence, 7, '0', STR_PAD_LEFT);
    }

    /** @return array<string, mixed> */
    private function parseBatchHeader(string $line): array
    {
        return [
            'service_class_code' => (int) mb_substr($line, 1, 3),
            'company_name' => rtrim(mb_substr($line, 4, 16)),
            'company_discretionary_data' => rtrim(mb_substr($line, 20, 20)) ?: null,
            'company_id' => rtrim(mb_substr($line, 40, 10)),
            'sec_code' => rtrim(mb_substr($line, 50, 3)),
            'company_entry_description' => rtrim(mb_substr($line, 53, 10)),
            'company_descriptive_date' => rtrim(mb_substr($line, 63, 6)) ?: null,
            'effective_entry_date' => rtrim(mb_substr($line, 69, 6)),
            'originating_dfi_identification' => rtrim(mb_substr($line, 79, 8)),
            'batch_number' => (int) mb_substr($line, 87, 7),
        ];
    }

    private function parseEntryDetail(string $line): AchEntry
    {
        $transactionCode = TransactionCode::from(mb_substr($line, 1, 2));
        $receivingDfi = mb_substr($line, 3, 8);
        $checkDigit = mb_substr($line, 11, 1);
        $routing = new RoutingNumber($receivingDfi . $checkDigit);
        $accountNumber = rtrim(mb_substr($line, 12, 17));
        $amount = (int) mb_substr($line, 29, 10);
        $individualId = rtrim(mb_substr($line, 39, 15));
        $individualName = rtrim(mb_substr($line, 54, 22));
        $discretionary = rtrim(mb_substr($line, 76, 2)) ?: null;
        $traceNumber = rtrim(mb_substr($line, 79, 15));

        $accountType = $transactionCode->isSavings() ? AccountType::SAVINGS : AccountType::CHECKING;

        return new AchEntry(
            id: 'parsed-entry-' . $traceNumber,
            transactionCode: $transactionCode,
            routingNumber: $routing,
            accountNumber: $accountNumber,
            accountType: $accountType,
            amount: new Money($amount, 'USD'),
            individualName: $individualName,
            individualId: $individualId,
            discretionaryData: $discretionary,
            addenda: null,
            traceNumber: $traceNumber,
        );
    }

    /** @param array<string, mixed> $batch */
    private function finalizeParsedBatch(array $batch, array $entries, RoutingNumber $fileOriginRouting): AchBatch
    {
        $secCode = SecCode::from($batch['sec_code']);
        $effectiveEntryDate = \DateTimeImmutable::createFromFormat('ymd', (string) $batch['effective_entry_date'])
            ?: new \DateTimeImmutable();

        return new AchBatch(
            id: 'parsed-batch-' . (string) $batch['batch_number'],
            secCode: $secCode,
            companyName: (string) ($batch['company_name'] ?? ''),
            companyId: (string) ($batch['company_id'] ?? ''),
            companyEntryDescription: (string) ($batch['company_entry_description'] ?? ''),
            // Batch header only includes the first 8 digits (ODFI identification), not the check digit.
            // Use the file's immediate origin routing number as a safe default.
            originatingDfi: $fileOriginRouting,
            effectiveEntryDate: $effectiveEntryDate,
            entries: $entries,
            companyDiscretionaryData: $batch['company_discretionary_data'] ?? null,
            companyDescriptiveDate: null,
            batchNumber: (int) ($batch['batch_number'] ?? 1),
        );
    }
}
