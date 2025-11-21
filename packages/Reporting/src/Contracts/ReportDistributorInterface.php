<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Reporting\ValueObjects\DistributionResult;
use Nexus\Reporting\ValueObjects\ReportResult;

/**
 * Handles multi-channel distribution of generated reports via Nexus\Notifier.
 */
interface ReportDistributorInterface
{
    /**
     * Distribute a generated report to recipients.
     *
     * Sends the report via configured channels (email with PDF attachment, SMS alert, etc.)
     * and tracks delivery status in reports_distribution_log.
     *
     * Implements REL-REP-0305: On failure, preserves PDF for manual retry.
     *
     * @param ReportResult $result The generated report
     * @param array<\Nexus\Notifier\Contracts\NotifiableInterface> $recipients
     * @param array<string, mixed> $options Additional options (e.g., email subject override)
     * @return DistributionResult
     * @throws \Nexus\Reporting\Exceptions\ReportDistributionException
     */
    public function distribute(
        ReportResult $result,
        array $recipients,
        array $options = []
    ): DistributionResult;

    /**
     * Schedule a future distribution of a generated report.
     *
     * @param string $reportGeneratedId The generated report ID
     * @param \DateTimeImmutable $scheduledAt When to distribute
     * @param array<\Nexus\Notifier\Contracts\NotifiableInterface> $recipients
     * @return string The scheduled job ID from Scheduler
     */
    public function scheduleDistribution(
        string $reportGeneratedId,
        \DateTimeImmutable $scheduledAt,
        array $recipients
    ): string;

    /**
     * Track the delivery status of a distributed report.
     *
     * @param string $reportGeneratedId
     * @return array<array{
     *     recipient_id: string,
     *     channel_type: string,
     *     status: string,
     *     delivered_at: ?\DateTimeImmutable,
     *     error: ?string
     * }>
     */
    public function trackDelivery(string $reportGeneratedId): array;

    /**
     * Retry failed distributions for a report.
     *
     * Resends to recipients whose previous delivery failed.
     *
     * @param string $reportGeneratedId
     * @return DistributionResult
     */
    public function retryFailedDistributions(string $reportGeneratedId): DistributionResult;
}
