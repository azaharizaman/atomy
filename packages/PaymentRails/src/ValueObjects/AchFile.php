<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\FileStatus;

/**
 * Represents a complete ACH file in NACHA format.
 *
 * An ACH file contains:
 * - File Header Record (1)
 * - One or more Batches
 * - File Control Record (1)
 *
 * Each batch contains:
 * - Batch Header Record (5)
 * - Entry Detail Records (6)
 * - Addenda Records (7) - optional
 * - Batch Control Record (8)
 */
final class AchFile
{
    /**
     * @param string $id Unique identifier for the file
     * @param RoutingNumber $immediateDestination Receiving bank routing number
     * @param RoutingNumber $immediateOrigin Originating bank routing number
     * @param string $immediateDestinationName Receiving bank name
     * @param string $immediateOriginName Originating bank/company name
     * @param \DateTimeImmutable $fileCreationDateTime File creation timestamp
     * @param array<AchBatch> $batches Array of ACH batches
     * @param string $fileIdModifier File ID modifier (A-Z, 0-9)
     * @param FileStatus $status Current file status
     * @param string|null $referenceCode Optional reference code
     */
    public function __construct(
        public readonly string $id,
        public readonly RoutingNumber $immediateDestination,
        public readonly RoutingNumber $immediateOrigin,
        public readonly string $immediateDestinationName,
        public readonly string $immediateOriginName,
        public readonly \DateTimeImmutable $fileCreationDateTime,
        public readonly array $batches = [],
        public readonly string $fileIdModifier = 'A',
        public readonly FileStatus $status = FileStatus::GENERATED,
        public readonly ?string $referenceCode = null,
    ) {}

    /**
     * Create a new ACH file.
     */
    public static function create(
        string $id,
        RoutingNumber $immediateDestination,
        RoutingNumber $immediateOrigin,
        string $immediateDestinationName,
        string $immediateOriginName,
        ?\DateTimeImmutable $fileCreationDateTime = null,
    ): self {
        return new self(
            id: $id,
            immediateDestination: $immediateDestination,
            immediateOrigin: $immediateOrigin,
            immediateDestinationName: $immediateDestinationName,
            immediateOriginName: $immediateOriginName,
            fileCreationDateTime: $fileCreationDateTime ?? new \DateTimeImmutable(),
        );
    }

    /**
     * Add a batch to the file.
     */
    public function addBatch(AchBatch $batch): self
    {
        $batchNumber = count($this->batches) + 1;
        $numberedBatch = $batch->withBatchNumber($batchNumber);

        return new self(
            id: $this->id,
            immediateDestination: $this->immediateDestination,
            immediateOrigin: $this->immediateOrigin,
            immediateDestinationName: $this->immediateDestinationName,
            immediateOriginName: $this->immediateOriginName,
            fileCreationDateTime: $this->fileCreationDateTime,
            batches: [...$this->batches, $numberedBatch],
            fileIdModifier: $this->fileIdModifier,
            status: $this->status,
            referenceCode: $this->referenceCode,
        );
    }

    /**
     * Update the file status.
     */
    public function withStatus(FileStatus $status): self
    {
        return new self(
            id: $this->id,
            immediateDestination: $this->immediateDestination,
            immediateOrigin: $this->immediateOrigin,
            immediateDestinationName: $this->immediateDestinationName,
            immediateOriginName: $this->immediateOriginName,
            fileCreationDateTime: $this->fileCreationDateTime,
            batches: $this->batches,
            fileIdModifier: $this->fileIdModifier,
            status: $status,
            referenceCode: $this->referenceCode,
        );
    }

    /**
     * Get the total number of batches.
     */
    public function getBatchCount(): int
    {
        return count($this->batches);
    }

    /**
     * Get the total number of entries across all batches.
     */
    public function getEntryCount(): int
    {
        return array_reduce(
            $this->batches,
            fn (int $count, AchBatch $batch) => $count + $batch->getEntryCount(),
            0
        );
    }

    /**
     * Get the total number of addenda records.
     */
    public function getAddendaCount(): int
    {
        return array_reduce(
            $this->batches,
            fn (int $count, AchBatch $batch) => $count + $batch->getAddendaCount(),
            0
        );
    }

    /**
     * Get the block count (number of 940-character blocks).
     *
     * Each record is 94 characters. A block is 10 records.
     */
    public function getBlockCount(): int
    {
        $recordCount = $this->getRecordCount();

        return (int) ceil($recordCount / 10);
    }

    /**
     * Get the total record count.
     *
     * Records include:
     * - 1 File Header
     * - For each batch: 1 Batch Header + Entries + Addenda + 1 Batch Control
     * - 1 File Control
     */
    public function getRecordCount(): int
    {
        $count = 2; // File header and control

        foreach ($this->batches as $batch) {
            $count += 2; // Batch header and control
            $count += $batch->getEntryCount();
            $count += $batch->getAddendaCount();
        }

        return $count;
    }

    /**
     * Get the entry hash for file control.
     */
    public function getEntryHash(): int
    {
        $hash = 0;

        foreach ($this->batches as $batch) {
            $hash += $batch->getEntryHash();
        }

        return $hash % 10000000000;
    }

    /**
     * Get total debit amount across all batches.
     */
    public function getTotalDebits(): Money
    {
        $total = Money::zero('USD');

        foreach ($this->batches as $batch) {
            $total = $total->add($batch->getTotalDebits());
        }

        return $total;
    }

    /**
     * Get total credit amount across all batches.
     */
    public function getTotalCredits(): Money
    {
        $total = Money::zero('USD');

        foreach ($this->batches as $batch) {
            $total = $total->add($batch->getTotalCredits());
        }

        return $total;
    }

    /**
     * Get the formatted immediate destination (with leading space).
     */
    public function getFormattedImmediateDestination(): string
    {
        return ' ' . $this->immediateDestination->value;
    }

    /**
     * Get the formatted immediate origin (with leading space).
     */
    public function getFormattedImmediateOrigin(): string
    {
        return ' ' . $this->immediateOrigin->value;
    }

    /**
     * Get the formatted destination name (23 chars).
     */
    public function getFormattedDestinationName(): string
    {
        return str_pad(mb_substr($this->immediateDestinationName, 0, 23), 23);
    }

    /**
     * Get the formatted origin name (23 chars).
     */
    public function getFormattedOriginName(): string
    {
        return str_pad(mb_substr($this->immediateOriginName, 0, 23), 23);
    }

    /**
     * Get the file creation date in YYMMDD format.
     */
    public function getFileCreationDate(): string
    {
        return $this->fileCreationDateTime->format('ymd');
    }

    /**
     * Get the file creation time in HHMM format.
     */
    public function getFileCreationTime(): string
    {
        return $this->fileCreationDateTime->format('Hi');
    }

    /**
     * Check if the file has any batches.
     */
    public function hasBatches(): bool
    {
        return $this->getBatchCount() > 0;
    }

    /**
     * Get a suggested filename for this ACH file.
     */
    public function getSuggestedFilename(): string
    {
        $date = $this->fileCreationDateTime->format('Ymd');
        $time = $this->fileCreationDateTime->format('His');

        return sprintf('ACH_%s_%s_%s.txt', $date, $time, $this->fileIdModifier);
    }
}
