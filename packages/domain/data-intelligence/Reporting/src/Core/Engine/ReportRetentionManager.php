<?php

declare(strict_types=1);

namespace Nexus\Reporting\Core\Engine;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Contracts\ReportRetentionInterface;
use Nexus\Reporting\ValueObjects\RetentionTier;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages the tiered retention lifecycle of generated reports.
 *
 * Tier 1 (Active): 90 days in hot storage
 * Tier 2 (Archive): 7 years in deep archive (S3 Glacier)
 * Tier 3 (Purged): Permanently deleted
 */
final readonly class ReportRetentionManager implements ReportRetentionInterface
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository,
        private StorageDriverInterface $storage,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function applyRetentionPolicy(): array
    {
        $this->logger->info('Starting retention policy application');

        $stats = [
            'active_to_archived' => 0,
            'archived_to_purged' => 0,
            'errors' => [],
        ];

        try {
            // Transition: Active → Archived (90 days)
            $activeReports = $this->reportRepository->findReportsForRetentionTransition(
                RetentionTier::ACTIVE->value,
                (new \DateTimeImmutable())->modify('-90 days')
            );

            foreach ($activeReports as $report) {
                try {
                    if ($this->archiveReport($report['id'])) {
                        $stats['active_to_archived']++;
                    }
                } catch (\Throwable $e) {
                    $stats['errors'][] = "Failed to archive report {$report['id']}: {$e->getMessage()}";
                    $this->logger->error('Archive transition failed', [
                        'report_id' => $report['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Transition: Archived → Purged (7 years = 2555 days)
            $archivedReports = $this->reportRepository->findReportsForRetentionTransition(
                RetentionTier::ARCHIVED->value,
                (new \DateTimeImmutable())->modify('-2555 days')
            );

            foreach ($archivedReports as $report) {
                try {
                    if ($this->purgeReport($report['id'])) {
                        $stats['archived_to_purged']++;
                    }
                } catch (\Throwable $e) {
                    $stats['errors'][] = "Failed to purge report {$report['id']}: {$e->getMessage()}";
                    $this->logger->error('Purge transition failed', [
                        'report_id' => $report['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->logger->info('Retention policy completed', [
                'active_to_archived' => $stats['active_to_archived'],
                'archived_to_purged' => $stats['archived_to_purged'],
                'error_count' => count($stats['errors']),
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Retention policy application failed', [
                'error' => $e->getMessage(),
            ]);
            $stats['errors'][] = "Global error: {$e->getMessage()}";
        }

        return $stats;
    }

    /**
     * {@inheritdoc}
     */
    public function archiveReport(string $reportGeneratedId): bool
    {
        $this->logger->info('Archiving report', ['report_id' => $reportGeneratedId]);

        $report = $this->reportRepository->findGeneratedReportById($reportGeneratedId);
        if (!$report) {
            $this->logger->warning('Report not found for archival', [
                'report_id' => $reportGeneratedId,
            ]);
            return false;
        }

        // Skip if already archived or purged
        if ($report['retention_tier'] !== RetentionTier::ACTIVE->value) {
            return false;
        }

        try {
            // Move file to archive storage tier
            // In real implementation, this would use cloud provider APIs to move
            // from STANDARD to GLACIER storage class
            $filePath = $report['file_path'];
            if ($filePath && $this->storage->exists($filePath)) {
                // Placeholder: In production, use cloud provider's tiering API
                // e.g., AWS S3: transition to GLACIER storage class
                $this->logger->info('File moved to archive tier', [
                    'file_path' => $filePath,
                ]);
            }

            // Update retention tier in database
            $this->reportRepository->updateRetentionTier(
                $reportGeneratedId,
                RetentionTier::ARCHIVED->value
            );

            // Audit log
            $this->auditLogger->log(
                logName: 'report_archived',
                description: "Report moved to archive tier (7-year retention)",
                subjectType: 'Report',
                subjectId: $reportGeneratedId,
                level: 3, // High
                properties: [
                    'previous_tier' => RetentionTier::ACTIVE->value,
                    'new_tier' => RetentionTier::ARCHIVED->value,
                    'file_path' => $filePath,
                ]
            );

            return true;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to archive report', [
                'report_id' => $reportGeneratedId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function purgeReport(string $reportGeneratedId): bool
    {
        $this->logger->warning('Purging report permanently', [
            'report_id' => $reportGeneratedId,
        ]);

        $report = $this->reportRepository->findGeneratedReportById($reportGeneratedId);
        if (!$report) {
            return false;
        }

        // Only purge archived reports
        if ($report['retention_tier'] !== RetentionTier::ARCHIVED->value) {
            return false;
        }

        try {
            // Delete file from storage
            $filePath = $report['file_path'];
            if ($filePath && $this->storage->exists($filePath)) {
                $this->storage->delete($filePath);
                $this->logger->info('File deleted from storage', [
                    'file_path' => $filePath,
                ]);
            }

            // Update retention tier (keep metadata for compliance audit)
            $this->reportRepository->updateRetentionTier(
                $reportGeneratedId,
                RetentionTier::PURGED->value
            );

            // Audit log (CRITICAL severity - irreversible action)
            $this->auditLogger->log(
                logName: 'report_purged',
                description: "Report permanently deleted (GDPR/data minimization)",
                subjectType: 'Report',
                subjectId: $reportGeneratedId,
                level: 4, // Critical
                properties: [
                    'previous_tier' => RetentionTier::ARCHIVED->value,
                    'new_tier' => RetentionTier::PURGED->value,
                    'file_path' => $filePath,
                    'purged_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]
            );

            return true;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to purge report', [
                'report_id' => $reportGeneratedId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRetentionStatistics(): array
    {
        // Placeholder - would need repository method to aggregate stats
        return [
            'total_active' => 0,
            'total_archived' => 0,
            'total_purged' => 0,
            'total_size_bytes' => 0,
            'oldest_active' => null,
            'oldest_archived' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function checkTransitionStatus(string $reportGeneratedId): array
    {
        $report = $this->reportRepository->findGeneratedReportById($reportGeneratedId);
        if (!$report) {
            return [
                'current_tier' => null,
                'should_transition' => false,
                'next_tier' => null,
                'transition_date' => null,
            ];
        }

        $currentTier = RetentionTier::from($report['retention_tier']);
        $generatedAt = new \DateTimeImmutable($report['generated_at']);
        $now = new \DateTimeImmutable();

        $nextTier = $currentTier->nextTier();
        $durationDays = $currentTier->durationDays();

        if ($durationDays === null || $nextTier === null) {
            // Terminal tier
            return [
                'current_tier' => $currentTier->value,
                'should_transition' => false,
                'next_tier' => null,
                'transition_date' => null,
            ];
        }

        $transitionDate = $generatedAt->modify("+{$durationDays} days");
        $shouldTransition = $now >= $transitionDate;

        return [
            'current_tier' => $currentTier->value,
            'should_transition' => $shouldTransition,
            'next_tier' => $nextTier->value,
            'transition_date' => $transitionDate,
        ];
    }
}
