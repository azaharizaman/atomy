<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\BankFile;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\BankFileFormat;

/**
 * Interface for bank file generation results.
 */
interface BankFileResultInterface
{
    /**
     * Get the batch ID this file was generated for.
     */
    public function getBatchId(): string;

    /**
     * Get the file format.
     */
    public function getFormat(): BankFileFormat;

    /**
     * Get the generated file name.
     */
    public function getFileName(): string;

    /**
     * Get the generated file content.
     */
    public function getFileContent(): string;

    /**
     * Get the file size in bytes.
     */
    public function getFileSize(): int;

    /**
     * Get the file checksum (SHA-256).
     */
    public function getChecksum(): string;

    /**
     * Get the total number of records/transactions in the file.
     */
    public function getTotalRecords(): int;

    /**
     * Get the total amount of all transactions.
     */
    public function getTotalAmount(): Money;

    /**
     * Check if the generation was successful.
     */
    public function isSuccess(): bool;

    /**
     * Get the error message if generation failed.
     */
    public function getErrorMessage(): ?string;

    /**
     * Get validation errors if any.
     *
     * @return array<string>
     */
    public function getValidationErrors(): array;

    /**
     * Get the timestamp when the file was generated.
     */
    public function getGeneratedAt(): \DateTimeImmutable;

    /**
     * Get format-specific metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
