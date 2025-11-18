<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\NotificationHistory;
use DateTimeImmutable;
use Nexus\Notifier\Contracts\NotificationHistoryRepositoryInterface;
use Nexus\Notifier\ValueObjects\DeliveryStatus;

final readonly class DbNotificationHistoryRepository implements NotificationHistoryRepositoryInterface
{
    public function log(array $logData): string
    {
        $history = NotificationHistory::create($logData);

        return $history->id;
    }

    public function findByRecipient(string $recipientId, int $limit = 100): array
    {
        return NotificationHistory::where('recipient_id', $recipientId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function findByStatus(DeliveryStatus $status, int $limit = 100): array
    {
        return NotificationHistory::where('status', $status->value)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function updateStatus(string $notificationId, DeliveryStatus $status, array $metadata = []): bool
    {
        $data = ['status' => $status->value];

        if (!empty($metadata)) {
            $data['metadata'] = $metadata;
        }

        if ($status === DeliveryStatus::Sent) {
            $data['sent_at'] = now();
        } elseif ($status === DeliveryStatus::Delivered) {
            $data['delivered_at'] = now();
        } elseif ($status->isFailure()) {
            $data['failed_at'] = now();
        }

        return NotificationHistory::where('notification_id', $notificationId)
            ->update($data) > 0;
    }

    public function getRetryable(int $maxRetries = 3): array
    {
        return NotificationHistory::where('status', DeliveryStatus::Failed->value)
            ->where('retry_count', '<', $maxRetries)
            ->whereNotNull('scheduled_at')
            ->orderBy('scheduled_at')
            ->get()
            ->toArray();
    }

    public function purge(DateTimeImmutable $olderThan): int
    {
        return NotificationHistory::where('created_at', '<', $olderThan->format('Y-m-d H:i:s'))
            ->delete();
    }
}
