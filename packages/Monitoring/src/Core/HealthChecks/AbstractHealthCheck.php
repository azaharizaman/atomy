<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Core\HealthChecks;

use Nexus\Monitoring\Contracts\HealthCheckInterface;
use Nexus\Monitoring\ValueObjects\HealthCheckResult;
use Nexus\Monitoring\ValueObjects\HealthStatus;

/**
 * AbstractHealthCheck
 *
 * Base class for implementing health checks with common functionality.
 * Provides template method pattern for consistent check execution.
 *
 * @package Nexus\Monitoring\Core\HealthChecks
 */
abstract class AbstractHealthCheck implements HealthCheckInterface
{
    public function __construct(
        protected readonly string $name,
        protected readonly int $priority = 50,
        protected readonly int $timeout = 5,
        protected readonly ?int $cacheTtl = null
    ) {}

    /**
     * Execute the health check (required by interface).
     * Template method that calls performCheck() and wraps result.
     */
    public function check(): HealthCheckResult
    {
        try {
            return $this->performCheck();
        } catch (\Throwable $e) {
            return new HealthCheckResult(
                checkName: $this->name,
                status: HealthStatus::CRITICAL,
                message: sprintf('Health check failed: %s', $e->getMessage()),
                responseTimeMs: 0.0,
                metadata: [
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                checkedAt: new \DateTimeImmutable()
            );
        }
    }

    /**
     * Backward compatibility alias for check().
     */
    public function execute(): HealthCheckResult
    {
        return $this->check();
    }

    /**
     * Check if this is a critical health check.
     * Checks with priority >= 70 are considered critical.
     */
    public function isCritical(): bool
    {
        return $this->priority >= 70;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getCacheTtl(): ?int
    {
        return $this->cacheTtl;
    }

    /**
     * Perform the actual health check logic.
     * Subclasses must implement this method.
     *
     * @return HealthCheckResult The check result
     */
    abstract protected function performCheck(): HealthCheckResult;

    /**
     * Helper method to create a healthy result.
     */
    protected function healthy(string $message = 'OK', array $metadata = [], float $responseTimeMs = 0.0): HealthCheckResult
    {
        return new HealthCheckResult(
            checkName: $this->name,
            status: HealthStatus::HEALTHY,
            message: $message,
            responseTimeMs: $responseTimeMs,
            metadata: $metadata,
            checkedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Helper method to create a warning result.
     */
    protected function warning(string $message, array $metadata = [], float $responseTimeMs = 0.0): HealthCheckResult
    {
        return new HealthCheckResult(
            checkName: $this->name,
            status: HealthStatus::WARNING,
            message: $message,
            responseTimeMs: $responseTimeMs,
            metadata: $metadata,
            checkedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Helper method to create a degraded result.
     */
    protected function degraded(string $message, array $metadata = [], float $responseTimeMs = 0.0): HealthCheckResult
    {
        return new HealthCheckResult(
            checkName: $this->name,
            status: HealthStatus::DEGRADED,
            message: $message,
            responseTimeMs: $responseTimeMs,
            metadata: $metadata,
            checkedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Helper method to create a critical result.
     */
    protected function critical(string $message, array $metadata = [], float $responseTimeMs = 0.0): HealthCheckResult
    {
        return new HealthCheckResult(
            checkName: $this->name,
            status: HealthStatus::CRITICAL,
            message: $message,
            responseTimeMs: $responseTimeMs,
            metadata: $metadata,
            checkedAt: new \DateTimeImmutable()
        );
    }

    /**
     * Helper method to create an offline result.
     */
    protected function offline(string $message, array $metadata = [], float $responseTimeMs = 0.0): HealthCheckResult
    {
        return new HealthCheckResult(
            checkName: $this->name,
            status: HealthStatus::OFFLINE,
            message: $message,
            responseTimeMs: $responseTimeMs,
            metadata: $metadata,
            checkedAt: new \DateTimeImmutable()
        );
    }
}
