<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Contracts;

use Nexus\FinancialStatements\Enums\StatementFormat;

/**
 * Contract for exporting financial statements.
 *
 * This interface is implemented by the orchestrator using Nexus\Export
 * and Nexus\Reporting for actual rendering.
 */
interface StatementExportAdapterInterface
{
    /**
     * Export a financial statement to the specified format.
     *
     * @param FinancialStatementInterface $statement The statement to export
     * @param StatementFormat $format The output format
     * @param array<string, mixed> $options Export options
     * @return string The exported content or file path
     */
    public function export(
        FinancialStatementInterface $statement,
        StatementFormat $format,
        array $options = []
    ): string;

    /**
     * Get supported export formats.
     *
     * @return array<StatementFormat>
     */
    public function getSupportedFormats(): array;
}
