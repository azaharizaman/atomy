<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

/**
 * SLO Configuration Interface
 *
 * Contract for retrieving Service Level Objective thresholds by operation.
 *
 * @package Nexus\Telemetry\Contracts
 */
interface SLOConfigurationInterface
{
    /**
     * Get SLO threshold in milliseconds for a specific operation.
     *
     * @param string $operation Operation name
     * @return float|null Threshold in milliseconds, or null if not configured
     */
    public function getThreshold(string $operation): ?float;
}
