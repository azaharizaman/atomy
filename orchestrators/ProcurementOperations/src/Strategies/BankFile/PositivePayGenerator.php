<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Strategies\BankFile;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;
use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Enums\PositivePayFormat;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\DTOs\BankFile\PositivePayResult;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\BankFile\BankFileResultInterface;
use Nexus\ProcurementOperations\ValueObjects\PositivePayConfiguration;

/**
 * Positive Pay File Generator.
 *
 * Generates check fraud prevention files for various bank formats.
 *
 * Positive Pay is a fraud prevention service where the company provides
 * the bank with a list of issued checks. The bank matches presented
 * checks against this list and flags mismatches.
 *
 * Supported formats:
 * - STANDARD_CSV: Universal CSV format
 * - BAI2: Bank Administration Institute format
 * - BANK_OF_AMERICA: Bank of America specific format
 * - WELLS_FARGO: Wells Fargo specific format
 * - CHASE: Chase specific format
 * - CITI: Citibank specific format
 */
final readonly class PositivePayGenerator extends AbstractBankFileGenerator
{
    protected const string VERSION = '1.0.0';

    private readonly PositivePayConfiguration $configuration;

    public function __construct(
        PositivePayConfiguration $configuration,
        LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($logger);
        $this->configuration = $configuration;
    }

    public function getFormat(): BankFileFormat
    {
        return BankFileFormat::POSITIVE_PAY;
    }

    public function getFileExtension(): string
    {
        return match ($this->configuration->format) {
            PositivePayFormat::BAI2 => 'bai2',
            default => 'csv',
        };
    }

    public function supports(PaymentBatchData $batch): bool
    {
        // Early return for empty payment items
        if (empty($batch->paymentItems)) {
            return false;
        }

        // Positive Pay requires check numbers
        foreach ($batch->paymentItems as $item) {
            if (empty($item->checkNumber)) {
                return false;
            }
        }

        return true;
    }

    public function validate(PaymentBatchData $batch): array
    {
        $errors = $this->validateCommonFields($batch);

        // Validate configuration
        if (!$this->configuration->isValid()) {
            $errors[] = 'Invalid Positive Pay configuration: check account number';
        }

        // Validate each payment item
        foreach ($batch->paymentItems as $index => $item) {
            $itemErrors = $this->validatePaymentItem($item, $index);
            $errors = array_merge($errors, $itemErrors);
        }

        // Check for duplicate check numbers
        $checkNumbers = array_map(fn($item) => $item->checkNumber, $batch->paymentItems);
        $duplicates = array_diff_assoc($checkNumbers, array_unique($checkNumbers));

        if (!empty($duplicates)) {
            $errors[] = 'Duplicate check numbers found: ' . implode(', ', array_unique($duplicates));
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

            return PositivePayResult::failure(
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
            $this->logger->error('Positive Pay generation failed with exception', [
                'batch_id' => $batch->batchId,
                'error' => $e->getMessage(),
            ]);

            return PositivePayResult::failure(
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

        if (empty($item->checkNumber)) {
            $errors[] = "{$prefix}: Check number is required for Positive Pay";
        }

        if (empty($item->vendorName)) {
            $errors[] = "{$prefix}: Payee name is required";
        }

        if ($item->amount->getAmount() <= 0) {
            $errors[] = "{$prefix}: Amount must be positive";
        }

        // Validate check number format (numeric, reasonable length)
        if (!empty($item->checkNumber) && (!ctype_digit($item->checkNumber) || strlen($item->checkNumber) > 12)) {
            $errors[] = "{$prefix}: Check number must be numeric and up to 12 digits";
        }

        return $errors;
    }

    /**
     * Build the complete file content based on format.
     */
    private function buildFileContent(PaymentBatchData $batch): string
    {
        return match ($this->configuration->format) {
            PositivePayFormat::STANDARD_CSV => $this->buildStandardCsv($batch),
            PositivePayFormat::BAI2 => $this->buildBai2Format($batch),
            PositivePayFormat::BANK_OF_AMERICA => $this->buildBankOfAmericaFormat($batch),
            PositivePayFormat::WELLS_FARGO => $this->buildWellsFargoFormat($batch),
            PositivePayFormat::CHASE => $this->buildChaseFormat($batch),
            PositivePayFormat::CITI => $this->buildCitiFormat($batch),
        };
    }

    /**
     * Build Standard CSV format.
     */
    private function buildStandardCsv(PaymentBatchData $batch): string
    {
        $lines = [];

        // Header row (always include for standard CSV)
        $lines[] = 'Account Number,Check Number,Issue Date,Amount,Payee Name,Status';

        // Data rows
        $issueDate = $batch->paymentDate ?? new \DateTimeImmutable();

        // Sanitize account number from configuration
        $sanitizedAccountNumber = $this->sanitizeForPositivePay($this->configuration->bankAccountNumber);

        foreach ($batch->paymentItems as $item) {
            // Sanitize check number to prevent CSV injection and format manipulation
            $sanitizedCheckNumber = $this->sanitizeForPositivePay($item->checkNumber ?? '');

            $lines[] = sprintf(
                '%s,%s,%s,%.2f,"%s",%s',
                $sanitizedAccountNumber,
                $sanitizedCheckNumber,
                $issueDate->format($this->configuration->format->dateFormat()),
                $item->amount->getAmount(),
                $this->escapeCsvValue($item->vendorName ?? ''),
                $this->determineStatus($item),
            );
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Build BAI2 format.
     */
    private function buildBai2Format(PaymentBatchData $batch): string
    {
        $lines = [];
        $now = new \DateTimeImmutable();
        $issueDate = $batch->paymentDate ?? $now;

        // Sanitize account and routing numbers from configuration
        $sanitizedAccountNumber = $this->sanitizeForPositivePay($this->configuration->bankAccountNumber);
        $sanitizedRoutingNumber = $this->sanitizeForPositivePay($this->configuration->bankRoutingNumber ?? '000000000');

        // 01 - File Header
        $lines[] = sprintf(
            '01,%s,%s,%s,%s,1,//',
            $sanitizedAccountNumber,
            $sanitizedRoutingNumber,
            $now->format('ymd'),
            $now->format('Hi'),
        );

        // 02 - Group Header
        $lines[] = sprintf(
            '02,%s,%s,1,%s,%s,,2/',
            $sanitizedAccountNumber,
            $sanitizedRoutingNumber,
            $issueDate->format('ymd'),
            $issueDate->format('Hi'),
        );

        // 03 - Account Identifier
        $totalAmount = $this->calculateTotalAmount($batch);
        $lines[] = sprintf(
            '03,%s,USD,010,%d,%d,/',
            $sanitizedAccountNumber,
            count($batch->paymentItems),
            $this->formatAmountAsCents($totalAmount),
        );

        // 16 - Transaction Detail (for each check)
        foreach ($batch->paymentItems as $item) {
            // Sanitize check number to prevent BAI2 format manipulation
            $sanitizedCheckNumber = $this->sanitizeForPositivePay($item->checkNumber ?? '');

            $lines[] = sprintf(
                '16,475,%d,0,%s,%s,/',
                $this->formatAmountAsCents($item->amount),
                $sanitizedCheckNumber,
                $this->sanitizeForBankFile($item->vendorName ?? '', false),
            );
        }

        // 49 - Account Trailer
        $lines[] = sprintf(
            '49,%d,%d/',
            $this->formatAmountAsCents($totalAmount),
            count($batch->paymentItems) + 2, // records in this group
        );

        // 98 - Group Trailer
        $lines[] = sprintf(
            '98,%d,%d,1/',
            $this->formatAmountAsCents($totalAmount),
            count($lines),
        );

        // 99 - File Trailer
        $lines[] = sprintf(
            '99,%d,%d,1/',
            $this->formatAmountAsCents($totalAmount),
            count($lines) + 1,
        );

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Build Bank of America specific format.
     */
    private function buildBankOfAmericaFormat(PaymentBatchData $batch): string
    {
        $lines = [];
        $issueDate = $batch->paymentDate ?? new \DateTimeImmutable();

        // Sanitize account number from configuration
        $sanitizedAccountNumber = $this->sanitizeForPositivePay($this->configuration->bankAccountNumber);

        // Bank of America uses fixed-width format
        foreach ($batch->paymentItems as $item) {
            // Sanitize check number to prevent fixed-width format manipulation
            $sanitizedCheckNumber = $this->sanitizeForPositivePay($item->checkNumber ?? '');

            $lines[] = sprintf(
                '%s%s%s%s%s',
                $this->padString($sanitizedAccountNumber, 16),                   // Account Number
                $this->padNumber($sanitizedCheckNumber, 10),                     // Check Number
                $issueDate->format('mdY'),                                       // Issue Date (MMDDYYYY)
                $this->padNumber($this->formatAmountAsCents($item->amount), 10), // Amount in cents
                $this->padString($this->sanitizeForBankFile($item->vendorName ?? ''), 40), // Payee Name
            );
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Build Wells Fargo specific format.
     */
    private function buildWellsFargoFormat(PaymentBatchData $batch): string
    {
        $lines = [];
        $issueDate = $batch->paymentDate ?? new \DateTimeImmutable();

        // Sanitize account number from configuration
        $sanitizedAccountNumber = $this->sanitizeForPositivePay($this->configuration->bankAccountNumber);

        // Wells Fargo header
        $lines[] = sprintf(
            'H%s%s%06d',
            $this->padString($sanitizedAccountNumber, 15),
            $issueDate->format('Ymd'),
            count($batch->paymentItems),
        );

        // Detail records
        foreach ($batch->paymentItems as $item) {
            // Sanitize check number to prevent fixed-width format manipulation
            $sanitizedCheckNumber = $this->sanitizeForPositivePay($item->checkNumber ?? '');

            $lines[] = sprintf(
                'D%s%s%s%s%s',
                $this->padString($sanitizedAccountNumber, 15),                   // Account Number
                $this->padNumber($sanitizedCheckNumber, 10),                     // Check Number
                $this->padNumber($this->formatAmountAsCents($item->amount), 12), // Amount
                $issueDate->format('Ymd'),                                       // Issue Date
                $this->padString($this->sanitizeForBankFile($item->vendorName ?? ''), 35), // Payee
            );
        }

        // Trailer
        $totalAmount = $this->calculateTotalAmount($batch);
        $lines[] = sprintf(
            'T%06d%012d',
            count($batch->paymentItems),
            $this->formatAmountAsCents($totalAmount),
        );

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Build Chase specific format.
     */
    private function buildChaseFormat(PaymentBatchData $batch): string
    {
        $lines = [];
        $issueDate = $batch->paymentDate ?? new \DateTimeImmutable();

        // Sanitize account number from configuration
        $sanitizedAccountNumber = $this->sanitizeForPositivePay($this->configuration->bankAccountNumber);

        // Chase uses pipe-delimited format
        foreach ($batch->paymentItems as $item) {
            // Sanitize check number to prevent delimiter injection
            $sanitizedCheckNumber = $this->sanitizeForPositivePay($item->checkNumber ?? '');

            $lines[] = implode('|', [
                $sanitizedAccountNumber,
                $sanitizedCheckNumber,
                number_format($item->amount->getAmount(), 2, '.', ''),
                $issueDate->format('m/d/Y'),
                $this->sanitizeForBankFile($item->vendorName ?? ''),
                $this->determineStatus($item),
            ]);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Build Citi specific format.
     */
    private function buildCitiFormat(PaymentBatchData $batch): string
    {
        $lines = [];
        $issueDate = $batch->paymentDate ?? new \DateTimeImmutable();

        // Sanitize account number from configuration
        $sanitizedAccountNumber = $this->sanitizeForPositivePay($this->configuration->bankAccountNumber);

        // Citi file header
        $lines[] = sprintf(
            '01%s%s%s',
            $this->padString($sanitizedAccountNumber, 20),
            (new \DateTimeImmutable())->format('Ymd'),
            $this->padNumber(count($batch->paymentItems), 6),
        );

        // Detail records
        foreach ($batch->paymentItems as $item) {
            // Sanitize check number to prevent fixed-width format manipulation
            $sanitizedCheckNumber = $this->sanitizeForPositivePay($item->checkNumber ?? '');

            $lines[] = sprintf(
                '02%s%s%s%s%s%s',
                $this->padString($sanitizedAccountNumber, 20),                   // Account
                $this->padNumber($sanitizedCheckNumber, 10),                     // Check Number
                $this->padNumber($this->formatAmountAsCents($item->amount), 12), // Amount
                $issueDate->format('Ymd'),                                       // Issue Date
                $this->padString($this->sanitizeForBankFile($item->vendorName ?? ''), 40), // Payee
                'I',                                                              // Issue code
            );
        }

        // File trailer
        $totalAmount = $this->calculateTotalAmount($batch);
        $lines[] = sprintf(
            '99%06d%015d',
            count($batch->paymentItems),
            $this->formatAmountAsCents($totalAmount),
        );

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Determine check status based on item metadata.
     */
    private function determineStatus(PaymentItemData $item): string
    {
        // Check metadata for void/stop payment flags
        // Use null coalescing on metadata first to handle null metadata array
        $metadata = $item->metadata ?? [];
        $isVoid = $metadata['void'] ?? false;
        $isStopPayment = $metadata['stop_payment'] ?? false;

        if ($isVoid) {
            return 'VOID';
        }

        if ($isStopPayment) {
            return 'STOP';
        }

        return 'ISSUED';
    }

    /**
     * Escape CSV value.
     *
     * Neutralizes formula prefixes to prevent CSV injection attacks.
     * Prefixes like =, +, -, @ can trigger formula execution in spreadsheet apps.
     */
    private function escapeCsvValue(string $value): string
    {
        // Remove any existing quotes and add proper escaping
        $value = str_replace('"', '""', $value);

        // Neutralize formula injection prefixes by prepending with single quote
        // This prevents spreadsheet applications from interpreting the value as a formula
        $formulaPrefixes = ['=', '+', '-', '@'];
        if ($value !== '' && in_array($value[0], $formulaPrefixes, true)) {
            $value = "'" . $value;
        }

        return $value;
    }

    /**
     * Sanitize value for Positive Pay file fields.
     *
     * Strips control characters and non-alphanumeric characters to prevent
     * injection attacks that could manipulate fixed-width records, CSV fields,
     * or delimited formats (BAI2, pipe-delimited).
     *
     * For check numbers and account numbers, this ensures only safe numeric
     * characters are included in the output.
     */
    private function sanitizeForPositivePay(string $value): string
    {
        // Strip control characters (0x00-0x1F and 0x7F)
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';

        // For Positive Pay fields (account/check numbers), allow only alphanumeric
        return preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '';
    }

    /**
     * Categorize items by status.
     *
     * @param array<PaymentItemData> $items
     * @return array{issued: array<string>, voided: array<string>, stop_payment: array<string>}
     */
    private function categorizeItems(array $items): array
    {
        $issued = [];
        $voided = [];
        $stopPayment = [];

        foreach ($items as $item) {
            $checkNumber = $item->checkNumber ?? '';
            $status = $this->determineStatus($item);

            match ($status) {
                'VOID' => $voided[] = $checkNumber,
                'STOP' => $stopPayment[] = $checkNumber,
                default => $issued[] = $checkNumber,
            };
        }

        return [
            'issued' => $issued,
            'voided' => $voided,
            'stop_payment' => $stopPayment,
        ];
    }

    /**
     * Create the result object.
     */
    private function createResult(PaymentBatchData $batch, string $fileContent): PositivePayResult
    {
        $issueDate = $batch->paymentDate ?? new \DateTimeImmutable();
        $totalAmount = $this->calculateTotalAmount($batch);
        $categories = $this->categorizeItems($batch->paymentItems);

        return PositivePayResult::success(
            batchId: $batch->batchId,
            fileName: $this->generateFileName('POSITIVEPAY', $batch->batchId),
            fileContent: $fileContent,
            totalAmount: $totalAmount,
            positivePayFormat: $this->configuration->format,
            bankAccountNumber: $this->configuration->bankAccountNumber,
            includedCheckNumbers: $categories['issued'],
            voidedCheckNumbers: $categories['voided'],
            stopPaymentCheckNumbers: $categories['stop_payment'],
            issueDate: $issueDate,
        );
    }
}
