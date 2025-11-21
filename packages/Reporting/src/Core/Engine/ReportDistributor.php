<?php

declare(strict_types=1);

namespace Nexus\Reporting\Core\Engine;

use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Notifier\ValueObjects\ChannelType;
use Nexus\Notifier\ValueObjects\Priority;
use Nexus\Reporting\Contracts\ReportDistributorInterface;
use Nexus\Reporting\Contracts\ReportRepositoryInterface;
use Nexus\Reporting\Exceptions\ReportDistributionException;
use Nexus\Reporting\Exceptions\ReportNotFoundException;
use Nexus\Reporting\ValueObjects\DistributionResult;
use Nexus\Reporting\ValueObjects\DistributionStatus;
use Nexus\Reporting\ValueObjects\ReportResult;
use Nexus\Scheduler\Contracts\ScheduleManagerInterface;
use Nexus\Scheduler\ValueObjects\JobType;
use Nexus\Scheduler\ValueObjects\ScheduleDefinition;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Psr\Log\LoggerInterface;

/**
 * Core engine for distributing generated reports via multiple channels.
 *
 * Implements REL-REP-0305: Preserves PDF on distribution failure for manual retry.
 */
final readonly class ReportDistributor implements ReportDistributorInterface
{
    public function __construct(
        private NotificationManagerInterface $notificationManager,
        private ReportRepositoryInterface $reportRepository,
        private ScheduleManagerInterface $scheduleManager,
        private StorageDriverInterface $storage,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function distribute(
        ReportResult $result,
        array $recipients,
        array $options = []
    ): DistributionResult {
        if (!$result->isSuccessful()) {
            throw new ReportDistributionException(
                "Cannot distribute failed report: {$result->reportId}"
            );
        }

        // REL-REP-0305: Verify file exists
        if (!$this->storage->exists($result->filePath)) {
            throw ReportDistributionException::missingReportFile($result->filePath);
        }

        $this->logger->info('Starting report distribution', [
            'report_id' => $result->reportId,
            'recipient_count' => count($recipients),
        ]);

        $deliveries = [];
        $notificationIds = [];

        foreach ($recipients as $recipient) {
            if (!$recipient instanceof NotifiableInterface) {
                $deliveries[] = [
                    'recipient_id' => 'unknown',
                    'success' => false,
                    'error' => 'Invalid recipient type',
                ];
                continue;
            }

            try {
                // Create notification with PDF attachment
                $notification = $this->buildNotification($result, $options);

                // Send via Notifier
                $notificationId = $this->notificationManager->send(
                    $recipient,
                    $notification,
                    [ChannelType::EMAIL] // Primary channel for report delivery
                );

                $notificationIds[] = $notificationId;

                // Store distribution log
                $this->reportRepository->storeDistributionLog([
                    'id' => $this->generateLogId(),
                    'report_generated_id' => $result->reportId,
                    'recipient_id' => $this->getRecipientId($recipient),
                    'notification_id' => $notificationId,
                    'channel_type' => ChannelType::EMAIL->value,
                    'status' => DistributionStatus::SENT->value,
                    'delivered_at' => null, // Updated by webhook later
                    'error' => null,
                    'created_at' => new \DateTimeImmutable(),
                ]);

                $deliveries[] = [
                    'recipient_id' => $this->getRecipientId($recipient),
                    'success' => true,
                ];

            } catch (\Throwable $e) {
                $this->logger->error('Failed to distribute to recipient', [
                    'report_id' => $result->reportId,
                    'recipient_id' => $this->getRecipientId($recipient),
                    'error' => $e->getMessage(),
                ]);

                // Store failure log
                $this->reportRepository->storeDistributionLog([
                    'id' => $this->generateLogId(),
                    'report_generated_id' => $result->reportId,
                    'recipient_id' => $this->getRecipientId($recipient),
                    'notification_id' => null,
                    'channel_type' => ChannelType::EMAIL->value,
                    'status' => DistributionStatus::FAILED->value,
                    'delivered_at' => null,
                    'error' => $e->getMessage(),
                    'created_at' => new \DateTimeImmutable(),
                ]);

                $deliveries[] = [
                    'recipient_id' => $this->getRecipientId($recipient),
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                // REL-REP-0305: Continue to next recipient, PDF is preserved
            }
        }

        $distributionResult = DistributionResult::fromDeliveries(
            $result->reportId,
            $notificationIds,
            $deliveries,
            new \DateTimeImmutable()
        );

        // Audit log
        $this->auditLogger->log(
            logName: 'report_distributed',
            description: "Report distributed to {$distributionResult->getTotalRecipients()} recipients",
            subjectType: 'Report',
            subjectId: $result->reportId,
            level: 2, // Medium
            properties: [
                'success_count' => $distributionResult->successCount,
                'failure_count' => $distributionResult->failureCount,
                'success_rate' => $distributionResult->getSuccessRate(),
            ]
        );

        $this->logger->info('Report distribution completed', [
            'report_id' => $result->reportId,
            'success_count' => $distributionResult->successCount,
            'failure_count' => $distributionResult->failureCount,
        ]);

        return $distributionResult;
    }

    /**
     * {@inheritdoc}
     */
    public function scheduleDistribution(
        string $reportGeneratedId,
        \DateTimeImmutable $scheduledAt,
        array $recipients
    ): string {
        $this->logger->info('Scheduling future distribution', [
            'report_id' => $reportGeneratedId,
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
        ]);

        $job = $this->scheduleManager->schedule(
            new ScheduleDefinition(
                jobType: JobType::from('distribute_report'), // Assuming this exists
                targetId: $reportGeneratedId,
                runAt: $scheduledAt,
                payload: [
                    'recipient_ids' => array_map(
                        fn($r) => $this->getRecipientId($r),
                        $recipients
                    ),
                ],
            )
        );

        return $job->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function trackDelivery(string $reportGeneratedId): array
    {
        $logs = $this->reportRepository->getDistributionLogs($reportGeneratedId);

        return array_map(function ($log) {
            return [
                'recipient_id' => $log['recipient_id'],
                'channel_type' => $log['channel_type'],
                'status' => $log['status'],
                'delivered_at' => isset($log['delivered_at'])
                    ? new \DateTimeImmutable($log['delivered_at'])
                    : null,
                'error' => $log['error'],
            ];
        }, $logs);
    }

    /**
     * {@inheritdoc}
     */
    public function retryFailedDistributions(string $reportGeneratedId): DistributionResult
    {
        $this->logger->info('Retrying failed distributions', [
            'report_id' => $reportGeneratedId,
        ]);

        // Get failed distribution logs
        $logs = $this->reportRepository->getDistributionLogs($reportGeneratedId);
        $failedLogs = array_filter(
            $logs,
            fn($log) => $log['status'] === DistributionStatus::FAILED->value
        );

        if (empty($failedLogs)) {
            $this->logger->info('No failed distributions to retry', [
                'report_id' => $reportGeneratedId,
            ]);

            return new DistributionResult(
                reportId: $reportGeneratedId,
                notificationIds: [],
                successCount: 0,
                failureCount: 0,
                errors: [],
                distributedAt: new \DateTimeImmutable()
            );
        }

        // Get the generated report
        $generatedReport = $this->reportRepository->findGeneratedReportById($reportGeneratedId);
        if (!$generatedReport) {
            throw ReportNotFoundException::forGeneratedReport($reportGeneratedId);
        }

        // Build ReportResult from stored data
        $result = new ReportResult(
            reportId: $generatedReport['id'],
            format: \Nexus\Reporting\ValueObjects\ReportFormat::from($generatedReport['format']),
            filePath: $generatedReport['file_path'],
            fileSize: $generatedReport['file_size_bytes'],
            generatedAt: new \DateTimeImmutable($generatedReport['generated_at']),
            durationMs: $generatedReport['duration_ms'],
            isSuccessful: $generatedReport['is_successful'],
        );

        // Attempt to retry each failed distribution
        $notificationIds = [];
        $errors = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($failedLogs as $log) {
            try {
                // Attempt to resolve recipient from log data
                // If recipient info is not available, skip and log error
                if (empty($log['recipient_id'])) {
                    $errors[] = "Missing recipient_id for failed distribution log ID {$log['id']}";
                    $failureCount++;
                    continue;
                }

                // TODO: This should be replaced with a proper RecipientResolverInterface
                // that can hydrate full NotifiableInterface instances from recipient IDs.
                // For now, we use a minimal stub to enable basic retry functionality.
                $recipient = new class($log['recipient_id']) implements NotifiableInterface {
                    public function __construct(private readonly string $id) {}
                    public function getId(): string { return $this->id; }
                    public function getNotificationEmail(): ?string { return null; }
                    public function getNotificationPhone(): ?string { return null; }
                    public function getNotificationDeviceTokens(): array { return []; }
                    public function getNotificationLocale(): ?string { return null; }
                    public function getNotificationTimezone(): ?string { return null; }
                    public function getNotificationIdentifier(): string { return $this->id; }
                };

                // Build notification options from log (if available)
                $options = [
                    'channel' => $log['channel_type'] ?? ChannelType::EMAIL->value,
                    'priority' => Priority::NORMAL->value,
                ];

                $notification = $this->buildNotification($result, $options);
                $notificationId = $this->notificationManager->send(
                    $recipient,
                    $notification,
                    [ChannelType::from($options['channel'])]
                );

                $notificationIds[] = $notificationId;
                $successCount++;

                // Update distribution log status to success
                $this->reportRepository->updateDistributionLog($log['id'], [
                    'status' => DistributionStatus::SENT->value,
                    'notification_id' => $notificationId,
                    'error' => null,
                ]);

            } catch (\Throwable $ex) {
                $errors[] = "Failed to retry distribution for log ID {$log['id']}: " . $ex->getMessage();
                $failureCount++;

                // Update distribution log to reflect retry failure
                $this->reportRepository->updateDistributionLog($log['id'], [
                    'status' => DistributionStatus::FAILED->value,
                    'error' => $ex->getMessage(),
                ]);
            }
        }

        $this->logger->info('Retry distribution completed', [
            'report_id' => $reportGeneratedId,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => $errors,
        ]);

        return new DistributionResult(
            reportId: $reportGeneratedId,
            notificationIds: $notificationIds,
            successCount: $successCount,
            failureCount: $failureCount,
            errors: $errors,
            distributedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Build notification for report delivery.
     *
     * @param array<string, mixed> $options
     */
    private function buildNotification(
        ReportResult $result,
        array $options
    ): NotificationInterface {
        // This is a placeholder - actual implementation would use a concrete notification class
        return new class($result, $options) implements NotificationInterface {
            public function __construct(
                private readonly ReportResult $result,
                private readonly array $options
            ) {}

            public function toEmail(): array
            {
                return [
                    'subject' => $this->options['subject'] ?? 'Your Report is Ready',
                    'body' => $this->options['body'] ?? 'Please find your report attached.',
                    'attachments' => [
                        [
                            'path' => $this->result->filePath,
                            'name' => 'report.' . $this->result->format->extension(),
                            'mime' => $this->result->format->mimeType(),
                        ],
                    ],
                ];
            }

            public function toSms(): string
            {
                return 'Your report is ready. Check your email.';
            }

            public function toPush(): array
            {
                return [
                    'title' => 'Report Generated',
                    'body' => 'Your report is ready for download.',
                ];
            }

            public function toInApp(): array
            {
                return [
                    'title' => 'Report Ready',
                    'body' => 'Your report has been generated successfully.',
                    'action_url' => '/reports/' . $this->result->reportId,
                ];
            }

            public function getPriority(): Priority
            {
                return Priority::NORMAL;
            }

            public function getCategory(): \Nexus\Notifier\ValueObjects\Category
            {
                return \Nexus\Notifier\ValueObjects\Category::TRANSACTIONAL;
            }
        };
    }

    /**
     * Extract recipient ID from NotifiableInterface.
     */
    private function getRecipientId(NotifiableInterface $recipient): string
    {
        // Placeholder - actual implementation depends on Notifiable structure
        return method_exists($recipient, 'getId') ? $recipient->getId() : 'unknown';
    }

    /**
     * Generate a unique ULID for distribution log entries.
     *
     * Framework-agnostic implementation using symfony/uid package.
     */
    private function generateLogId(): string
    {
        return (string) new \Symfony\Component\Uid\Ulid();
    }
}
