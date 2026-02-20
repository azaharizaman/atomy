<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Tests\Mocks;

use Nexus\CRMOperations\Contracts\NotificationProviderInterface;

/**
 * Mock implementation of NotificationProviderInterface for testing
 * 
 * @package Nexus\CRMOperations\Tests\Mocks
 */
final class MockNotificationProvider implements NotificationProviderInterface
{
    private array $notifications = [];

    public function notify(
        string $userId,
        string $subject,
        string $message,
        array $context = []
    ): void {
        $this->notifications[] = [
            'type' => 'notify',
            'user_id' => $userId,
            'subject' => $subject,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function notifyMany(
        array $userIds,
        string $subject,
        string $message,
        array $context = []
    ): void {
        $this->notifications[] = [
            'type' => 'notify_many',
            'user_ids' => $userIds,
            'subject' => $subject,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function notifyRole(
        string $role,
        string $subject,
        string $message,
        array $context = []
    ): void {
        $this->notifications[] = [
            'type' => 'notify_role',
            'role' => $role,
            'subject' => $subject,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function escalate(
        string $escalationLevel,
        string $subject,
        string $message,
        array $context = []
    ): void {
        $this->notifications[] = [
            'type' => 'escalate',
            'level' => $escalationLevel,
            'subject' => $subject,
            'message' => $message,
            'context' => $context,
        ];
    }

    public function remind(
        string $userId,
        string $type,
        array $context = []
    ): void {
        $this->notifications[] = [
            'type' => 'remind',
            'user_id' => $userId,
            'reminder_type' => $type,
            'context' => $context,
        ];
    }

    public function hasUnread(string $userId): bool
    {
        return false;
    }

    public function getUnreadCount(string $userId): int
    {
        return 0;
    }

    // Helper methods for testing
    public function getNotificationCount(): int
    {
        return count($this->notifications);
    }

    public function getNotifications(): array
    {
        return $this->notifications;
    }
}
