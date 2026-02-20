<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

/**
 * Notification Provider Interface
 * 
 * Provides access to notification services from the Notifier package.
 * This interface abstracts the Notifier package integration.
 * 
 * @package Nexus\CRMOperations\Contracts
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
interface NotificationProviderInterface
{
    /**
     * Send notification to a user
     * 
     * @param string $userId User to notify
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     */
    public function notify(
        string $userId,
        string $subject,
        string $message,
        array $context = []
    ): void;

    /**
     * Send notification to multiple users
     * 
     * @param string[] $userIds Users to notify
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     */
    public function notifyMany(
        array $userIds,
        string $subject,
        string $message,
        array $context = []
    ): void;

    /**
     * Send notification to role members
     * 
     * @param string $role Role to notify
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     */
    public function notifyRole(
        string $role,
        string $subject,
        string $message,
        array $context = []
    ): void;

    /**
     * Send escalation notification
     * 
     * @param string $escalationLevel Escalation level (e.g., 'manager', 'director')
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     */
    public function escalate(
        string $escalationLevel,
        string $subject,
        string $message,
        array $context = []
    ): void;

    /**
     * Send reminder notification
     * 
     * @param string $userId User to remind
     * @param string $type Reminder type
     * @param array<string, mixed> $context Additional context
     */
    public function remind(
        string $userId,
        string $type,
        array $context = []
    ): void;

    /**
     * Check if user has unread notifications
     */
    public function hasUnread(string $userId): bool;

    /**
     * Get unread notification count for user
     */
    public function getUnreadCount(string $userId): int;
}
