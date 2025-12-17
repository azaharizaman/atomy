<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\SOX\SOXPerformanceMetrics;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;

/**
 * Contract for monitoring SOX control performance.
 *
 * Tracks validation latency, failure rates, and provides
 * recommendations for optimization and opt-out eligibility.
 */
interface SOXPerformanceMonitorInterface
{
    /**
     * Record a control validation execution.
     *
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function recordValidation(
        string $tenantId,
        SOXControlPoint $controlPoint,
        bool $passed,
        float $durationMs,
        array $metadata = [],
    ): void;

    /**
     * Record a validation timeout.
     */
    public function recordTimeout(
        string $tenantId,
        SOXControlPoint $controlPoint,
        float $durationMs,
    ): void;

    /**
     * Record a validation error.
     */
    public function recordError(
        string $tenantId,
        SOXControlPoint $controlPoint,
        string $errorMessage,
    ): void;

    /**
     * Get performance metrics for a tenant over a time period.
     */
    public function getMetrics(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): SOXPerformanceMetrics;

    /**
     * Get the P95 latency for a tenant over the last N days.
     */
    public function getP95Latency(string $tenantId, int $days = 30): float;

    /**
     * Check if a tenant is eligible for SOX opt-out (low risk).
     *
     * Returns eligibility assessment with reasons.
     *
     * @return array{eligible: bool, reasons: array<string>, risk_score: float}
     */
    public function assessOptOutEligibility(string $tenantId): array;

    /**
     * Get tenants with performance issues (P95 > threshold).
     *
     * @return array<string, float> Tenant ID => P95 latency
     */
    public function getTenantsWithPerformanceIssues(float $thresholdMs = 200.0): array;

    /**
     * Get tenants recommended for SOX opt-out.
     *
     * @return array<string, array{risk_score: float, reasons: array<string>}>
     */
    public function getOptOutRecommendations(): array;

    /**
     * Clear metrics for a tenant (admin operation).
     */
    public function clearMetrics(string $tenantId): void;
}
