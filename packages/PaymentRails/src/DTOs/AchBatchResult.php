<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\FileStatus;
use Nexus\PaymentRails\ValueObjects\AchFile;

/**
 * Result DTO for ACH batch submission.
 */
final readonly class AchBatchResult
{
    /**
     * @param string $fileId Unique file identifier
     * @param string $batchId Batch identifier
     * @param bool $success Whether the batch was created successfully
     * @param FileStatus $status Current file status
     * @param int $entryCount Number of entries in the batch
     * @param Money $totalDebits Total debit amount
     * @param Money $totalCredits Total credit amount
     * @param string|null $fileContents Generated NACHA file contents
     * @param string|null $fileName Suggested filename
     * @param array<string> $errors Any validation errors
     * @param array<string, string> $traceNumbers Map of external IDs to trace numbers
     * @param \DateTimeImmutable $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $effectiveDate Effective entry date
     */
    public function __construct(
        public string $fileId,
        public string $batchId,
        public bool $success,
        public FileStatus $status,
        public int $entryCount,
        public Money $totalDebits,
        public Money $totalCredits,
        public ?string $fileContents = null,
        public ?string $fileName = null,
        public array $errors = [],
        public array $traceNumbers = [],
        public \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        public ?\DateTimeImmutable $effectiveDate = null,
    ) {}

    /**
     * Create a successful result from an ACH file.
     *
     * @param array<string, string> $traceNumbers
     */
    public static function success(
        AchFile $file,
        string $batchId,
        string $fileContents,
        array $traceNumbers = [],
    ): self {
        return new self(
            fileId: $file->id,
            batchId: $batchId,
            success: true,
            status: $file->status,
            entryCount: $file->getEntryCount(),
            totalDebits: $file->getTotalDebits(),
            totalCredits: $file->getTotalCredits(),
            fileContents: $fileContents,
            fileName: $file->getSuggestedFilename(),
            traceNumbers: $traceNumbers,
            effectiveDate: $file->fileCreationDate,
        );
    }

    /**
     * Create a failure result.
     *
     * @param array<string> $errors
     */
    public static function failure(
        string $batchId,
        array $errors,
    ): self {
        return new self(
            fileId: '',
            batchId: $batchId,
            success: false,
            status: FileStatus::REJECTED,
            entryCount: 0,
            totalDebits: Money::zero('USD'),
            totalCredits: Money::zero('USD'),
            errors: $errors,
        );
    }

    /**
     * Get the trace number for a specific external ID.
     */
    public function getTraceNumber(string $externalId): ?string
    {
        return $this->traceNumbers[$externalId] ?? null;
    }

    /**
     * Get the total transaction amount (debits + credits).
     */
    public function getTotalAmount(): Money
    {
        return $this->totalDebits->add($this->totalCredits);
    }

    /**
     * Check if the batch is balanced.
     */
    public function isBalanced(): bool
    {
        return $this->totalDebits->equals($this->totalCredits);
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
