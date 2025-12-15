<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Strategies\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\BankFile\BankFileResultInterface;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentItemData;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Abstract base class for bank file generators.
 *
 * Provides common functionality for:
 * - File naming conventions
 * - Checksum generation
 * - Validation helpers
 * - Amount formatting
 */
abstract class AbstractBankFileGenerator implements BankFileGeneratorInterface
{
    protected const string VERSION = '1.0.0';

    public function __construct(
        protected LoggerInterface $logger = new NullLogger(),
    ) {}

    abstract public function getFormat(): BankFileFormat;

    public function getVersion(): string
    {
        return static::VERSION;
    }

    public function getFileExtension(): string
    {
        return match ($this->getFormat()) {
            BankFileFormat::NACHA => 'ach',
            BankFileFormat::POSITIVE_PAY => 'csv',
            BankFileFormat::SWIFT_MT101 => 'fin',
            BankFileFormat::ISO20022 => 'xml',
            BankFileFormat::BAI2 => 'bai2',
        };
    }

    public function getMimeType(): string
    {
        return match ($this->getFormat()) {
            BankFileFormat::NACHA => 'application/nacha',
            BankFileFormat::POSITIVE_PAY => 'text/csv',
            BankFileFormat::SWIFT_MT101 => 'application/x-swift',
            BankFileFormat::ISO20022 => 'application/xml',
            BankFileFormat::BAI2 => 'text/plain',
        };
    }

    public function requiresConfiguration(): bool
    {
        return true;
    }

    /**
     * Generate a standardized file name.
     */
    protected function generateFileName(string $prefix, string $batchId, ?\DateTimeImmutable $date = null): string
    {
        $date ??= new \DateTimeImmutable();

        return sprintf(
            '%s_%s_%s.%s',
            $prefix,
            $date->format('Ymd_His'),
            substr($batchId, 0, 8),
            $this->getFileExtension(),
        );
    }

    /**
     * Calculate SHA-256 checksum of file content.
     */
    protected function calculateChecksum(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Format amount as integer cents (no decimal point).
     */
    protected function formatAmountAsCents(Money $amount): int
    {
        return (int) round($amount->getAmount() * 100);
    }

    /**
     * Format amount as string with padding.
     */
    protected function formatAmountPadded(Money $amount, int $length, bool $padLeft = true): string
    {
        $cents = $this->formatAmountAsCents($amount);
        $formatted = (string) $cents;

        return $padLeft
            ? str_pad($formatted, $length, '0', STR_PAD_LEFT)
            : str_pad($formatted, $length, '0', STR_PAD_RIGHT);
    }

    /**
     * Pad string to fixed width (right-padded with spaces by default).
     */
    protected function padString(string $value, int $length, string $pad = ' ', bool $padLeft = false): string
    {
        $value = substr($value, 0, $length);

        return $padLeft
            ? str_pad($value, $length, $pad, STR_PAD_LEFT)
            : str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * Pad number to fixed width (left-padded with zeros).
     */
    protected function padNumber(int|string $value, int $length): string
    {
        return str_pad((string) $value, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Sanitize string for bank file (uppercase, alphanumeric only).
     *
     * Security: Explicitly strips control characters (0x00-0x1F, 0x7F) to prevent
     * manipulation of fixed-width and delimited file formats before applying
     * alphanumeric filtering.
     */
    protected function sanitizeForBankFile(string $value, bool $allowSpaces = true): string
    {
        // First, strip all control characters (0x00-0x1F and 0x7F) to prevent injection attacks
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';

        // Convert to uppercase for bank file compatibility
        $value = strtoupper($value);

        // Apply alphanumeric filter based on space allowance
        if ($allowSpaces) {
            return preg_replace('/[^A-Z0-9 ]/', '', $value) ?? '';
        }

        return preg_replace('/[^A-Z0-9]/', '', $value) ?? '';
    }

    /**
     * Validate routing number using ABA checksum algorithm.
     */
    protected function isValidRoutingNumber(string $routingNumber): bool
    {
        if (strlen($routingNumber) !== 9 || !ctype_digit($routingNumber)) {
            return false;
        }

        $digits = str_split($routingNumber);

        // ABA checksum: 3*d1 + 7*d2 + d3 + 3*d4 + 7*d5 + d6 + 3*d7 + 7*d8 + d9 should be divisible by 10
        $checksum = (3 * (int) $digits[0])
            + (7 * (int) $digits[1])
            + (int) $digits[2]
            + (3 * (int) $digits[3])
            + (7 * (int) $digits[4])
            + (int) $digits[5]
            + (3 * (int) $digits[6])
            + (7 * (int) $digits[7])
            + (int) $digits[8];

        return $checksum % 10 === 0;
    }

    /**
     * Validate account number format.
     * 
     * Uses strict digits-only validation to prevent control character injection.
     */
    protected function isValidAccountNumber(string $accountNumber): bool
    {
        // Strict validation: must be 4-17 digits only, no other characters
        return preg_match('/^\d{4,17}$/', $accountNumber) === 1;
    }

    /**
     * Sanitize account number by removing non-digit characters.
     * 
     * Use this when you need to clean up user input before validation.
     */
    protected function sanitizeAccountNumber(string $accountNumber): string
    {
        return preg_replace('/[^0-9]/', '', $accountNumber) ?? '';
    }

    /**
     * Calculate total amount from payment items.
     */
    protected function calculateTotalAmount(PaymentBatchData $batch): Money
    {
        $total = Money::zero($batch->currency);

        foreach ($batch->paymentItems as $item) {
            $total = $total->add($item->amount);
        }

        return $total;
    }

    /**
     * Count valid payment items.
     */
    protected function countValidItems(PaymentBatchData $batch): int
    {
        return count(array_filter(
            $batch->paymentItems,
            fn(PaymentItemData $item) => $item->amount->getAmount() > 0,
        ));
    }

    /**
     * Add common validation errors.
     *
     * @return array<string>
     */
    protected function validateCommonFields(PaymentBatchData $batch): array
    {
        $errors = [];

        if (empty($batch->batchId)) {
            $errors[] = 'Batch ID is required';
        }

        if (empty($batch->paymentItems)) {
            $errors[] = 'At least one payment item is required';
        }

        foreach ($batch->paymentItems as $index => $item) {
            if ($item->amount->getAmount() <= 0) {
                $errors[] = "Item {$index}: Amount must be positive";
            }
        }

        return $errors;
    }

    /**
     * Log generation start.
     */
    protected function logGenerationStart(PaymentBatchData $batch): void
    {
        $this->logger->info('Starting bank file generation', [
            'format' => $this->getFormat()->value,
            'version' => $this->getVersion(),
            'batch_id' => $batch->batchId,
            'item_count' => count($batch->paymentItems),
        ]);
    }

    /**
     * Log generation success.
     */
    protected function logGenerationSuccess(BankFileResultInterface $result): void
    {
        $this->logger->info('Bank file generation completed', [
            'format' => $this->getFormat()->value,
            'batch_id' => $result->getBatchId(),
            'file_name' => $result->getFileName(),
            'file_size' => $result->getFileSize(),
            'total_records' => $result->getTotalRecords(),
            'checksum' => $result->getChecksum(),
        ]);
    }

    /**
     * Log generation failure.
     *
     * @param array<string> $errors
     */
    protected function logGenerationFailure(string $batchId, array $errors): void
    {
        $this->logger->error('Bank file generation failed', [
            'format' => $this->getFormat()->value,
            'batch_id' => $batchId,
            'errors' => $errors,
        ]);
    }
}
