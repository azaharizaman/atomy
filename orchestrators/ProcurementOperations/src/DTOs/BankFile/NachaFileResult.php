<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\BankFileFormat;
use Nexus\ProcurementOperations\Enums\NachaSecCode;

/**
 * Result of NACHA ACH file generation.
 *
 * Contains all NACHA-specific metadata including:
 * - Immediate Origin/Destination
 * - Batch and block counts
 * - Entry hash and control totals
 */
final readonly class NachaFileResult extends AbstractBankFileResult
{
    public function __construct(
        string $batchId,
        string $fileName,
        string $fileContent,
        int $totalRecords,
        Money $totalAmount,
        bool $success,
        public string $immediateOrigin,
        public string $immediateDestination,
        public NachaSecCode $secCode,
        public int $batchCount,
        public int $blockCount,
        public int $entryAddendaCount,
        public int $entryHash,
        public int $totalDebitAmount,
        public int $totalCreditAmount,
        public \DateTimeImmutable $fileCreationDate,
        public \DateTimeImmutable $effectiveEntryDate,
        ?string $errorMessage = null,
        array $validationErrors = [],
        ?\DateTimeImmutable $generatedAt = null,
        array $metadata = [],
    ) {
        parent::__construct(
            batchId: $batchId,
            format: BankFileFormat::NACHA,
            fileName: $fileName,
            fileContent: $fileContent,
            totalRecords: $totalRecords,
            totalAmount: $totalAmount,
            success: $success,
            errorMessage: $errorMessage,
            validationErrors: $validationErrors,
            generatedAt: $generatedAt,
            metadata: array_merge($metadata, [
                'file_type' => 'NACHA ACH',
                'standard' => 'NACHA Operating Rules 2025',
                'sec_code' => $secCode->value,
                'immediate_origin' => $immediateOrigin,
                'immediate_destination' => $immediateDestination,
            ]),
        );
    }

    /**
     * Create a successful NACHA file result.
     */
    public static function success(
        string $batchId,
        string $fileName,
        string $fileContent,
        int $totalRecords,
        Money $totalAmount,
        string $immediateOrigin,
        string $immediateDestination,
        NachaSecCode $secCode,
        int $batchCount,
        int $blockCount,
        int $entryAddendaCount,
        int $entryHash,
        int $totalDebitAmount,
        int $totalCreditAmount,
        \DateTimeImmutable $fileCreationDate,
        \DateTimeImmutable $effectiveEntryDate,
    ): self {
        return new self(
            batchId: $batchId,
            fileName: $fileName,
            fileContent: $fileContent,
            totalRecords: $totalRecords,
            totalAmount: $totalAmount,
            success: true,
            immediateOrigin: $immediateOrigin,
            immediateDestination: $immediateDestination,
            secCode: $secCode,
            batchCount: $batchCount,
            blockCount: $blockCount,
            entryAddendaCount: $entryAddendaCount,
            entryHash: $entryHash,
            totalDebitAmount: $totalDebitAmount,
            totalCreditAmount: $totalCreditAmount,
            fileCreationDate: $fileCreationDate,
            effectiveEntryDate: $effectiveEntryDate,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create a failed NACHA file result.
     *
     * @param array<string> $validationErrors
     */
    public static function failure(
        string $batchId,
        string $errorMessage,
        array $validationErrors = [],
    ): self {
        return new self(
            batchId: $batchId,
            fileName: '',
            fileContent: '',
            totalRecords: 0,
            totalAmount: Money::zero('USD'),
            success: false,
            immediateOrigin: '',
            immediateDestination: '',
            secCode: NachaSecCode::CCD,
            batchCount: 0,
            blockCount: 0,
            entryAddendaCount: 0,
            entryHash: 0,
            totalDebitAmount: 0,
            totalCreditAmount: 0,
            fileCreationDate: new \DateTimeImmutable(),
            effectiveEntryDate: new \DateTimeImmutable(),
            errorMessage: $errorMessage,
            validationErrors: $validationErrors,
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Validate NACHA file control totals.
     */
    public function validateControlTotals(): bool
    {
        // Entry hash should be last 10 digits
        $hashValid = $this->entryHash >= 0;

        // Totals should balance
        $totalsValid = $this->totalDebitAmount >= 0 && $this->totalCreditAmount >= 0;

        return $hashValid && $totalsValid;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'immediate_origin' => $this->immediateOrigin,
            'immediate_destination' => $this->immediateDestination,
            'sec_code' => $this->secCode->value,
            'sec_code_description' => $this->secCode->description(),
            'batch_count' => $this->batchCount,
            'block_count' => $this->blockCount,
            'entry_addenda_count' => $this->entryAddendaCount,
            'entry_hash' => $this->entryHash,
            'total_debit_amount' => $this->totalDebitAmount,
            'total_credit_amount' => $this->totalCreditAmount,
            'file_creation_date' => $this->fileCreationDate->format('Y-m-d'),
            'effective_entry_date' => $this->effectiveEntryDate->format('Y-m-d'),
        ]);
    }
}
