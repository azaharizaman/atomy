<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Services;

use Nexus\AuditLogger\Contracts\AuditLogRepositoryInterface;

/**
 * Service for exporting audit logs
 * Satisfies: FUN-AUD-0191
 *
 * @package Nexus\AuditLogger\Services
 */
class AuditLogExportService
{
    private AuditLogRepositoryInterface $repository;

    public function __construct(AuditLogRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Export audit logs to CSV format
     *
     * @param array $filters
     * @param int $limit
     * @return string CSV content
     */
    public function exportToCsv(array $filters = [], int $limit = 10000): string
    {
        $logs = $this->repository->exportToArray($filters, $limit);

        if (empty($logs)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Write headers
        fputcsv($output, array_keys($logs[0]));

        // Write data rows
        foreach ($logs as $log) {
            fputcsv($output, $log);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export audit logs to JSON format
     *
     * @param array $filters
     * @param int $limit
     * @return string JSON content
     */
    public function exportToJson(array $filters = [], int $limit = 10000): string
    {
        $logs = $this->repository->exportToArray($filters, $limit);
        return json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export audit logs to array (for PDF generation in app layer)
     *
     * @param array $filters
     * @param int $limit
     * @return array
     */
    public function exportToArray(array $filters = [], int $limit = 10000): array
    {
        return $this->repository->exportToArray($filters, $limit);
    }
}
