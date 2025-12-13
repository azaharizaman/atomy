<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Financial;

use Nexus\Common\ValueObjects\Money;

/**
 * Bank File Generation Result
 * 
 * Represents the result of generating a bank payment file.
 */
final readonly class BankFileGenerationResult
{
    /**
     * @param array<string> $includedPaymentIds
     * @param array<string, string> $failedPayments Map of paymentId => reason
     */
    public function __construct(
        public string $batchId,
        public string $fileFormat, // 'nacha', 'iso20022', 'swift', 'bai2', 'csv'
        public string $fileName,
        public string $fileContent,
        public int $fileSize,
        public string $checksum,
        public int $totalRecords,
        public Money $totalAmount,
        public array $includedPaymentIds,
        public array $failedPayments,
        public bool $success,
        public ?string $errorMessage = null,
        public \DateTimeImmutable $generatedAt,
        public array $metadata = [],
    ) {}

    /**
     * Create successful NACHA ACH file result
     */
    public static function nachaFile(
        string $batchId,
        string $fileName,
        string $fileContent,
        int $totalRecords,
        Money $totalAmount,
        array $includedPaymentIds,
    ): self {
        return new self(
            batchId: $batchId,
            fileFormat: 'nacha',
            fileName: $fileName,
            fileContent: $fileContent,
            fileSize: strlen($fileContent),
            checksum: hash('sha256', $fileContent),
            totalRecords: $totalRecords,
            totalAmount: $totalAmount,
            includedPaymentIds: $includedPaymentIds,
            failedPayments: [],
            success: true,
            generatedAt: new \DateTimeImmutable(),
            metadata: [
                'file_type' => 'NACHA ACH',
                'standard' => 'NACHA Operating Rules',
            ],
        );
    }

    /**
     * Create successful ISO 20022 file result
     */
    public static function iso20022File(
        string $batchId,
        string $fileName,
        string $fileContent,
        int $totalRecords,
        Money $totalAmount,
        array $includedPaymentIds,
        string $messageType = 'pain.001.001.03',
    ): self {
        return new self(
            batchId: $batchId,
            fileFormat: 'iso20022',
            fileName: $fileName,
            fileContent: $fileContent,
            fileSize: strlen($fileContent),
            checksum: hash('sha256', $fileContent),
            totalRecords: $totalRecords,
            totalAmount: $totalAmount,
            includedPaymentIds: $includedPaymentIds,
            failedPayments: [],
            success: true,
            generatedAt: new \DateTimeImmutable(),
            metadata: [
                'file_type' => 'ISO 20022 XML',
                'message_type' => $messageType,
            ],
        );
    }

    /**
     * Create failure result
     */
    public static function failure(
        string $batchId,
        string $fileFormat,
        string $errorMessage,
        array $failedPayments = [],
    ): self {
        return new self(
            batchId: $batchId,
            fileFormat: $fileFormat,
            fileName: '',
            fileContent: '',
            fileSize: 0,
            checksum: '',
            totalRecords: 0,
            totalAmount: Money::zero('USD'),
            includedPaymentIds: [],
            failedPayments: $failedPayments,
            success: false,
            errorMessage: $errorMessage,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get success count
     */
    public function getSuccessCount(): int
    {
        return count($this->includedPaymentIds);
    }

    /**
     * Get failure count
     */
    public function getFailureCount(): int
    {
        return count($this->failedPayments);
    }

    /**
     * Check if all payments were included
     */
    public function hasAllPayments(): bool
    {
        return empty($this->failedPayments);
    }

    /**
     * Get file extension
     */
    public function getFileExtension(): string
    {
        return match ($this->fileFormat) {
            'nacha' => 'ach',
            'iso20022' => 'xml',
            'swift' => 'mt',
            'bai2' => 'bai',
            'csv' => 'csv',
            default => 'txt',
        };
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'batch_id' => $this->batchId,
            'file_format' => $this->fileFormat,
            'file_name' => $this->fileName,
            'file_size' => $this->fileSize,
            'file_size_formatted' => $this->formatFileSize(),
            'checksum' => $this->checksum,
            'total_records' => $this->totalRecords,
            'total_amount' => $this->totalAmount->toArray(),
            'success_count' => $this->getSuccessCount(),
            'failure_count' => $this->getFailureCount(),
            'success' => $this->success,
            'error_message' => $this->errorMessage,
            'generated_at' => $this->generatedAt->format('c'),
        ];
    }

    /**
     * Format file size for display
     */
    private function formatFileSize(): string
    {
        $bytes = $this->fileSize;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
