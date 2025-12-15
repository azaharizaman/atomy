<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Strategies\BankFile;

use Nexus\ProcurementOperations\DTOs\BankFile\BankFileResultInterface;
use Nexus\ProcurementOperations\DTOs\Financial\PaymentBatchData;
use Nexus\ProcurementOperations\Enums\BankFileFormat;

/**
 * Strategy interface for bank file generation.
 *
 * Each bank file format (NACHA, Positive Pay, SWIFT, etc.) implements
 * this interface to provide format-specific file generation logic.
 */
interface BankFileGeneratorInterface
{
    /**
     * Get the bank file format this generator produces.
     */
    public function getFormat(): BankFileFormat;

    /**
     * Get the version of this generator.
     *
     * Version is used to support multiple generator versions (e.g., NACHA 2025 rules).
     */
    public function getVersion(): string;

    /**
     * Check if this generator can handle the given payment batch.
     */
    public function supports(PaymentBatchData $batch): bool;

    /**
     * Validate the payment batch before generation.
     *
     * @param PaymentBatchData $batch Payment batch to validate
     * @return array<string> List of validation errors (empty if valid)
     */
    public function validate(PaymentBatchData $batch): array;

    /**
     * Generate the bank file for the given payment batch.
     *
     * @param PaymentBatchData $batch Payment batch to generate file for
     * @return BankFileResultInterface Result containing file content and metadata
     */
    public function generate(PaymentBatchData $batch): BankFileResultInterface;

    /**
     * Get the file extension for generated files.
     */
    public function getFileExtension(): string;

    /**
     * Get the MIME type for generated files.
     */
    public function getMimeType(): string;

    /**
     * Check if this generator requires specific configuration.
     */
    public function requiresConfiguration(): bool;
}
