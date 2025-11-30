<?php

declare(strict_types=1);

namespace Nexus\Statutory\Contracts;

use Nexus\Statutory\ValueObjects\ReportFormat;

/**
 * Interface representing a statutory report entity.
 */
interface StatutoryReportInterface
{
    /**
     * Get the report identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     *
     * @return string
     */
    public function getTenantId(): string;

    /**
     * Get the report type identifier.
     *
     * @return string
     */
    public function getReportType(): string;

    /**
     * Get the report period start date.
     *
     * @return \DateTimeImmutable
     */
    public function getStartDate(): \DateTimeImmutable;

    /**
     * Get the report period end date.
     *
     * @return \DateTimeImmutable
     */
    public function getEndDate(): \DateTimeImmutable;

    /**
     * Get the report format.
     *
     * @return ReportFormat
     */
    public function getFormat(): ReportFormat;

    /**
     * Get the report status.
     *
     * @return string Example: 'draft', 'generated', 'filed', 'rejected'
     */
    public function getStatus(): string;

    /**
     * Get the file path or URL for the generated report.
     *
     * @return string|null
     */
    public function getFilePath(): ?string;

    /**
     * Get the report metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get the creation timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get the last update timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getUpdatedAt(): \DateTimeImmutable;
}
