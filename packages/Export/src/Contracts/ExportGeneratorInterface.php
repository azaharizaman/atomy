<?php

declare(strict_types=1);

namespace Nexus\Export\Contracts;

use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportResult;

/**
 * Export generator contract
 * 
 * Domain packages implement this to convert their data structures
 * into the standardized ExportDefinition format.
 * 
 * Example implementations:
 * - FinancialStatementExportGenerator (Nexus\Accounting)
 * - PayslipExportGenerator (Nexus\Payroll)
 * - InvoiceExportGenerator (Nexus\Receivable)
 */
interface ExportGeneratorInterface
{
    /**
     * Convert domain data to standardized export definition
     * 
     * The returned ExportDefinition MUST be validated against
     * the current schema version before being passed to formatters.
     * 
     * @return ExportDefinition Validated export definition
     * @throws \Nexus\Export\Exceptions\InvalidDefinitionException
     */
    public function toExportDefinition(): ExportDefinition;

    /**
     * Get supported schema versions
     * 
     * @return string Semantic version range (e.g., '1.0-1.2')
     */
    public function supportsSchemaVersion(): string;

    /**
     * Generate export from data.
     *
     * @param array $data
     * @param string $format
     * @return ExportResult
     */
    public function generate(array $data, string $format): ExportResult;
}
