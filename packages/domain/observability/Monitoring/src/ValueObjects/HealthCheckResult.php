<?php

declare(strict_types=1);

namespace Nexus\Monitoring\ValueObjects;

use DateTimeImmutable;

/**
 * Health Check Result Value Object
 *
 * Immutable representation of a single health check execution result.
 *
 * @package Nexus\Monitoring\ValueObjects
 */
final readonly class HealthCheckResult
{
    /**
     * @param string $checkName Unique name of the health check
     * @param HealthStatus $status Health status result
     * @param string|null $message Optional descriptive message about the check result
     * @param float $responseTimeMs Time taken to execute the check in milliseconds
     * @param array<string, mixed> $metadata Additional context about the check
     * @param DateTimeImmutable $checkedAt When the check was performed
     */
    public function __construct(
        public string $checkName,
        public HealthStatus $status,
        public ?string $message,
        public float $responseTimeMs,
        public array $metadata,
        public DateTimeImmutable $checkedAt,
    ) {
        if ($responseTimeMs < 0) {
            throw new \InvalidArgumentException('Response time cannot be negative');
        }
    }

    /**
     * Check if this health check result indicates healthy status.
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->status->isHealthy();
    }

    /**
     * Check if this health check result is critical or offline.
     *
     * @return bool
     */
    public function isCritical(): bool
    {
        return $this->status->isCritical();
    }

    /**
     * Check if the check was slow (exceeded threshold).
     *
     * @param float $thresholdMs Threshold in milliseconds (default: 1000ms)
     * @return bool
     */
    public function wasSlow(float $thresholdMs = 1000.0): bool
    {
        return $this->responseTimeMs >= $thresholdMs;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'check_name' => $this->checkName,
            'status' => $this->status->value,
            'message' => $this->message,
            'response_time_ms' => $this->responseTimeMs,
            'metadata' => $this->metadata,
            'checked_at' => $this->checkedAt->format('Y-m-d H:i:s.u'),
        ];
    }
}
