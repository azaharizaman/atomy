<?php

declare(strict_types=1);

namespace Nexus\Accounting\Contracts;

use Nexus\Accounting\Core\ValueObjects\StatementFormat;

/**
 * Export formatting contract.
 *
 * Handles conversion of statements to various output formats.
 */
interface ReportFormatterInterface
{
    /**
     * Format a statement for export.
     *
     * @param array<string, mixed> $options Formatting options
     * @return string Formatted output (file path, JSON string, etc.)
     */
    public function format(
        FinancialStatementInterface $statement,
        StatementFormat $format,
        array $options = []
    ): string;

    /**
     * Check if a format is supported.
     */
    public function supportsFormat(StatementFormat $format): bool;

    /**
     * Get available formatting options for a format.
     *
     * @return array<string, mixed>
     */
    public function getFormatOptions(StatementFormat $format): array;

    /**
     * Generate a formatted filename.
     */
    public function generateFilename(
        FinancialStatementInterface $statement,
        StatementFormat $format
    ): string;
}
