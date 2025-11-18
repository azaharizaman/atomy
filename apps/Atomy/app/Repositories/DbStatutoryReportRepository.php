<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StatutoryReport;
use Nexus\Statutory\Contracts\StatutoryReportInterface;
use Nexus\Statutory\Contracts\StatutoryReportRepositoryInterface;
use Nexus\Statutory\Exceptions\ReportNotFoundException;
use Nexus\Statutory\ValueObjects\ReportFormat;

/**
 * Database implementation of StatutoryReportRepositoryInterface.
 */
final class DbStatutoryReportRepository implements StatutoryReportRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?StatutoryReportInterface
    {
        return StatutoryReport::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function getReports(
        string $tenantId,
        ?string $reportType = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null
    ): array {
        $query = StatutoryReport::where('tenant_id', $tenantId);

        if ($reportType !== null) {
            $query->where('report_type', $reportType);
        }

        if ($from !== null) {
            $query->where('start_date', '>=', $from);
        }

        if ($to !== null) {
            $query->where('end_date', '<=', $to);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * {@inheritDoc}
     */
    public function save(StatutoryReportInterface $report): void
    {
        if (!$report instanceof StatutoryReport) {
            throw new \InvalidArgumentException('Report must be an instance of StatutoryReport model');
        }

        $report->save();
    }

    /**
     * {@inheritDoc}
     */
    public function create(
        string $tenantId,
        string $reportType,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ReportFormat $format,
        string $status = 'pending'
    ): StatutoryReportInterface {
        return StatutoryReport::create([
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'format' => $format->value,
            'status' => $status,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $id): void
    {
        $report = StatutoryReport::find($id);
        
        if ($report === null) {
            throw new ReportNotFoundException($id);
        }

        $report->delete();
    }
}
