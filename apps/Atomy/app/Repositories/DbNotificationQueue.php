<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\NotificationQueue as NotificationQueueModel;
use DateTimeImmutable;
use Nexus\Notifier\Contracts\NotificationQueueInterface;

final readonly class DbNotificationQueue implements NotificationQueueInterface
{
    public function enqueue(array $notificationData, ?DateTimeImmutable $scheduledAt = null): string
    {
        $queue = NotificationQueueModel::create([
            'notification_id' => $notificationData['notification_id'],
            'recipient_id' => $notificationData['recipient_id'],
            'channel' => $notificationData['channel'],
            'priority' => $notificationData['priority'],
            'payload' => $notificationData,
            'scheduled_at' => $scheduledAt?->format('Y-m-d H:i:s'),
            'status' => 'pending',
        ]);

        return $queue->id;
    }

    public function dequeue(): ?array
    {
        $item = NotificationQueueModel::where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->first();

        if ($item) {
            $item->update(['status' => 'processing', 'processed_at' => now()]);
            return $item->payload;
        }

        return null;
    }

    public function getPending(int $limit = 100): array
    {
        return NotificationQueueModel::where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function retry(string $notificationId): bool
    {
        $item = NotificationQueueModel::where('notification_id', $notificationId)
            ->where('status', 'failed')
            ->first();

        if ($item) {
            $item->update([
                'status' => 'pending',
                'attempts' => $item->attempts + 1,
            ]);
            return true;
        }

        return false;
    }

    public function remove(string $notificationId): bool
    {
        return NotificationQueueModel::where('notification_id', $notificationId)
            ->delete() > 0;
    }

    public function size(): int
    {
        return NotificationQueueModel::where('status', 'pending')->count();
    }
}
