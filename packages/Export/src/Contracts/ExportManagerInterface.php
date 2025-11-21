<?php

declare(strict_types=1);

namespace Nexus\Export\Contracts;

use Nexus\Export\ValueObjects\ExportDefinition;
use Nexus\Export\ValueObjects\ExportDestination;
use Nexus\Export\ValueObjects\ExportFormat;
use Nexus\Export\ValueObjects\ExportResult;

/**
 * Export Manager Interface
 *
 * Main orchestration service for exports.
 */
interface ExportManagerInterface
{
    /**
     * Export definition to specified format and destination
     *
     * @param ExportDefinition $definition The export definition
     * @param ExportFormat $format The output format
     * @param ExportDestination $destination The destination
     * @return ExportResult The export result
     * @throws \Nexus\Export\Exceptions\UnsupportedFormatException
     * @throws \Nexus\Export\Exceptions\UnsupportedDestinationException
     */
    public function export(
        ExportDefinition $definition,
        ExportFormat $format,
        ExportDestination $destination
    ): ExportResult;
}
