<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

use Nexus\Telemetry\ValueObjects\HealthCheckResult;

/**
 * Health Check Interface
 *
 * Contract for individual health check implementations.
 *
 * @package Nexus\Telemetry\Contracts
 */
interface HealthCheckInterface
{
    /**
     * Execute the health check and return result.
     *
     * @return HealthCheckResult
     */
    public function check(): HealthCheckResult;

    /**
     * Get unique name of this health check.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if this is a critical health check.
     * Critical checks are prioritized and their failure indicates severe system issues.
     *
     * @return bool
     */
    public function isCritical(): bool;
}
