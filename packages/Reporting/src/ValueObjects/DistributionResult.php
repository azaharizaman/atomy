<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

/**
 * Immutable value object representing the result of a report distribution.
 */
final readonly class DistributionResult
{
    /**
     * @param string $reportId The generated report ID
     * @param array<string> $notificationIds Notification IDs from Notifier package
     * @param int $successCount Number of successful deliveries
     * @param int $failureCount Number of failed deliveries
     * @param array<array{recipient_id: string, error: string}> $errors Detailed error information
     * @param \DateTimeImmutable $distributedAt When the distribution was attempted
     */
    public function __construct(
        public string $reportId,
        public array $notificationIds,
        public int $successCount,
        public int $failureCount,
        public array $errors,
        public \DateTimeImmutable $distributedAt,
    ) {
        if ($this->successCount < 0) {
            throw new \InvalidArgumentException('Success count cannot be negative');
        }

        if ($this->failureCount < 0) {
            throw new \InvalidArgumentException('Failure count cannot be negative');
        }

        if ($this->successCount + $this->failureCount === 0) {
            throw new \InvalidArgumentException('Distribution must have at least one recipient');
        }
    }

    /**
     * Create a result from distribution attempt.
     *
     * @param string $reportId
     * @param array<string> $notificationIds
     * @param array<array{recipient_id: string, success: bool, error?: string}> $deliveries
     * @param \DateTimeImmutable $distributedAt
     */
    public static function fromDeliveries(
        string $reportId,
        array $notificationIds,
        array $deliveries,
        \DateTimeImmutable $distributedAt
    ): self {
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($deliveries as $delivery) {
            if ($delivery['success']) {
                $successCount++;
            } else {
                $failureCount++;
                $errors[] = [
                    'recipient_id' => $delivery['recipient_id'],
                    'error' => $delivery['error'] ?? 'Unknown error',
                ];
            }
        }

        return new self(
            reportId: $reportId,
            notificationIds: $notificationIds,
            successCount: $successCount,
            failureCount: $failureCount,
            errors: $errors,
            distributedAt: $distributedAt
        );
    }

    /**
     * Check if all distributions succeeded.
     */
    public function isFullySuccessful(): bool
    {
        return $this->failureCount === 0;
    }

    /**
     * Check if any distributions succeeded.
     */
    public function hasAnySuccess(): bool
    {
        return $this->successCount > 0;
    }

    /**
     * Get the total number of recipients.
     */
    public function getTotalRecipients(): int
    {
        return $this->successCount + $this->failureCount;
    }

    /**
     * Get success rate as a percentage.
     */
    public function getSuccessRate(): float
    {
        $total = $this->getTotalRecipients();
        if ($total === 0) {
            return 0.0;
        }

        return ($this->successCount / $total) * 100;
    }

    /**
     * Convert to array for logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'report_id' => $this->reportId,
            'notification_ids' => $this->notificationIds,
            'success_count' => $this->successCount,
            'failure_count' => $this->failureCount,
            'errors' => $this->errors,
            'distributed_at' => $this->distributedAt->format('Y-m-d H:i:s'),
            'success_rate' => $this->getSuccessRate(),
        ];
    }
}
