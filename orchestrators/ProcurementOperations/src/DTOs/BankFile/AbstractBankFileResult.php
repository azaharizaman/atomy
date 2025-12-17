<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\BankFileFormat;

/**
 * Abstract base class for bank file generation results.
 *
 * Provides common functionality for all bank file result types.
 */
abstract readonly class AbstractBankFileResult implements BankFileResultInterface
{
    /**
     * @param array<string> $validationErrors
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        protected string $batchId,
        protected BankFileFormat $format,
        protected string $fileName,
        protected string $fileContent,
        protected int $totalRecords,
        protected Money $totalAmount,
        protected bool $success,
        protected ?string $errorMessage = null,
        protected array $validationErrors = [],
        protected ?\DateTimeImmutable $generatedAt = null,
        protected array $metadata = [],
    ) {}

    public function getBatchId(): string
    {
        return $this->batchId;
    }

    public function getFormat(): BankFileFormat
    {
        return $this->format;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFileContent(): string
    {
        return $this->fileContent;
    }

    public function getFileSize(): int
    {
        return strlen($this->fileContent);
    }

    public function getChecksum(): string
    {
        return hash('sha256', $this->fileContent);
    }

    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }

    public function getTotalAmount(): Money
    {
        return $this->totalAmount;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt ?? new \DateTimeImmutable();
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Format file size for display.
     */
    protected function formatFileSize(): string
    {
        $bytes = $this->getFileSize();

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function toArray(): array
    {
        return [
            'batch_id' => $this->batchId,
            'format' => $this->format->value,
            'format_label' => $this->format->label(),
            'file_name' => $this->fileName,
            'file_size' => $this->getFileSize(),
            'file_size_formatted' => $this->formatFileSize(),
            'checksum' => $this->getChecksum(),
            'total_records' => $this->totalRecords,
            'total_amount' => $this->totalAmount->getAmount(),
            'total_amount_currency' => $this->totalAmount->getCurrency(),
            'success' => $this->success,
            'error_message' => $this->errorMessage,
            'validation_errors' => $this->validationErrors,
            'generated_at' => $this->getGeneratedAt()->format('c'),
            'metadata' => $this->metadata,
        ];
    }
}
