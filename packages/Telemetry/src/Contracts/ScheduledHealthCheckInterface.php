<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Contracts;

/**
 * Scheduled Health Check Interface
 *
 * Extension of HealthCheckInterface for checks that should run on a schedule.
 *
 * @package Nexus\Telemetry\Contracts
 */
interface ScheduledHealthCheckInterface extends HealthCheckInterface
{
    /**
     * Get cron expression for when this check should execute.
     *
     * @return string Cron expression (e.g., every 5 minutes: "* /5 * * * *" without space)
     */
    public function getSchedule(): string;
}
