<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Core;

use Nexus\Monitoring\Contracts\MetricRetentionInterface;

/**
 * TimeBasedRetentionPolicy
 *
 * Simple time-based retention policy.
 * Retains metrics for a fixed duration.
 *
 * @package Nexus\Monitoring\Core
 */
final readonly class TimeBasedRetentionPolicy implements MetricRetentionInterface
{
    public function __construct(
        private int $retentionPeriodSeconds
    ) {
        if ($retentionPeriodSeconds <= 0) {
            throw new \InvalidArgumentException('Retention period must be positive');
        }
    }

    /**
     * Create policy with retention in days.
     *
     * @param int $days
     * @return self
     */
    public static function days(int $days): self
    {
        return new self($days * 86400);
    }

    /**
     * Create policy with retention in hours.
     *
     * @param int $hours
     * @return self
     */
    public static function hours(int $hours): self
    {
        return new self($hours * 3600);
    }

    /**
     * {@inheritdoc}
     */
    public function getRetentionPeriod(): int
    {
        return $this->retentionPeriodSeconds;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldRetain(string $metricKey, int $timestamp): bool
    {
        $cutoffTime = time() - $this->retentionPeriodSeconds;
        return $timestamp >= $cutoffTime;
    }
}
