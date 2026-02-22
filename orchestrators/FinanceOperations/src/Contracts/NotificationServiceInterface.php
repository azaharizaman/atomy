<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

/**
 * Interface for notification services.
 *
 * Defines the contract for sending notifications and alerts
 * within the FinanceOperations orchestrator.
 */
interface NotificationServiceInterface
{
    /**
     * Send a budget threshold alert.
     *
     * @param array<string, mixed> $data Alert data containing:
     *                                   - type: string
     *                                   - budget_id: string
     *                                   - severity: string
     *                                   - threshold: string
     *                                   - actual: string
     * @return void
     */
    public function sendAlert(array $data): void;

    /**
     * Notify that reconciliation is completed.
     *
     * @param array<string, mixed> $data Notification data containing:
     *                                   - is_reconciled: bool
     *                                   - discrepancy_count: int
     * @return void
     */
    public function notifyReconciliationCompleted(array $data): void;
}
