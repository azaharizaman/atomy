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
final class NachaFormatter implements NachaFormatterInterface
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

__halt_compiler();

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

        return $this->assertRecordLength($record);
    }

    public function generateFileControl(array $controlData): string
    {
        $record = self::RECORD_TYPE_FILE_CONTROL;
        $record .= $this->formatField($controlData['batch_count'] ?? 0, 6, 'numeric', 'right', '0');
        $record .= $this->formatField($controlData['block_count'] ?? 0, 6, 'numeric', 'right', '0');
        $record .= $this->formatField($controlData['entry_addenda_count'] ?? 0, 8, 'numeric', 'right', '0');
        $record .= $this->formatField($controlData['entry_hash'] ?? 0, 10, 'numeric', 'right', '0');
        $record .= $this->formatField($controlData['total_debit_cents'] ?? 0, 12, 'numeric', 'right', '0');
        $record .= $this->formatField($controlData['total_credit_cents'] ?? 0, 12, 'numeric', 'right', '0');
        $record .= str_repeat(' ', 39);

        return $this->assertRecordLength($record);
    }

    public function generateBatchHeader(array $batchData): string
    {
        $record = self::RECORD_TYPE_BATCH_HEADER;
        $record .= $this->formatField($batchData['service_class_code'] ?? 200, 3, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['company_name'] ?? '', 16, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['company_discretionary_data'] ?? '', 20, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['company_id'] ?? '', 10, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['sec_code'] ?? '', 3, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['company_entry_description'] ?? '', 10, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['company_descriptive_date'] ?? '', 6, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($batchData['effective_entry_date'] ?? '', 6, 'numeric', 'right', '0');
        $record .= str_repeat(' ', 3);
        $record .= self::ORIGINATOR_STATUS_CODE;
        $record .= $this->formatField($batchData['originating_dfi_identification'] ?? '', 8, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['batch_number'] ?? 1, 7, 'numeric', 'right', '0');

        return $this->assertRecordLength($record);
    }

    public function generateBatchControl(array $batchData): string
    {
        $record = self::RECORD_TYPE_BATCH_CONTROL;
        $record .= $this->formatField($batchData['service_class_code'] ?? 200, 3, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['entry_addenda_count'] ?? 0, 6, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['entry_hash'] ?? 0, 10, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['total_debit_cents'] ?? 0, 12, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['total_credit_cents'] ?? 0, 12, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['company_id'] ?? '', 10, 'alphanumeric', 'left', ' ');
        $record .= str_repeat(' ', 19);
        $record .= str_repeat(' ', 6);
        $record .= $this->formatField($batchData['originating_dfi_identification'] ?? '', 8, 'numeric', 'right', '0');
        $record .= $this->formatField($batchData['batch_number'] ?? 1, 7, 'numeric', 'right', '0');

        return $this->assertRecordLength($record);
    }

    public function generateEntryDetail(array $entryData): string
    {
        $record = self::RECORD_TYPE_ENTRY_DETAIL;
        $record .= $this->formatField($entryData['transaction_code'] ?? '', 2, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['receiving_dfi_identification'] ?? '', 8, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['check_digit'] ?? '', 1, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['dfi_account_number'] ?? '', 17, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($entryData['amount_cents'] ?? 0, 10, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['individual_id'] ?? '', 15, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($entryData['individual_name'] ?? '', 22, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($entryData['discretionary_data'] ?? '', 2, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($entryData['addenda_indicator'] ?? 0, 1, 'numeric', 'right', '0');
        $record .= $this->formatField($entryData['trace_number'] ?? '', 15, 'numeric', 'right', '0');

        return $this->assertRecordLength($record);
    }

    public function generateAddenda(array $addendaData): string
    {
        $record = self::RECORD_TYPE_ADDENDA;
        $record .= self::ADDENDA_TYPE_CODE;
        $record .= $this->formatField($addendaData['payment_related_information'] ?? '', 80, 'alphanumeric', 'left', ' ');
        $record .= $this->formatField($addendaData['addenda_sequence_number'] ?? 1, 4, 'numeric', 'right', '0');
        $record .= $this->formatField($addendaData['entry_detail_sequence_number'] ?? 1, 7, 'numeric', 'right', '0');

        return $this->assertRecordLength($record);
    }

    public function parseFile(string $content): AchFile
    {
        $errors = $this->validateFormat($content);
        if ($errors !== []) {
            throw new \InvalidArgumentException('Invalid NACHA file: ' . implode(' ', $errors));
        }

        $lines = $this->splitLines($content);
        $lines = array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));

        $headerLine = $lines[0] ?? '';
        $header = $this->parseFileHeader($headerLine);

        $creationDate = $header['file_creation_date'];
        $creationTime = $header['file_creation_time'];
        $fileCreationDateTime = \DateTimeImmutable::createFromFormat('ymdHi', $creationDate . $creationTime)
            ?: new \DateTimeImmutable();

        $batches = [];
        $currentBatchHeader = null;
        $currentBatchEntries = [];
        $currentBatchNumber = 1;

        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];

            if ($line === str_repeat('9', self::RECORD_LENGTH)) {
                continue;
            }

            $type = $line[0] ?? '';

            if ($type === self::RECORD_TYPE_BATCH_HEADER) {
                $currentBatchHeader = $this->parseBatchHeader($line);
                $currentBatchEntries = [];
                $currentBatchNumber = (int) ($currentBatchHeader['batch_number'] ?? $currentBatchNumber);
                continue;
            }

            if ($type === self::RECORD_TYPE_ENTRY_DETAIL) {
                $entry = $this->parseEntryDetail($line);
                $currentBatchEntries[] = $entry;
                continue;
            }

            if ($type === self::RECORD_TYPE_ADDENDA) {
                $addenda = $this->parseAddenda($line);
                $lastIndex = count($currentBatchEntries) - 1;
                if ($lastIndex >= 0) {
                    $existing = $currentBatchEntries[$lastIndex];
                    $currentBatchEntries[$lastIndex] = new AchEntry(
                        id: $existing->id,
                        transactionCode: $existing->transactionCode,
                        routingNumber: $existing->routingNumber,
                        accountNumber: $existing->accountNumber,
                        accountType: $existing->accountType,
                        amount: $existing->amount,
                        individualName: $existing->individualName,
                        individualId: $existing->individualId,
                        discretionaryData: $existing->discretionaryData,
                        addenda: $addenda['payment_related_information'],
                        traceNumber: $existing->traceNumber,
                    );
                }
                continue;
            }

            if ($type === self::RECORD_TYPE_BATCH_CONTROL) {
                $batchHeader = $currentBatchHeader ?? $this->parseBatchHeaderFallback($currentBatchNumber);
                $secCodeValue = trim((string) ($batchHeader['sec_code'] ?? 'PPD'));

                $batches[] = new AchBatch(
                    id: sprintf('BATCH-%d', $currentBatchNumber),
                    secCode: SecCode::from($secCodeValue),
                    companyName: rtrim((string) ($batchHeader['company_name'] ?? '')),
                    companyId: rtrim((string) ($batchHeader['company_id'] ?? '')),
                    companyEntryDescription: rtrim((string) ($batchHeader['company_entry_description'] ?? '')),
                    originatingDfi: new RoutingNumber($this->inferFullRoutingNumberFromFirst8Digits((string) ($batchHeader['originating_dfi_identification'] ?? '00000000'))),
                    effectiveEntryDate: \DateTimeImmutable::createFromFormat('ymd', (string) ($batchHeader['effective_entry_date'] ?? '000000'))
                        ?: new \DateTimeImmutable('today'),
                    entries: $currentBatchEntries,
                    companyDiscretionaryData: rtrim((string) ($batchHeader['company_discretionary_data'] ?? '')),
                    companyDescriptiveDate: $this->parseOptionalDate((string) ($batchHeader['company_descriptive_date'] ?? '')),
                    batchNumber: $currentBatchNumber,
                );

                $currentBatchHeader = null;
                $currentBatchEntries = [];
                continue;
            }

            if ($type === self::RECORD_TYPE_FILE_CONTROL) {
                break;
            }
        }

        $immediateDestination = new RoutingNumber(trim($header['immediate_destination']));
        $immediateOrigin = new RoutingNumber(trim($header['immediate_origin']));

        $id = sprintf(
            'NACHA-%s%s-%s',
            $creationDate,
            $creationTime,
            $header['file_id_modifier']
        );

        return new AchFile(
            id: $id,
            immediateDestination: $immediateDestination,
            immediateOrigin: $immediateOrigin,
            immediateDestinationName: rtrim($header['immediate_destination_name']),
            immediateOriginName: rtrim($header['immediate_origin_name']),
            fileCreationDateTime: $fileCreationDateTime,
            batches: $batches,
            fileIdModifier: $header['file_id_modifier'],
            status: FileStatus::GENERATED,
            referenceCode: rtrim($header['reference_code']) !== '' ? rtrim($header['reference_code']) : null,
        );
    }

    public function validateFormat(string $content): array
    {
        $errors = [];

        $lines = $this->splitLines($content);
        $lines = array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));

        if ($lines === []) {
            return ['Empty NACHA file content.'];
        }

        $first = $lines[0];
        if (($first[0] ?? '') !== self::RECORD_TYPE_FILE_HEADER) {
            $errors[] = 'First record must be a File Header record (type 1).';
        }

        $hasFileControl = false;

        foreach ($lines as $index => $line) {
            if (mb_strlen($line) !== self::RECORD_LENGTH) {
                $errors[] = sprintf('Record %d must be %d characters, got %d.', $index + 1, self::RECORD_LENGTH, mb_strlen($line));
                continue;
            }

            if ($line === str_repeat('9', self::RECORD_LENGTH)) {
                continue;
            }

            $type = $line[0] ?? '';
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

            if ($type === self::RECORD_TYPE_FILE_CONTROL) {
                $hasFileControl = true;
            }
        }

        if (!$hasFileControl) {
            $errors[] = 'Missing File Control record (type 9).';
        }

        return $errors;
    }

    public function calculateEntryHash(array $routingNumbers): string
    {
        $sum = 0;

        foreach ($routingNumbers as $routingNumber) {
            $digits = preg_replace('/[^0-9]/', '', (string) $routingNumber) ?? '';
            if (mb_strlen($digits) >= 8) {
                $sum += (int) mb_substr($digits, 0, 8);
            }
        }

        $hash = $sum % 10000000000;

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

        $stringValue = match ($type) {
            'numeric' => preg_replace('/[^0-9\-]/', '', $stringValue) ?? '',
            'alpha' => preg_replace('/[^A-Za-z ]/', '', $stringValue) ?? '',
            default => $stringValue,
        };

        $stringValue = mb_substr($stringValue, 0, $length);
        $padType = $align === 'right' ? STR_PAD_LEFT : STR_PAD_RIGHT;

        return str_pad($stringValue, $length, $padChar, $padType);
    }

    /** @param array<string> $lines */
    private function addBlockingRecords(array $lines): array
    {
        $blockRecord = str_repeat('9', self::RECORD_LENGTH);
        while (count($lines) % 10 !== 0) {
            $lines[] = $blockRecord;
        }

        return $lines;
    }

    private function assertRecordLength(string $record): string
    {
        if (mb_strlen($record) !== self::RECORD_LENGTH) {
            throw new \RuntimeException(sprintf(
                'Generated NACHA record must be %d characters, got %d.',
                self::RECORD_LENGTH,
                mb_strlen($record)
            ));
        }

        return $record;
    }

    private function buildTraceNumber(string $originatingDfiIdentification, int $sequence): string
    {
        $prefix = $this->formatField($originatingDfiIdentification, 8, 'numeric', 'right', '0');
        $suffix = $this->formatField($sequence, 7, 'numeric', 'right', '0');

        return $prefix . $suffix;
    }

    /** @return array<string, string> */
    private function parseFileHeader(string $line): array
    {
        return [
            'immediate_destination' => mb_substr($line, 3, 10),
            'immediate_origin' => mb_substr($line, 13, 10),
            'file_creation_date' => mb_substr($line, 23, 6),
            'file_creation_time' => mb_substr($line, 29, 4),
            'file_id_modifier' => mb_substr($line, 33, 1),
            'immediate_destination_name' => mb_substr($line, 40, 23),
            'immediate_origin_name' => mb_substr($line, 63, 23),
            'reference_code' => mb_substr($line, 86, 8),
        ];
    }

    /** @return array<string, string> */
    private function parseBatchHeader(string $line): array
    {
        return [
            'service_class_code' => mb_substr($line, 1, 3),
            'company_name' => mb_substr($line, 4, 16),
            'company_discretionary_data' => mb_substr($line, 20, 20),
            'company_id' => mb_substr($line, 40, 10),
            'sec_code' => mb_substr($line, 50, 3),
            'company_entry_description' => mb_substr($line, 53, 10),
            'company_descriptive_date' => mb_substr($line, 63, 6),
            'effective_entry_date' => mb_substr($line, 69, 6),
            'originating_dfi_identification' => mb_substr($line, 79, 8),
            'batch_number' => mb_substr($line, 87, 7),
        ];
    }

    private function parseBatchHeaderFallback(int $batchNumber): array
    {
        return [
            'sec_code' => 'PPD',
            'company_name' => '',
            'company_discretionary_data' => '',
            'company_id' => '',
            'company_entry_description' => '',
            'company_descriptive_date' => '',
            'effective_entry_date' => (new \DateTimeImmutable('today'))->format('ymd'),
            'originating_dfi_identification' => '00000000',
            'batch_number' => (string) $batchNumber,
        ];
    }

    private function parseEntryDetail(string $line): AchEntry
    {
        $transactionCode = TransactionCode::from(mb_substr($line, 1, 2));
        $receivingDfi8 = mb_substr($line, 3, 8);
        $checkDigit = mb_substr($line, 11, 1);
        $routingNumber = new RoutingNumber($receivingDfi8 . $checkDigit);

        $accountNumber = rtrim(mb_substr($line, 12, 17));
        $amountCents = (int) mb_substr($line, 29, 10);
        $individualId = rtrim(mb_substr($line, 39, 15));
        $individualName = rtrim(mb_substr($line, 54, 22));
        $discretionaryData = rtrim(mb_substr($line, 76, 2));
        $addendaIndicator = (int) mb_substr($line, 78, 1);
        $traceNumber = rtrim(mb_substr($line, 79, 15));

        return new AchEntry(
            id: $traceNumber !== '' ? $traceNumber : sprintf('ENTRY-%s', uniqid('', true)),
            transactionCode: $transactionCode,
            routingNumber: $routingNumber,
            accountNumber: $accountNumber,
            accountType: $this->inferAccountTypeFromTransactionCode($transactionCode),
            amount: new Money($amountCents, 'USD'),
            individualName: $individualName,
            individualId: $individualId,
            discretionaryData: $discretionaryData !== '' ? $discretionaryData : null,
            addenda: $addendaIndicator === 1 ? '' : null,
            traceNumber: $traceNumber !== '' ? $traceNumber : null,
        );
    }

    /** @return array{payment_related_information: string} */
    private function parseAddenda(string $line): array
    {
        return [
            'payment_related_information' => rtrim(mb_substr($line, 3, 80)),
        ];
    }

    private function inferAccountTypeFromTransactionCode(TransactionCode $transactionCode): AccountType
    {
        return $transactionCode->isChecking() ? AccountType::CHECKING : AccountType::SAVINGS;
    }

    private function inferFullRoutingNumberFromFirst8Digits(string $first8Digits): string
    {
        $digits = preg_replace('/[^0-9]/', '', $first8Digits) ?? '';
        $digits = str_pad(mb_substr($digits, 0, 8), 8, '0', STR_PAD_LEFT);

        $weights = [3, 7, 1, 3, 7, 1, 3, 7];
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += ((int) $digits[$i]) * $weights[$i];
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $digits . (string) $checkDigit;
    }

    private function parseOptionalDate(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('ymd', $value);
        return $dt ?: null;
    }

    /** @return array<string> */
    private function splitLines(string $content): array
    {
        $normalized = str_replace("\r\n", "\n", $content);
        $normalized = str_replace("\r", "\n", $normalized);
        $normalized = rtrim($normalized, "\n");

        return explode("\n", $normalized);
    }
}

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
                self::RECORD_TYPE_FILE_CONTROL,
            ], true)) {
                $errors[] = sprintf('Record %d has invalid record type: %s', $index + 1, $type);
            }

            if ($type === self::RECORD_TYPE_FILE_CONTROL && $line !== str_repeat('9', self::RECORD_LENGTH)) {
                $hasFileControl = true;
            }
        }

        if (!$hasFileControl) {
            $errors[] = 'Missing File Control record (type 9).';
        }

        return $errors;
    }

    public function calculateEntryHash(array $routingNumbers): string
    {
        $sum = 0;

        foreach ($routingNumbers as $routingNumber) {
            $digits = preg_replace('/[^0-9]/', '', (string) $routingNumber) ?? '';
            if (mb_strlen($digits) >= 8) {
                $sum += (int) mb_substr($digits, 0, 8);
            }
        }

        $hash = $sum % 10000000000;

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

        $stringValue = match ($type) {
            'numeric' => preg_replace('/[^0-9\-]/', '', $stringValue) ?? '',
            'alpha' => preg_replace('/[^A-Za-z ]/', '', $stringValue) ?? '',
            default => $stringValue,
        };

        $stringValue = mb_substr($stringValue, 0, $length);
        $padType = $align === 'right' ? STR_PAD_LEFT : STR_PAD_RIGHT;

        return str_pad($stringValue, $length, $padChar, $padType);
    }

    /** @param array<string> $lines */
    private function addBlockingRecords(array $lines): array
    {
        $blockRecord = str_repeat('9', self::RECORD_LENGTH);
        while (count($lines) % 10 !== 0) {
            $lines[] = $blockRecord;
        }
        return $lines;
    }

    private function assertRecordLength(string $record): string
    {
        if (mb_strlen($record) !== self::RECORD_LENGTH) {
            throw new \RuntimeException(sprintf(
                'Generated NACHA record must be %d characters, got %d.',
                self::RECORD_LENGTH,
                mb_strlen($record)
            ));
        }

        return $record;
    }

    private function buildTraceNumber(string $originatingDfiIdentification, int $sequence): string
    {
        $prefix = $this->formatField($originatingDfiIdentification, 8, 'numeric', 'right', '0');
        $suffix = $this->formatField($sequence, 7, 'numeric', 'right', '0');

        return $prefix . $suffix;
    }

    private function calculateRoutingCheckDigit(string $first8Digits): int
    {
        $digits = preg_replace('/[^0-9]/', '', $first8Digits) ?? '';
        $digits = str_pad(mb_substr($digits, 0, 8), 8, '0', STR_PAD_LEFT);

        $weights = [3, 7, 1, 3, 7, 1, 3, 7];
        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $sum += ((int) $digits[$i]) * $weights[$i];
        }

        return (10 - ($sum % 10)) % 10;
    }

    private function inferAccountTypeFromTransactionCode(TransactionCode $transactionCode): AccountType
    {
        return str_starts_with($transactionCode->value, '2')
            ? AccountType::CHECKING
            : AccountType::SAVINGS;
    }

    /** @return array<string, string> */
    private function parseFileHeader(string $line): array
    {
        return [
            'immediate_destination' => mb_substr($line, 3, 10),
            'immediate_origin' => mb_substr($line, 13, 10),
            'file_creation_date' => mb_substr($line, 23, 6),
            'file_creation_time' => mb_substr($line, 29, 4),
            'file_id_modifier' => mb_substr($line, 33, 1),
            'immediate_destination_name' => mb_substr($line, 40, 23),
            'immediate_origin_name' => mb_substr($line, 63, 23),
            'reference_code' => mb_substr($line, 86, 8),
        ];
    }

    /** @return array<string, string> */
    private function parseBatchHeader(string $line): array
    {
        return [
            'service_class_code' => mb_substr($line, 1, 3),
            'company_name' => mb_substr($line, 4, 16),
            'company_discretionary_data' => mb_substr($line, 20, 20),
            'company_id' => mb_substr($line, 40, 10),
            'sec_code' => mb_substr($line, 50, 3),
            'company_entry_description' => mb_substr($line, 53, 10),
            'company_descriptive_date' => mb_substr($line, 63, 6),
            'effective_entry_date' => mb_substr($line, 69, 6),
            'originating_dfi_identification' => mb_substr($line, 79, 8),
            'batch_number' => mb_substr($line, 87, 7),
        ];
    }

    /** @return array<string, string|int> */
    private function parseEntryDetail(string $line): array
    {
        $traceNumber = mb_substr($line, 79, 15);
        $entrySequence = (int) mb_substr($traceNumber, 8, 7);

        return [
            'transaction_code' => mb_substr($line, 1, 2),
            'receiving_dfi' => mb_substr($line, 3, 8),
            'check_digit' => mb_substr($line, 11, 1),
            'dfi_account_number' => mb_substr($line, 12, 17),
            'amount_cents' => (int) mb_substr($line, 29, 10),
            'individual_id' => mb_substr($line, 39, 15),
            'individual_name' => mb_substr($line, 54, 22),
            'discretionary_data' => mb_substr($line, 76, 2),
            'addenda_indicator' => (int) mb_substr($line, 78, 1),
            'trace_number' => $traceNumber,
            'entry_sequence_number' => $entrySequence,
        ];
    }

    /** @return array<string, string|int> */
    private function parseAddenda(string $line): array
    {
        return [
            'payment_related_information' => rtrim(mb_substr($line, 3, 80)),
            'addenda_sequence_number' => (int) mb_substr($line, 83, 4),
            'entry_detail_sequence_number' => (int) mb_substr($line, 87, 7),
        ];
    }
}
/*
 * Legacy duplicated formatter implementation was appended below and caused
 * a parse error due to a second `<?php` open tag. It is intentionally kept
 * commented out to preserve history while ensuring the autoloader loads a
 * single coherent implementation.
 */
<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Services;

use Nexus\PaymentRails\Contracts\NachaFormatterInterface;
use Nexus\PaymentRails\Enums\AchSecCode;
use Nexus\PaymentRails\ValueObjects\NachaBatch;
use Nexus\PaymentRails\ValueObjects\NachaEntry;
use Nexus\PaymentRails\ValueObjects\NachaFile;

/**
 * NACHA file formatter for ACH processing.
 *
 * Generates NACHA-compliant file content from ACH batch data.
 * Supports all record types: 1 (File Header), 5 (Batch Header),
 * 6 (Entry Detail), 7 (Addenda), 8 (Batch Control), 9 (File Control).
 */
final readonly class NachaFormatter implements NachaFormatterInterface
{
    /**
     * NACHA record length (94 characters).
     */
    private const RECORD_LENGTH = 94;

    /**
     * File Header record type.
     */
    private const RECORD_TYPE_FILE_HEADER = '1';

    /**
     * Batch Header record type.
     */
    private const RECORD_TYPE_BATCH_HEADER = '5';

    /**
     * Entry Detail record type.
     */
    private const RECORD_TYPE_ENTRY_DETAIL = '6';

    /**
     * Addenda record type.
     */
    private const RECORD_TYPE_ADDENDA = '7';

    /**
     * Batch Control record type.
     */
    private const RECORD_TYPE_BATCH_CONTROL = '8';

    /**
     * File Control record type.
     */
    private const RECORD_TYPE_FILE_CONTROL = '9';

    /**
     * NACHA format version (blocking factor).
     */
    private const BLOCKING_FACTOR = '10';

    /**
     * NACHA format code (blocking factor).
     */
    private const FORMAT_CODE = '1';

    /**
     * Priority code for file header.
     */
    private const PRIORITY_CODE = '01';

    /**
     * Generate complete NACHA file content.
     *
     * @param NachaFile $file
     * @return string
     */
    public function format(NachaFile $file): string
    {
        $lines = [];
        
        // File Header Record (Record Type 1)
        $lines[] = $this->formatFileHeader($file);

        // Process each batch
        foreach ($file->batches as $batchIndex => $batch) {
            $batchNumber = $batchIndex + 1;
            
            // Batch Header Record (Record Type 5)
            $lines[] = $this->formatBatchHeader($batch, $batchNumber, $file);
            
            // Entry Detail Records (Record Type 6) with optional Addenda (Record Type 7)
            $entrySequence = 0;
            foreach ($batch->entries as $entry) {
                $entrySequence++;
                $lines[] = $this->formatEntryDetail($entry, $file->originatingDfi, $entrySequence);
                
                // Add addenda records if present
                if ($entry->hasAddenda()) {
                    $addendaSequence = 0;
                    foreach ($entry->addendaRecords as $addenda) {
                        $addendaSequence++;
                        $lines[] = $this->formatAddenda($addenda, $entrySequence, $addendaSequence);
                    }
                }
            }
            
            // Batch Control Record (Record Type 8)
            $lines[] = $this->formatBatchControl($batch, $batchNumber, $file);
        }

        // File Control Record (Record Type 9)
        $lines[] = $this->formatFileControl($file, count($lines) + 1);

        // Add blocking records (pad to multiple of 10 records)
        $lines = $this->addBlockingRecords($lines);

        return implode("\n", $lines);
    }

    /**
     * Parse a NACHA file string into a NachaFile object.
     *
     * @param string $content
     * @return NachaFile
     */
    public function parse(string $content): NachaFile
    {
        $lines = explode("\n", trim($content));
        $batches = [];
        $currentBatch = null;
        $currentEntries = [];
        $originatingDfi = '';
        $immediateOrigin = '';
        $immediateDestination = '';
        $originCompanyName = '';
        $destinationName = '';
        $fileCreationDate = null;
        $fileId = null;

        foreach ($lines as $line) {
            $line = str_pad($line, self::RECORD_LENGTH);
            $recordType = $line[0];

            switch ($recordType) {
                case self::RECORD_TYPE_FILE_HEADER:
                    $fileData = $this->parseFileHeader($line);
                    $immediateDestination = $fileData['immediate_destination'];
                    $immediateOrigin = $fileData['immediate_origin'];
                    $originCompanyName = $fileData['origin_company_name'];
                    $destinationName = $fileData['destination_name'];
                    $fileCreationDate = $fileData['file_creation_date'];
                    $fileId = $fileData['file_id'];
                    $originatingDfi = trim($immediateOrigin);
                    break;

                case self::RECORD_TYPE_BATCH_HEADER:
                    if ($currentBatch !== null && !empty($currentEntries)) {
                        $batches[] = $this->createBatch($currentBatch, $currentEntries);
                    }
                    $currentBatch = $this->parseBatchHeader($line);
                    $currentEntries = [];
                    break;

                case self::RECORD_TYPE_ENTRY_DETAIL:
                    $currentEntries[] = $this->parseEntryDetail($line);
                    break;

                case self::RECORD_TYPE_ADDENDA:
                    if (!empty($currentEntries)) {
                        $lastEntry = array_pop($currentEntries);
                        $lastEntry->addendaRecords[] = $this->parseAddenda($line);
                        $currentEntries[] = $lastEntry;
                    }
                    break;

                case self::RECORD_TYPE_BATCH_CONTROL:
                    // Batch control processed, finalize batch
                    break;

                case self::RECORD_TYPE_FILE_CONTROL:
                    // File control - finalize any pending batch
                    if ($currentBatch !== null && !empty($currentEntries)) {
                        $batches[] = $this->createBatch($currentBatch, $currentEntries);
                    }
                    break;

                default:
                    // Blocking records (9999...) - ignore
                    break;
            }
        }

        return new NachaFile(
            fileId: $fileId ?? '',
            originatingDfi: $originatingDfi,
            immediateOrigin: $immediateOrigin,
            immediateDestination: $immediateDestination,
            originCompanyName: $originCompanyName,
            destinationName: $destinationName,
            batches: $batches,
            createdAt: $fileCreationDate ?? new \DateTimeImmutable(),
        );
    }

    /**
     * Validate NACHA file content.
     *
     * @param string $content
     * @return array<string> Validation errors
     */
    public function validate(string $content): array
    {
        $errors = [];
        $lines = explode("\n", trim($content));

        if (empty($lines)) {
            return ['File is empty'];
        }

        // Check record lengths
        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            // Allow blocking records to be shorter (filled with 9s)
            if (strlen($line) !== self::RECORD_LENGTH && !preg_match('/^9+$/', $line)) {
                $errors[] = "Line {$lineNumber}: Invalid record length (expected " . self::RECORD_LENGTH . ", got " . strlen($line) . ")";
            }
        }

        // Check file header exists
        if ($lines[0][0] !== self::RECORD_TYPE_FILE_HEADER) {
            $errors[] = 'File must begin with File Header record (1)';
        }

        // Check file control exists (excluding blocking records)
        $lastNonBlockingLine = null;
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (!preg_match('/^9+$/', $lines[$i])) {
                $lastNonBlockingLine = $lines[$i];
                break;
            }
        }

        if ($lastNonBlockingLine === null || $lastNonBlockingLine[0] !== self::RECORD_TYPE_FILE_CONTROL) {
            $errors[] = 'File must end with File Control record (9)';
        }

        // Validate batch structure
        $inBatch = false;
        $batchCount = 0;
        
        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            $recordType = $line[0];

            if ($recordType === self::RECORD_TYPE_BATCH_HEADER) {
                if ($inBatch) {
                    $errors[] = "Line {$lineNumber}: Batch Header without Batch Control";
                }
                $inBatch = true;
                $batchCount++;
            } elseif ($recordType === self::RECORD_TYPE_BATCH_CONTROL) {
                if (!$inBatch) {
                    $errors[] = "Line {$lineNumber}: Batch Control without Batch Header";
                }
                $inBatch = false;
            } elseif ($recordType === self::RECORD_TYPE_ENTRY_DETAIL) {
                if (!$inBatch) {
                    $errors[] = "Line {$lineNumber}: Entry Detail outside of batch";
                }
            }
        }

        return $errors;
    }

    /**
     * Format File Header Record (Type 1).
     */
    private function formatFileHeader(NachaFile $file): string
    {
        $date = $file->createdAt->format('ymd');
        $time = $file->createdAt->format('Hi');

        return sprintf(
            '%s%s%s%s%s%s%s%s%s%s%-23s%-23s%-8s',
            self::RECORD_TYPE_FILE_HEADER,
            self::PRIORITY_CODE,
            str_pad($file->immediateDestination, 10, ' ', STR_PAD_LEFT),
            str_pad($file->immediateOrigin, 10, ' ', STR_PAD_LEFT),
            $date,
            $time,
            strtoupper(substr($file->fileId, 0, 1)) ?: 'A',
            '094', // Record size
            self::BLOCKING_FACTOR,
            self::FORMAT_CODE,
            substr($file->destinationName, 0, 23),
            substr($file->originCompanyName, 0, 23),
            '' // Reference code (optional)
        );
    }

    /**
     * Format Batch Header Record (Type 5).
     */
    private function formatBatchHeader(NachaBatch $batch, int $batchNumber, NachaFile $file): string
    {
        return sprintf(
            '%s%s%-16s%s%s%-40s%-40s%s%-3s%s%s%s%s%08d',
            self::RECORD_TYPE_BATCH_HEADER,
            $batch->serviceClassCode->value,
            substr($batch->companyName, 0, 16),
            str_pad($batch->companyDiscretionaryData ?? '', 20),
            str_pad($batch->companyIdentification, 10),
            $batch->secCode->value,
            substr($batch->companyEntryDescription, 0, 10),
            str_pad($batch->companyDescriptiveDate ?? '', 6),
            $batch->effectiveEntryDate->format('ymd'),
            '   ', // Settlement date (reserved)
            '1', // Originator status code
            str_pad($file->originatingDfi, 8),
            $batchNumber
        );
    }

    /**
     * Format Entry Detail Record (Type 6).
     */
    private function formatEntryDetail(NachaEntry $entry, string $originatingDfi, int $entrySequence): string
    {
        $traceNumber = $originatingDfi . str_pad((string) $entrySequence, 7, '0', STR_PAD_LEFT);

        return sprintf(
            '%s%s%s%09d%-17s%010d%s%-22s%s%s%-15s',
            self::RECORD_TYPE_ENTRY_DETAIL,
            $entry->transactionCode->value,
            str_pad($entry->receivingDfiId, 8),
            $entry->dfiAccountNumber,
            $entry->dfiAccountNumber,
            $entry->amountCents,
            str_pad($entry->individualId, 15),
            substr($entry->individualName, 0, 22),
            str_pad($entry->discretionaryData ?? '', 2),
            $entry->hasAddenda() ? '1' : '0',
            $traceNumber
        );
    }

    /**
     * Format Addenda Record (Type 7).
     */
    private function formatAddenda(string $addendaInfo, int $entrySequence, int $addendaSequence): string
    {
        return sprintf(
            '%s%s%-80s%04d%07d',
            self::RECORD_TYPE_ADDENDA,
            '05', // Addenda type code (05 = payment related)
            substr($addendaInfo, 0, 80),
            $addendaSequence,
            $entrySequence
        );
    }

    /**
     * Format Batch Control Record (Type 8).
     */
    private function formatBatchControl(NachaBatch $batch, int $batchNumber, NachaFile $file): string
    {
        $entryCount = count($batch->entries);
        $entryHash = $this->calculateEntryHash($batch->entries);
        $debitTotal = $this->calculateTotalDebits($batch->entries);
        $creditTotal = $this->calculateTotalCredits($batch->entries);

        return sprintf(
            '%s%s%06d%010d%012d%012d%s%-19s%s%s%08d',
            self::RECORD_TYPE_BATCH_CONTROL,
            $batch->serviceClassCode->value,
            $entryCount,
            $entryHash,
            $debitTotal,
            $creditTotal,
            str_pad($batch->companyIdentification, 10),
            '', // Message authentication code (optional)
            '      ', // Reserved
            str_pad($file->originatingDfi, 8),
            $batchNumber
        );
    }

    /**
     * Format File Control Record (Type 9).
     */
    private function formatFileControl(NachaFile $file, int $recordCount): string
    {
        $batchCount = count($file->batches);
        $entryCount = 0;
        $entryHash = 0;
        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($file->batches as $batch) {
            $entryCount += count($batch->entries);
            $entryHash += $this->calculateEntryHash($batch->entries);
            $debitTotal += $this->calculateTotalDebits($batch->entries);
            $creditTotal += $this->calculateTotalCredits($batch->entries);
        }

        // Entry hash is last 10 digits only
        $entryHash = $entryHash % 10000000000;

        return sprintf(
            '%s%06d%06d%08d%010d%012d%012d%-39s',
            self::RECORD_TYPE_FILE_CONTROL,
            $batchCount,
            (int) ceil($recordCount / 10) * 10 / 10, // Block count
            $entryCount,
            $entryHash,
            $debitTotal,
            $creditTotal,
            '' // Reserved
        );
    }

    /**
     * Calculate entry hash for batch.
     *
     * @param array<NachaEntry> $entries
     */
    private function calculateEntryHash(array $entries): int
    {
        $hash = 0;
        foreach ($entries as $entry) {
            // Use first 8 digits of receiving DFI
            $hash += (int) substr($entry->receivingDfiId, 0, 8);
        }
        return $hash % 10000000000; // Last 10 digits
    }

    /**
     * Calculate total debits.
     *
     * @param array<NachaEntry> $entries
     */
    private function calculateTotalDebits(array $entries): int
    {
        $total = 0;
        foreach ($entries as $entry) {
            if ($entry->transactionCode->isDebit()) {
                $total += $entry->amountCents;
            }
        }
        return $total;
    }

    /**
     * Calculate total credits.
     *
     * @param array<NachaEntry> $entries
     */
    private function calculateTotalCredits(array $entries): int
    {
        $total = 0;
        foreach ($entries as $entry) {
            if ($entry->transactionCode->isCredit()) {
                $total += $entry->amountCents;
            }
        }
        return $total;
    }

    /**
     * Add blocking records to make file multiple of 10 records.
     *
     * @param array<string> $lines
     * @return array<string>
     */
    private function addBlockingRecords(array $lines): array
    {
        $recordCount = count($lines);
        $blockSize = 10;
        $remainder = $recordCount % $blockSize;

        if ($remainder !== 0) {
            $paddingNeeded = $blockSize - $remainder;
            $blockingRecord = str_repeat('9', self::RECORD_LENGTH);
            
            for ($i = 0; $i < $paddingNeeded; $i++) {
                $lines[] = $blockingRecord;
            }
        }

        return $lines;
    }

    /**
     * Parse File Header record.
     *
     * @return array<string, mixed>
     */
    private function parseFileHeader(string $line): array
    {
        return [
            'priority_code' => substr($line, 1, 2),
            'immediate_destination' => trim(substr($line, 3, 10)),
            'immediate_origin' => trim(substr($line, 13, 10)),
            'file_creation_date' => \DateTimeImmutable::createFromFormat('ymd', substr($line, 23, 6)),
            'file_creation_time' => substr($line, 29, 4),
            'file_id' => trim(substr($line, 33, 1)),
            'destination_name' => trim(substr($line, 40, 23)),
            'origin_company_name' => trim(substr($line, 63, 23)),
        ];
    }

    /**
     * Parse Batch Header record.
     *
     * @return array<string, mixed>
     */
    private function parseBatchHeader(string $line): array
    {
        return [
            'service_class_code' => substr($line, 1, 3),
            'company_name' => trim(substr($line, 4, 16)),
            'company_discretionary_data' => trim(substr($line, 20, 20)),
            'company_identification' => trim(substr($line, 40, 10)),
            'sec_code' => substr($line, 50, 3),
            'company_entry_description' => trim(substr($line, 53, 10)),
            'company_descriptive_date' => trim(substr($line, 63, 6)),
            'effective_entry_date' => substr($line, 69, 6),
            'originating_dfi' => substr($line, 79, 8),
            'batch_number' => (int) substr($line, 87, 7),
        ];
    }

    /**
     * Parse Entry Detail record.
     *
     * @return NachaEntry
     */
    private function parseEntryDetail(string $line): NachaEntry
    {
        return new NachaEntry(
            transactionCode: \Nexus\PaymentRails\Enums\AchTransactionCode::from(substr($line, 1, 2)),
            receivingDfiId: trim(substr($line, 3, 8)),
            dfiAccountNumber: trim(substr($line, 12, 17)),
            amountCents: (int) substr($line, 29, 10),
            individualId: trim(substr($line, 39, 15)),
            individualName: trim(substr($line, 54, 22)),
            discretionaryData: trim(substr($line, 76, 2)) ?: null,
            addendaRecords: [],
        );
    }

    /**
     * Parse Addenda record.
     */
    private function parseAddenda(string $line): string
    {
        return trim(substr($line, 3, 80));
    }

    /**
     * Create a NachaBatch from parsed data.
     *
     * @param array<string, mixed> $batchData
     * @param array<NachaEntry> $entries
     */
    private function createBatch(array $batchData, array $entries): NachaBatch
    {
        return new NachaBatch(
            batchId: (string) $batchData['batch_number'],
            serviceClassCode: \Nexus\PaymentRails\Enums\ServiceClassCode::from($batchData['service_class_code']),
            companyName: $batchData['company_name'],
            companyIdentification: $batchData['company_identification'],
            secCode: AchSecCode::from($batchData['sec_code']),
            companyEntryDescription: $batchData['company_entry_description'],
            effectiveEntryDate: \DateTimeImmutable::createFromFormat('ymd', $batchData['effective_entry_date']) 
                ?: new \DateTimeImmutable(),
            entries: $entries,
            companyDescriptiveDate: $batchData['company_descriptive_date'] ?: null,
            companyDiscretionaryData: $batchData['company_discretionary_data'] ?: null,
        );
    }
}

*/
