<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\PaymentRails\DTOs\AchBatchRequest;
use Nexus\PaymentRails\ValueObjects\AchFile;

/**
 * Contract for generating NACHA ACH files.
 *
 * Responsible for formatting ACH data into NACHA-compliant file format
 * that can be submitted to banks and ACH processors.
 */
interface NachaFormatterInterface
{
    /**
     * Generate a complete NACHA file from a batch request.
     */
    public function generateFile(AchBatchRequest $request): string;

    /**
     * Generate a NACHA file from an ACH file value object.
     */
    public function formatAchFile(AchFile $file): string;

    /**
     * Generate the file header record (1 record).
     *
     * @param array<string, mixed> $headerData
     */
    public function generateFileHeader(array $headerData): string;

    /**
     * Generate the file control record (9 record).
     *
     * @param array<string, mixed> $controlData
     */
    public function generateFileControl(array $controlData): string;

    /**
     * Generate a batch header record (5 record).
     *
     * @param array<string, mixed> $batchData
     */
    public function generateBatchHeader(array $batchData): string;

    /**
     * Generate a batch control record (8 record).
     *
     * @param array<string, mixed> $batchData
     */
    public function generateBatchControl(array $batchData): string;

    /**
     * Generate an entry detail record (6 record).
     *
     * @param array<string, mixed> $entryData
     */
    public function generateEntryDetail(array $entryData): string;

    /**
     * Generate an addenda record (7 record).
     *
     * @param array<string, mixed> $addendaData
     */
    public function generateAddenda(array $addendaData): string;

    /**
     * Parse a NACHA file into its component parts.
     *
     * @param string $content Raw NACHA file content
     * @return array<string, mixed> Parsed data
     */
    public function parseFile(string $content): array;

    /**
     * Validate a NACHA file format.
     *
     * @param string $content
     * @return array<string> Validation errors
     */
    public function validateFormat(string $content): array;

    /**
     * Calculate the entry hash for batch control.
     *
     * @param array<string> $routingNumbers
     */
    public function calculateEntryHash(array $routingNumbers): string;

    /**
     * Format a field to NACHA specification.
     *
     * @param mixed $value
     * @param int $length
     * @param string $type 'alpha' | 'numeric' | 'alphanumeric'
     * @param string $align 'left' | 'right'
     * @param string $padChar Padding character
     */
    public function formatField(
        mixed $value,
        int $length,
        string $type = 'alphanumeric',
        string $align = 'left',
        string $padChar = ' ',
    ): string;
}
