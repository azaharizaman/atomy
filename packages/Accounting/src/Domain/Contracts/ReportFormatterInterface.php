<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\StatementFormat;

/**
 * Report Formatter interface.
 *
 * Formats financial statements for export.
 */
interface ReportFormatterInterface
{
    /**
     * Format a statement for output.
     *
     * @param array<string, mixed> $options
     */
    public function format(
        FinancialStatementInterface $statement,
        StatementFormat $format,
        array $options = []
    ): string;

    /**
     * Get supported formats.
     *
     * @return StatementFormat[]
     */
    public function getSupportedFormats(): array;

    /**
     * Check if a format is supported.
     */
    public function supportsFormat(StatementFormat $format): bool;
}
