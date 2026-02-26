<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

use Nexus\Telemetry\ValueObjects\HealthCheckResult;

/**
 * Health Checker Interface
 *
 * Manages registration and execution of health checks for system dependencies.
 *
 * @package Nexus\Telemetry\Contracts
 */
interface HealthCheckerInterface
{
    /**
     * Register a health check for execution.
     *
     * @param HealthCheckInterface $check Health check instance
     * @return void
     */
    public function registerCheck(HealthCheckInterface $check): void;

    /**
     * Run all registered health checks and return results.
     *
     * @return array<HealthCheckResult> Array of check results
     */
    public function runChecks(): array;

    /**
     * Get a specific registered check by name.
     *
     * @param string $name Check name
     * @return HealthCheckInterface|null
     */
    public function getCheckByName(string $name): ?HealthCheckInterface;
}
