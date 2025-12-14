<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Strategies\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\BankFile\BankFileResultInterface;
use Nexus\ProcurementOperations\DTOs\BankFile\NachaFileResult;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Enums\NachaSecCode;
use Nexus\ProcurementOperations\ValueObjects\NachaConfiguration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * NACHA ACH File Generator.
 *
 * Generates files compliant with NACHA Operating Rules 2025.
 *
 * File structure:
 * - Record Type 1: File Header Record
 * - Record Type 5: Batch Header Record
 * - Record Type 6: Entry Detail Record
 * - Record Type 7: Addenda Record (optional)
 * - Record Type 8: Batch Control Record
 * - Record Type 9: File Control Record
 *
 * All records are 94 characters in fixed-width format.
 * Files are padded to 10-record blocks using record type 9 (blocking factor).
 *
 * @see https://www.nacha.org/rules
 */
final class NachaFileGenerator extends AbstractBankFileGenerator
{
    protected const string VERSION = '2025.1';

    /** Standard NACHA record length */
    private const int RECORD_LENGTH = 94;

    /** Blocking factor (records per block) */
    private const int BLOCKING_FACTOR = 10;

    /** Transaction codes */
    private const int TRANSACTION_CODE_CREDIT_CHECKING = 22;
    private const int TRANSACTION_CODE_CREDIT_SAVINGS = 32;
    private const int TRANSACTION_CODE_DEBIT_CHECKING = 27;
    private const int TRANSACTION_CODE_DEBIT_SAVINGS = 37;
    private const int TRANSACTION_CODE_PRENOTE_CREDIT_CHECKING = 23;
    private const int TRANSACTION_CODE_PRENOTE_CREDIT_SAVINGS = 33;

    private readonly NachaConfiguration $configuration;

    public function __construct(
        NachaConfiguration $configuration,
        LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($logger);
        $this->configuration = $configuration;
    }

    public function getFormat(): BankFileFormat
    {
        return BankFileFormat::NACHA;
    }

    public function supports(PaymentBatchData $batch): bool
    {
        // NACHA only supports USD
        if ($batch->currency !== 'USD') {
            return false;
        }

        // All items must have valid routing/account numbers
        foreach ($batch->paymentItems as $item) {
            if (empty($item->vendorBankRoutingNumber) || empty($item->vendorBankAccountNumber)) {
                return false;
            }
        }

        return true;
    }

    public function validate(PaymentBatchData $batch): array
    {
        $errors = $this->validateCommonFields($batch);

        // Validate currency
        if ($batch->currency !== 'USD') {
            $errors[] = 'NACHA files only support USD currency';
        }

        // Validate configuration
        if (!$this->configuration->isValid()) {
            $errors[] = 'Invalid NACHA configuration: check routing numbers';
        }

        // Validate each payment item
        foreach ($batch->paymentItems as $index => $item) {
            $itemErrors = $this->validatePaymentItem($item, $index);
            $errors = array_merge($errors, $itemErrors);
        }

        return $errors;
    }

    public function generate(PaymentBatchData $batch): BankFileResultInterface
    {
        $this->logGenerationStart($batch);

        // Validate first
        $errors = $this->validate($batch);
        if (!empty($errors)) {
            $this->logGenerationFailure($batch->batchId, $errors);

            return NachaFileResult::failure(
                batchId: $batch->batchId,
                errorMessage: 'Validation failed: ' . implode('; ', $errors),
                validationErrors: $errors,
            );
        }

        try {
            $fileContent = $this->buildFileContent($batch);

            $result = $this->createResult($batch, $fileContent);
            $this->logGenerationSuccess($result);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('NACHA generation failed with exception', [
                'batch_id' => $batch->batchId,
                'error' => $e->getMessage(),
            ]);

            return NachaFileResult::failure(
                batchId: $batch->batchId,
                errorMessage: 'Generation failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Validate individual payment item.
     *
     * @return array<string>
     */
    private function validatePaymentItem(PaymentItemData $item, int $index): array
    {
        $errors = [];
        $prefix = "Item {$index}";

        if (empty($item->vendorBankRoutingNumber)) {
            $errors[] = "{$prefix}: Routing number is required";
        } elseif (!$this->isValidRoutingNumber($item->vendorBankRoutingNumber)) {
            $errors[] = "{$prefix}: Invalid routing number format";
        }

        if (empty($item->vendorBankAccountNumber)) {
            $errors[] = "{$prefix}: Account number is required";
        } elseif (!$this->isValidAccountNumber($item->vendorBankAccountNumber)) {
            $errors[] = "{$prefix}: Invalid account number format";
        }

        if (empty($item->vendorName)) {
            $errors[] = "{$prefix}: Vendor/Recipient name is required";
        }

        // NACHA amount limits (up to $99,999,999.99 per entry)
        if ($item->amount->getAmount() > 99999999.99) {
            $errors[] = "{$prefix}: Amount exceeds NACHA limit";
        }

        return $errors;
    }

    /**
     * Build the complete NACHA file content.
     */
    private function buildFileContent(PaymentBatchData $batch): string
    {
        $lines = [];
        $now = new \DateTimeImmutable();
        $effectiveDate = $batch->paymentDate ?? $now->modify('+1 business day');

        // Record Type 1: File Header
        $lines[] = $this->buildFileHeaderRecord($now);

        // Build batches (grouping by SEC code if needed)
        $batchLines = $this->buildBatchRecords($batch, $effectiveDate);
        $lines = array_merge($lines, $batchLines['records']);

        // Record Type 9: File Control
        $lines[] = $this->buildFileControlRecord(
            batchCount: $batchLines['batch_count'],
            blockCount: $this->calculateBlockCount(count($lines) + 1),
            entryAddendaCount: $batchLines['entry_addenda_count'],
            entryHash: $batchLines['entry_hash'],
            totalDebit: $batchLines['total_debit'],
            totalCredit: $batchLines['total_credit'],
        );

        // Pad to blocking factor
        $lines = $this->padToBlockingFactor($lines);

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Build File Header Record (Record Type 1).
     */
    private function buildFileHeaderRecord(\DateTimeImmutable $now): string
    {
        return sprintf(
            '%s%s%s%s%s%s%s%s%s%s%s%s%s',
            '1',                                                    // Record Type Code
            '01',                                                   // Priority Code
            ' ' . $this->padNumber($this->configuration->immediateDestination, 9), // Immediate Destination (space + 9 digits)
            ' ' . $this->padNumber($this->configuration->immediateOrigin, 9),      // Immediate Origin (space + 9 digits)
            $now->format('ymd'),                                    // File Creation Date (YYMMDD)
            $now->format('Hi'),                                     // File Creation Time (HHMM)
            'A',                                                    // File ID Modifier
            '094',                                                  // Record Size
            '10',                                                   // Blocking Factor
            '1',                                                    // Format Code
            $this->padString($this->configuration->immediateDestinationName ?? 'DEST BANK', 23), // Immediate Destination Name
            $this->padString($this->configuration->immediateOriginName ?? 'ORIGIN CO', 23),      // Immediate Origin Name
            $this->padString($this->configuration->referenceCode ?? '', 8),             // Reference Code
        );
    }

    /**
     * Build all batch records.
     *
     * @return array{records: array<string>, batch_count: int, entry_addenda_count: int, entry_hash: int, total_debit: int, total_credit: int}
     */
    private function buildBatchRecords(PaymentBatchData $batch, \DateTimeImmutable $effectiveDate): array
    {
        $records = [];
        $batchNumber = 1;
        $entryAddendaCount = 0;
        $entryHash = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        // For simplicity, all items go into one batch
        // Record Type 5: Batch Header
        $records[] = $this->buildBatchHeaderRecord($batch, $batchNumber, $effectiveDate);

        // Record Type 6: Entry Detail Records
        $traceNumber = 0;
        foreach ($batch->paymentItems as $item) {
            $traceNumber++;
            $entryRecord = $this->buildEntryDetailRecord($item, $traceNumber);
            $records[] = $entryRecord;

            // Update counters
            $entryAddendaCount++;
            $entryHash += $this->extractRoutingHash($item->vendorBankRoutingNumber);

            $amountCents = $this->formatAmountAsCents($item->amount);
            // For vendor payments, typically credits
            $totalCredit += $amountCents;
        }

        // Keep only last 10 digits of entry hash
        $entryHash = $entryHash % 10000000000;

        // Record Type 8: Batch Control
        $records[] = $this->buildBatchControlRecord(
            $batch,
            $batchNumber,
            $entryAddendaCount,
            $entryHash,
            $totalDebit,
            $totalCredit,
        );

        return [
            'records' => $records,
            'batch_count' => 1,
            'entry_addenda_count' => $entryAddendaCount,
            'entry_hash' => $entryHash,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }

    /**
     * Build Batch Header Record (Record Type 5).
     */
    private function buildBatchHeaderRecord(
        PaymentBatchData $batch,
        int $batchNumber,
        \DateTimeImmutable $effectiveDate,
    ): string {
        $secCode = $this->configuration->secCode;

        return sprintf(
            '%s%s%s%s%s%s%s%s%s%s%s%s%s',
            '5',                                                              // Record Type Code
            '220',                                                            // Service Class Code (220 = credits only)
            $this->padString($this->configuration->companyName ?? 'COMPANY', 16), // Company Name
            $this->padString('', 20),                                         // Company Discretionary Data
            $this->padString($this->configuration->companyId ?? '', 10),      // Company Identification
            $secCode->value,                                                  // Standard Entry Class Code
            $this->padString($batch->description ?? 'VENDOR PMT', 10),        // Company Entry Description
            $effectiveDate->format('ymd'),                                    // Company Descriptive Date (YYMMDD)
            $effectiveDate->format('ymd'),                                    // Effective Entry Date (YYMMDD)
            '   ',                                                            // Settlement Date (Julian) - blank for originator
            '1',                                                              // Originator Status Code
            $this->padString(substr($this->configuration->immediateOrigin, 0, 8), 8), // Originating DFI Identification
            $this->padNumber($batchNumber, 7),                                // Batch Number
        );
    }

    /**
     * Build Entry Detail Record (Record Type 6).
     */
    private function buildEntryDetailRecord(PaymentItemData $item, int $traceNumber): string
    {
        $transactionCode = $this->determineTransactionCode($item);
        $amountCents = $this->formatAmountAsCents($item->amount);

        return sprintf(
            '%s%s%s%s%s%s%s%s%s',
            '6',                                                          // Record Type Code
            $this->padNumber($transactionCode, 2),                        // Transaction Code
            $this->padNumber($item->vendorBankRoutingNumber, 8),                // Receiving DFI Identification (first 8 digits)
            $this->calculateCheckDigit($item->vendorBankRoutingNumber),         // Check Digit (9th digit)
            $this->padString($item->vendorBankAccountNumber, 17),               // DFI Account Number
            $this->padNumber($amountCents, 10),                           // Amount
            $this->padString($item->vendorId ?? $item->paymentReference ?? '', 15), // Individual Identification Number
            $this->padString($this->sanitizeForBankFile($item->vendorName ?? ''), 22), // Individual Name
            $this->padString('', 2),                                      // Discretionary Data
            '0',                                                          // Addenda Record Indicator (0 = no addenda)
            $this->padNumber($this->configuration->immediateOrigin, 8),   // Trace Number (first 8 - ODFI routing)
            $this->padNumber($traceNumber, 7),                            // Trace Number (sequence)
        );
    }

    /**
     * Build Batch Control Record (Record Type 8).
     */
    private function buildBatchControlRecord(
        PaymentBatchData $batch,
        int $batchNumber,
        int $entryAddendaCount,
        int $entryHash,
        int $totalDebit,
        int $totalCredit,
    ): string {
        return sprintf(
            '%s%s%s%s%s%s%s%s%s%s',
            '8',                                                              // Record Type Code
            '220',                                                            // Service Class Code
            $this->padNumber($entryAddendaCount, 6),                          // Entry/Addenda Count
            $this->padNumber($entryHash, 10),                                 // Entry Hash
            $this->padNumber($totalDebit, 12),                                // Total Debit Entry Dollar Amount
            $this->padNumber($totalCredit, 12),                               // Total Credit Entry Dollar Amount
            $this->padString($this->configuration->companyId ?? '', 10),      // Company Identification
            $this->padString('', 19),                                         // Message Authentication Code (blank)
            $this->padString('', 6),                                          // Reserved
            $this->padString(substr($this->configuration->immediateOrigin, 0, 8), 8), // Originating DFI Identification
            $this->padNumber($batchNumber, 7),                                // Batch Number
        );
    }

    /**
     * Build File Control Record (Record Type 9).
     */
    private function buildFileControlRecord(
        int $batchCount,
        int $blockCount,
        int $entryAddendaCount,
        int $entryHash,
        int $totalDebit,
        int $totalCredit,
    ): string {
        return sprintf(
            '%s%s%s%s%s%s%s%s',
            '9',                                        // Record Type Code
            $this->padNumber($batchCount, 6),           // Batch Count
            $this->padNumber($blockCount, 6),           // Block Count
            $this->padNumber($entryAddendaCount, 8),    // Entry/Addenda Count
            $this->padNumber($entryHash, 10),           // Entry Hash
            $this->padNumber($totalDebit, 12),          // Total Debit Entry Dollar Amount
            $this->padNumber($totalCredit, 12),         // Total Credit Entry Dollar Amount
            $this->padString('', 39),                   // Reserved
        );
    }

    /**
     * Pad file to blocking factor with type 9 records.
     *
     * @param array<string> $lines
     * @return array<string>
     */
    private function padToBlockingFactor(array $lines): array
    {
        $recordCount = count($lines);
        $remainder = $recordCount % self::BLOCKING_FACTOR;

        if ($remainder === 0) {
            return $lines;
        }

        $paddingNeeded = self::BLOCKING_FACTOR - $remainder;

        // Pad with type 9 records (all 9s)
        $paddingRecord = str_repeat('9', self::RECORD_LENGTH);

        for ($i = 0; $i < $paddingNeeded; $i++) {
            $lines[] = $paddingRecord;
        }

        return $lines;
    }

    /**
     * Calculate block count.
     */
    private function calculateBlockCount(int $recordCount): int
    {
        return (int) ceil($recordCount / self::BLOCKING_FACTOR);
    }

    /**
     * Determine transaction code based on payment type.
     */
    private function determineTransactionCode(PaymentItemData $item): int
    {
        // Default to credit to checking account for vendor payments
        $accountType = $item->bankAccountType ?? 'checking';

        return match (strtolower($accountType)) {
            'savings' => self::TRANSACTION_CODE_CREDIT_SAVINGS,
            default => self::TRANSACTION_CODE_CREDIT_CHECKING,
        };
    }

    /**
     * Extract routing number hash (first 8 digits).
     */
    private function extractRoutingHash(string $routingNumber): int
    {
        return (int) substr($routingNumber, 0, 8);
    }

    /**
     * Calculate check digit (9th digit of routing number).
     */
    private function calculateCheckDigit(string $routingNumber): string
    {
        return substr($routingNumber, 8, 1);
    }

    /**
     * Create the result object.
     */
    private function createResult(PaymentBatchData $batch, string $fileContent): NachaFileResult
    {
        $now = new \DateTimeImmutable();
        $effectiveDate = $batch->paymentDate ?? $now->modify('+1 business day');

        // Calculate totals
        $totalAmount = $this->calculateTotalAmount($batch);
        $totalCreditCents = $this->formatAmountAsCents($totalAmount);

        // Count records (file header + batch header + entries + batch control + file control)
        $totalRecords = 2 + count($batch->paymentItems) + 2;

        return NachaFileResult::success(
            batchId: $batch->batchId,
            fileName: $this->generateFileName('NACHA', $batch->batchId),
            fileContent: $fileContent,
            totalRecords: $totalRecords,
            totalAmount: $totalAmount,
            immediateOrigin: $this->configuration->immediateOrigin,
            immediateDestination: $this->configuration->immediateDestination,
            secCode: $this->configuration->secCode,
            batchCount: 1,
            blockCount: $this->calculateBlockCount($totalRecords),
            entryAddendaCount: count($batch->paymentItems),
            entryHash: $this->calculateEntryHash($batch),
            totalDebitAmount: 0,
            totalCreditAmount: $totalCreditCents,
            fileCreationDate: $now,
            effectiveEntryDate: $effectiveDate,
        );
    }

    /**
     * Calculate entry hash for the batch.
     */
    private function calculateEntryHash(PaymentBatchData $batch): int
    {
        $hash = 0;

        foreach ($batch->paymentItems as $item) {
            $hash += $this->extractRoutingHash($item->vendorBankRoutingNumber);
        }

        // Return only last 10 digits
        return $hash % 10000000000;
    }
}
