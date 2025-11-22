<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Contracts;

/**
 * Scheduled Health Check Interface
 *
 * Extension of HealthCheckInterface for checks that should run on a schedule.
 *
 * @package Nexus\Monitoring\Contracts
 */
interface ScheduledHealthCheckInterface extends HealthCheckInterface
{
    /**
     * Get cron expression for when this check should execute.
     *
     * @return string Cron expression (e.g., '*/5 * * * *' for every 5 minutes)
     */
    public function getSchedule(): string;
}
