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
