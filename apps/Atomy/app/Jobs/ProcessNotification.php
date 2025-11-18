<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NotificationQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nexus\Notifier\Contracts\NotificationChannelInterface;
use Nexus\Notifier\ValueObjects\DeliveryStatus;
use Psr\Log\LoggerInterface;

final class ProcessNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $queueId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LoggerInterface $logger): void
    {
        $queueItem = NotificationQueue::find($this->queueId);

        if (!$queueItem) {
            $logger->warning('Notification queue item not found', ['queue_id' => $this->queueId]);
            return;
        }

        // Check if already processed
        if ($queueItem->status !== 'pending') {
            $logger->info('Notification already processed', [
                'queue_id' => $this->queueId,
                'status' => $queueItem->status,
            ]);
            return;
        }

        // Mark as processing
        $queueItem->update([
            'status' => 'processing',
            'attempts' => $queueItem->attempts + 1,
            'last_attempted_at' => now(),
        ]);

        try {
            // Resolve channel
            $channel = $this->resolveChannel($queueItem->channel);

            if (!$channel->isAvailable()) {
                throw new \RuntimeException("Channel {$queueItem->channel} is not available");
            }

            // Send notification
            $externalId = $channel->send(
                $queueItem->recipient_id,
                $queueItem->content
            );

            // Check delivery status
            $status = $channel->getDeliveryStatus($externalId);

            // Update queue item
            $queueItem->update([
                'status' => match ($status) {
                    DeliveryStatus::Delivered => 'sent',
                    DeliveryStatus::Failed => 'failed',
                    DeliveryStatus::Bounced => 'failed',
                    default => 'pending',
                },
                'external_id' => $externalId,
                'sent_at' => $status === DeliveryStatus::Delivered ? now() : null,
                'error_message' => $status === DeliveryStatus::Failed ? 'Delivery failed' : null,
            ]);

            $logger->info('Notification processed successfully', [
                'queue_id' => $this->queueId,
                'channel' => $queueItem->channel,
                'status' => $status->value,
                'external_id' => $externalId,
            ]);

        } catch (\Throwable $e) {
            $queueItem->update([
                'status' => $queueItem->attempts >= $this->tries ? 'failed' : 'pending',
                'error_message' => $e->getMessage(),
            ]);

            $logger->error('Failed to process notification', [
                'queue_id' => $this->queueId,
                'attempt' => $queueItem->attempts,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry if attempts remain
            if ($queueItem->attempts < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Resolve the notification channel.
     */
    private function resolveChannel(string $channelName): NotificationChannelInterface
    {
        return match ($channelName) {
            'email' => app(\Nexus\Notifier\Contracts\EmailChannelInterface::class),
            'sms' => app(\Nexus\Notifier\Contracts\SmsChannelInterface::class),
            'push' => app(\Nexus\Notifier\Contracts\PushChannelInterface::class),
            'in_app' => app(\Nexus\Notifier\Contracts\InAppChannelInterface::class),
            default => throw new \InvalidArgumentException("Unknown channel: {$channelName}"),
        };
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        // Exponential backoff: 10s, 30s, 60s
        return [10, 30, 60];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception, LoggerInterface $logger): void
    {
        $logger->error('Notification job failed permanently', [
            'queue_id' => $this->queueId,
            'error' => $exception->getMessage(),
        ]);

        $queueItem = NotificationQueue::find($this->queueId);
        if ($queueItem) {
            $queueItem->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
