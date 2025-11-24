<?php

declare(strict_types=1);

namespace Nexus\Tax\Contracts;

use Nexus\Tax\ValueObjects\ComplianceReportLine;

/**
 * Tax Reporting Interface
 * 
 * Generates tax compliance reports (VAT returns, sales tax filings, etc.)
 * 
 * Application layer implements this with:
 * - Database aggregation queries
 * - Report templates
 * - Export to PDF/CSV/XML
 */
interface TaxReportingInterface
{
    /**
     * Generate compliance report for jurisdiction and period
     * 
     * Creates structured report data (e.g., VAT return, sales tax filing).
     * 
     * @param string $jurisdictionCode Jurisdiction to report on
     * @param \DateTimeInterface $periodStart Period start date
     * @param \DateTimeInterface $periodEnd Period end date
     * @param string|null $reportType Optional report type identifier
     * 
     * @return array<ComplianceReportLine> Report lines
     */
    public function generateReport(
        string $jurisdictionCode,
        \DateTimeInterface $periodStart,
        \DateTimeInterface $periodEnd,
        ?string $reportType = null
    ): array;

    /**
     * Get total tax collected for jurisdiction and period
     * 
     * Simple aggregation for reporting.
     * 
     * @param string $jurisdictionCode Jurisdiction code
     * @param \DateTimeInterface $periodStart Period start
     * @param \DateTimeInterface $periodEnd Period end
     * 
     * @return \Nexus\Currency\ValueObjects\Money Total tax collected
     */
    public function getTotalTaxCollected(
        string $jurisdictionCode,
        \DateTimeInterface $periodStart,
        \DateTimeInterface $periodEnd
    ): \Nexus\Currency\ValueObjects\Money;

    /**
     * Get tax collected by tax type for jurisdiction
     * 
     * Breaks down by VAT, GST, sales tax, etc.
     * 
     * @param string $jurisdictionCode Jurisdiction code
     * @param \DateTimeInterface $periodStart Period start
     * @param \DateTimeInterface $periodEnd Period end
     * 
     * @return array<string, \Nexus\Currency\ValueObjects\Money> Tax type => amount
     */
    public function getTaxByType(
        string $jurisdictionCode,
        \DateTimeInterface $periodStart,
        \DateTimeInterface $periodEnd
    ): array;

    /**
     * Get exemptions summary for jurisdiction
     * 
     * Total exempted amounts by exemption reason.
     * 
     * @param string $jurisdictionCode Jurisdiction code
     * @param \DateTimeInterface $periodStart Period start
     * @param \DateTimeInterface $periodEnd Period end
     * 
     * @return array<string, array{amount: \Nexus\Currency\ValueObjects\Money, count: int}>
     */
    public function getExemptionsSummary(
        string $jurisdictionCode,
        \DateTimeInterface $periodStart,
        \DateTimeInterface $periodEnd
    ): array;
}
