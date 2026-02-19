<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Services;

use Nexus\CRMOperations\Contracts\NotificationProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Real-Time Notifier Service
 * 
 * Provides real-time notification capabilities including WebSocket and push notifications.
 * Coordinates with the Notifier package for delivery.
 * 
 * @package Nexus\CRMOperations\Services
 * @author Azahari Zaman <azaharizaman@gmail.com>
 */
final readonly class RealTimeNotifier
{
    /**
     * @param NotificationProviderInterface $notificationProvider Notification service
     * @param object|null $webSocketService WebSocket service for real-time delivery
     * @param object|null $pushService Push notification service
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        private NotificationProviderInterface $notificationProvider,
        private ?object $webSocketService = null,
        private ?object $pushService = null,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Send real-time notification via WebSocket
     * 
     * @param string $userId User to notify
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     * @param string[] $channels Notification channels (websocket, push, email)
     */
    public function notify(
        string $userId,
        string $subject,
        string $message,
        array $context = [],
        array $channels = ['websocket']
    ): void {
        $this->logger?->info('Sending real-time notification', [
            'user_id' => $userId,
            'subject' => $subject,
            'channels' => $channels,
        ]);

        // Send via WebSocket if configured
        if (in_array('websocket', $channels) && $this->webSocketService !== null) {
            $this->sendWebSocketNotification($userId, $subject, $message, $context);
        }

        // Send via Push notification if configured
        if (in_array('push', $channels) && $this->pushService !== null) {
            $this->sendPushNotification($userId, $subject, $message, $context);
        }

        // Also send traditional notification if email channel requested
        if (in_array('email', $channels)) {
            $this->notificationProvider->notify($userId, $subject, $message, $context);
        }
    }

    /**
     * Send alert notification (high priority)
     * 
     * @param string $userId User to alert
     * @param string $alertType Type of alert (deal_update, sla_warning, approval_required, etc.)
     * @param string $title Alert title
     * @param string $description Alert description
     * @param string $severity Alert severity (info, warning, critical)
     * @param array<string, mixed> $context Additional context
     */
    public function sendAlert(
        string $userId,
        string $alertType,
        string $title,
        string $description,
        string $severity = 'info',
        array $context = []
    ): void {
        $this->logger?->info('Sending real-time alert', [
            'user_id' => $userId,
            'alert_type' => $alertType,
            'severity' => $severity,
        ]);

        $alertContext = array_merge($context, [
            'alert_type' => $alertType,
            'severity' => $severity,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        // Send high-priority WebSocket notification
        if ($this->webSocketService !== null) {
            $this->sendWebSocketAlert($userId, $alertType, $title, $description, $severity, $alertContext);
        }

        // Send push notification for critical alerts
        if ($this->pushService !== null && $severity === 'critical') {
            $this->sendPushNotification($userId, $title, $description, $alertContext);
        }

        // Always send email for critical alerts
        if ($severity === 'critical') {
            $this->notificationProvider->notify($userId, $title, $description, $alertContext);
        }
    }

    /**
     * Coordinate push notifications across devices
     * 
     * @param string $userId User to notify
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     * @param array<string> $deviceTypes Target device types (ios, android, web)
     */
    public function sendPushNotification(
        string $userId,
        string $subject,
        string $message,
        array $context = [],
        array $deviceTypes = ['ios', 'android', 'web']
    ): void {
        $this->logger?->debug('Sending push notification', [
            'user_id' => $userId,
            'device_types' => $deviceTypes,
        ]);

        // In real implementation, this would use the push service to send
        // notifications to specific device types
        // $this->pushService->send($userId, $subject, $message, $context, $deviceTypes);
    }

    /**
     * Send WebSocket notification
     * 
     * @param string $userId User to notify
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     */
    private function sendWebSocketNotification(
        string $userId,
        string $subject,
        string $message,
        array $context
    ): void {
        $this->logger?->debug('Sending WebSocket notification', ['user_id' => $userId]);

        // In real implementation, this would use the WebSocket service
        // $this->webSocketService->emit("user:{$userId}", [
        //     'type' => 'notification',
        //     'subject' => $subject,
        //     'message' => $message,
        //     'context' => $context,
        // ]);
    }

    /**
     * Send WebSocket alert (high priority)
     * 
     * @param string $userId User to alert
     * @param string $alertType Type of alert
     * @param string $title Alert title
     * @param string $description Alert description
     * @param string $severity Alert severity
     * @param array<string, mixed> $context Additional context
     */
    private function sendWebSocketAlert(
        string $userId,
        string $alertType,
        string $title,
        string $description,
        string $severity,
        array $context
    ): void {
        $this->logger?->debug('Sending WebSocket alert', [
            'user_id' => $userId,
            'alert_type' => $alertType,
            'severity' => $severity,
        ]);

        // In real implementation, this would send a high-priority WebSocket message
        // $this->webSocketService->emit("user:{$userId}", [
        //     'type' => 'alert',
        //     'alert_type' => $alertType,
        //     'title' => $title,
        //     'description' => $description,
        //     'severity' => $severity,
        //     'context' => $context,
        // ]);
    }

    /**
     * Broadcast notification to multiple users
     * 
     * @param string[] $userIds Users to notify
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     * @param string[] $channels Notification channels
     */
    public function notifyMany(
        array $userIds,
        string $subject,
        string $message,
        array $context = [],
        array $channels = ['websocket']
    ): void {
        $this->logger?->info('Broadcasting notification', [
            'user_count' => count($userIds),
            'channels' => $channels,
        ]);

        foreach ($userIds as $userId) {
            $this->notify($userId, $subject, $message, $context, $channels);
        }
    }

    /**
     * Send notification to a role
     * 
     * @param string $role Role to notify
     * @param string $subject Notification subject
     * @param string $message Notification message
     * @param array<string, mixed> $context Additional context
     * @param string[] $channels Notification channels
     */
    public function notifyRole(
        string $role,
        string $subject,
        string $message,
        array $context = [],
        array $channels = ['websocket']
    ): void {
        $this->logger?->info('Notifying role', [
            'role' => $role,
            'channels' => $channels,
        ]);

        // In real implementation, get users by role and notify
        // For now, use the notification provider
        $this->notificationProvider->notifyRole($role, $subject, $message, $context);
    }
}
