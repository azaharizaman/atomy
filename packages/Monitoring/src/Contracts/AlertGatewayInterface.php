<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Contracts;

use Nexus\Monitoring\ValueObjects\AlertContext;
use Nexus\Monitoring\ValueObjects\AlertSeverity;
use Throwable;

/**
 * Alert Gateway Interface
 *
 * Centralized interface for logging exceptions, audit alerts, and critical notifications.
 * Routes alerts to appropriate dispatchers based on severity.
 *
 * @package Nexus\Monitoring\Contracts
 */
interface AlertGatewayInterface
{
    /**
     * Log an exception with specified severity and context.
     *
     * @param Throwable $exception The exception to log
     * @param AlertSeverity $severity Alert severity level
     * @param array<string, mixed> $context Additional context data
     * @return void
     */
    public function logException(
        Throwable $exception,
        AlertSeverity $severity,
        array $context = []
    ): void;

    /**
     * Log an audit alert for high-risk events.
     * Used by AuditLogger for critical security/compliance events.
     *
     * @param AlertSeverity $severity Alert severity level
     * @param string $message Human-readable alert message
     * @param array<string, mixed> $context Additional context data
     * @return void
     */
    public function logAuditAlert(
        AlertSeverity $severity,
        string $message,
        array $context = []
    ): void;

    /**
     * Trigger immediate critical notification.
     * Used for system failures requiring immediate attention.
     *
     * @param string $message Critical failure message
     * @param array<string, mixed> $context Additional context data
     * @return void
     */
    public function notifyCritical(string $message, array $context = []): void;
}
