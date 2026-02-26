<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

use Nexus\Telemetry\ValueObjects\ExportFormat;
use Nexus\Telemetry\ValueObjects\QuerySpec;

/**
 * Metric Exporter Interface
 *
 * Contract for exporting metrics in standard formats (Prometheus, OpenMetrics, JSON).
 *
 * @package Nexus\Telemetry\Contracts
 */
interface MetricExporterInterface
{
    /**
     * Export metrics in specified format.
     *
     * @param QuerySpec $spec Query specification for metrics to export
     * @param ExportFormat $format Export format
     * @return string Formatted export string
     */
    public function export(QuerySpec $spec, ExportFormat $format): string;
}
