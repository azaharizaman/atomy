<?php

declare(strict_types=1);

namespace Nexus\Statutory\Contracts;

/**
 * Repository interface for statutory report persistence.
 */
interface StatutoryReportRepositoryInterface
{
    /**
     * Find a statutory report by ID.
     *
     * @param string $id The report ID
     * @return StatutoryReportInterface|null
     */
    public function findById(string $id): ?StatutoryReportInterface;

    /**
     * Get all reports for a tenant.
     *
     * @param string $tenantId The tenant identifier
     * @param string|null $reportType Optional filter by report type
     * @param \DateTimeImmutable|null $from Optional start date filter
     * @param \DateTimeImmutable|null $to Optional end date filter
     * @return array<StatutoryReportInterface>
     */
    public function getReports(
        string $tenantId,
        ?string $reportType = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array;

    /**
     * Save a statutory report.
     *
     * @param StatutoryReportInterface $report The report to save
     * @return void
     */
    public function save(StatutoryReportInterface $report): void;

    /**
     * Delete a statutory report.
     *
     * @param string $id The report ID
     * @return void
     */
    public function delete(string $id): void;
}
