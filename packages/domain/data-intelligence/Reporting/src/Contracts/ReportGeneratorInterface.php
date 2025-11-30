<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Analytics\Contracts\QueryResultInterface;
use Nexus\Reporting\ValueObjects\ReportFormat;
use Nexus\Reporting\ValueObjects\ReportResult;

/**
 * Generates reports by executing Analytics queries and rendering output via Export package.
 */
interface ReportGeneratorInterface
{
    /**
     * Generate a report from a report definition.
     *
     * Executes the underlying Analytics query, applies template formatting,
     * and stores the result in the configured format.
     *
     * @param ReportDefinitionInterface $definition The report definition
     * @param array<string, mixed> $parameters Optional parameter overrides
     * @return ReportResult The generation result with file path and metadata
     * @throws \Nexus\Reporting\Exceptions\ReportGenerationException
     * @throws \Nexus\Reporting\Exceptions\UnauthorizedReportException
     */
    public function generate(
        ReportDefinitionInterface $definition,
        array $parameters = []
    ): ReportResult;

    /**
     * Generate an ad-hoc report directly from an Analytics query.
     *
     * Bypasses report definition storage but still enforces permissions.
     *
     * @param string $queryId The Analytics query ID
     * @param ReportFormat $format The output format
     * @param array<string, mixed> $parameters Query parameters
     * @return ReportResult
     * @throws \Nexus\Reporting\Exceptions\ReportGenerationException
     * @throws \Nexus\Reporting\Exceptions\UnauthorizedReportException
     */
    public function generateFromQuery(
        string $queryId,
        ReportFormat $format,
        array $parameters = []
    ): ReportResult;

    /**
     * Preview a report without storing the output (for interactive dashboards).
     *
     * Returns the raw QueryResult from Analytics for AJAX/real-time rendering.
     * Does NOT create entries in reports_generated table.
     *
     * Implements FUN-REP-0213 (Interactive Dashboard Generation).
     *
     * @param ReportDefinitionInterface $definition
     * @param array<string, mixed> $parameters
     * @return QueryResultInterface The raw analytics result
     * @throws \Nexus\Reporting\Exceptions\UnauthorizedReportException
     */
    public function previewReport(
        ReportDefinitionInterface $definition,
        array $parameters = []
    ): QueryResultInterface;

    /**
     * Generate reports in batch for multiple entities.
     *
     * Optimized for high-volume scenarios (e.g., monthly invoices for 1,000 customers).
     * Returns array of scheduled job IDs for tracking.
     *
     * @param ReportDefinitionInterface $definition
     * @param array<string> $entityIds Entity IDs to generate reports for
     * @return array<string> Scheduler job IDs
     */
    public function generateBatch(
        ReportDefinitionInterface $definition,
        array $entityIds
    ): array;
}
