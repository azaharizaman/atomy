<?php

declare(strict_types=1);

namespace Nexus\Statutory\Contracts;

use Nexus\Statutory\ValueObjects\ReportFormat;

/**
 * Interface for generating statutory reports in various formats.
 */
interface TaxonomyReportGeneratorInterface
{
    /**
     * Generate a statutory report.
     *
     * @param string $tenantId The tenant identifier
     * @param string $reportType The report type identifier
     * @param \DateTimeImmutable $startDate Report period start date
     * @param \DateTimeImmutable $endDate Report period end date
     * @param ReportFormat $format Desired output format
     * @param array<string, mixed> $options Additional generation options
     * @return string The report ID
     * @throws \Nexus\Statutory\Exceptions\InvalidReportTypeException
     * @throws \Nexus\Statutory\Exceptions\DataExtractionException
     * @throws \Nexus\Statutory\Exceptions\ValidationException
     */
    public function generateReport(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ReportFormat $format,
        array $options = []
    ): string;

    /**
     * Get the metadata for a specific report type.
     *
     * @param string $reportType The report type identifier
     * @return ReportMetadataInterface
     * @throws \Nexus\Statutory\Exceptions\InvalidReportTypeException
     */
    public function getReportMetadata(string $reportType): ReportMetadataInterface;

    /**
     * Validate report data against schema.
     *
     * @param string $reportType The report type identifier
     * @param array<string, mixed> $data The report data to validate
     * @return array<string> Validation errors (empty if valid)
     */
    public function validateReportData(string $reportType, array $data): array;

    /**
     * Get all supported report types.
     *
     * @return array<string> Report type identifiers
     */
    public function getSupportedReportTypes(): array;
}
