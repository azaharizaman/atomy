<?php

declare(strict_types=1);

namespace Nexus\AuditLogger\Contracts;

/**
 * Interface AuditConfigInterface
 *
 * Configuration contract for audit logging system.
 *
 * @package Nexus\AuditLogger\Contracts
 */
interface AuditConfigInterface
{
    /**
     * Get the default retention period in days
     * Satisfies: BUS-AUD-0147
     *
     * @return int Default 90 days
     */
    public function getDefaultRetentionDays(): int;

    /**
     * Get the default audit level for entities
     * Satisfies: BUS-AUD-0149
     *
     * @param string $entityType
     * @return int 1=Low, 2=Medium, 3=High, 4=Critical
     */
    public function getDefaultLevelForEntity(string $entityType): int;

    /**
     * Check if asynchronous logging is enabled
     * Satisfies: FUN-AUD-0196
     *
     * @return bool
     */
    public function isAsyncLoggingEnabled(): bool;

    /**
     * Get the queue name for async logging
     *
     * @return string
     */
    public function getQueueName(): string;

    /**
     * Get sensitive field patterns to mask
     * Satisfies: FUN-AUD-0192
     *
     * @return array Regular expressions or field names
     */
    public function getSensitiveFieldPatterns(): array;

    /**
     * Get high-value entity types that default to Critical level
     * Satisfies: BUS-AUD-0149
     *
     * @return array Entity type names
     */
    public function getHighValueEntityTypes(): array;

    /**
     * Check if notifications are enabled for critical activities
     * Satisfies: FUN-AUD-0197
     *
     * @return bool
     */
    public function areNotificationsEnabled(): bool;

    /**
     * Get notification recipients for critical activities
     *
     * @return array Email addresses or user IDs
     */
    public function getNotificationRecipients(): array;
}
