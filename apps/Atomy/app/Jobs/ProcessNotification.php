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

            // Reconstruct recipient and notification from payload
            $payload = $queueItem->payload;
            $recipient = $this->createRecipientFromPayload($payload);
            $notification = $this->createNotificationFromPayload($payload);

            // Send notification
            $externalId = $channel->send(
                $recipient,
                $notification,
                $payload['content']
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
                'processed_at' => now(),
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
        // Get all channel implementations from the container
        // They should be registered with a key like 'notifier.channel.email'
        return match ($channelName) {
            'email' => app('notifier.channel.email'),
            'sms' => app('notifier.channel.sms'),
            'push' => app('notifier.channel.push'),
            'in_app' => app('notifier.channel.in_app'),
            default => throw new \InvalidArgumentException("Unknown channel: {$channelName}"),
        };
    }

    /**
     * Create a recipient instance from payload data.
     */
    private function createRecipientFromPayload(array $payload): \Nexus\Notifier\Contracts\NotifiableInterface
    {
        $recipientData = $payload['recipient_data'] ?? [];
        
        return new class($payload['recipient_id'], $recipientData) implements \Nexus\Notifier\Contracts\NotifiableInterface {
            public function __construct(
                private readonly string $id,
                private readonly array $data
            ) {}

            public function getNotificationIdentifier(): string
            {
                return $this->id;
            }

            public function getNotificationEmail(): ?string
            {
                return $this->data['email'] ?? null;
            }

            public function getNotificationPhone(): ?string
            {
                return $this->data['phone'] ?? null;
            }

            public function getNotificationDeviceTokens(): array
            {
                return $this->data['device_tokens'] ?? [];
            }

            public function getNotificationLocale(): string
            {
                return $this->data['locale'] ?? 'en';
            }

            public function getNotificationTimezone(): string
            {
                return $this->data['timezone'] ?? 'UTC';
            }
        };
    }

    /**
     * Create a notification instance from payload data.
     */
    private function createNotificationFromPayload(array $payload): \Nexus\Notifier\Contracts\NotificationInterface
    {
        return new class($payload) implements \Nexus\Notifier\Contracts\NotificationInterface {
            public function __construct(
                private readonly array $payload
            ) {}

            public function toEmail(): array
            {
                return is_array($this->payload['content']) ? $this->payload['content'] : [];
            }

            public function toSms(): string
            {
                return is_string($this->payload['content']) ? $this->payload['content'] : '';
            }

            public function toPush(): array
            {
                return is_array($this->payload['content']) ? $this->payload['content'] : [];
            }

            public function toInApp(): array
            {
                return is_array($this->payload['content']) ? $this->payload['content'] : [];
            }

            public function getPriority(): \Nexus\Notifier\ValueObjects\Priority
            {
                return \Nexus\Notifier\ValueObjects\Priority::from($this->payload['priority'] ?? 'normal');
            }

            public function getCategory(): \Nexus\Notifier\ValueObjects\Category
            {
                return \Nexus\Notifier\ValueObjects\Category::from($this->payload['category'] ?? 'transactional');
            }

            public function getContent(): \Nexus\Notifier\ValueObjects\NotificationContent
            {
                return new \Nexus\Notifier\ValueObjects\NotificationContent(
                    emailData: $this->toEmail(),
                    smsText: $this->toSms(),
                    pushData: $this->toPush(),
                    inAppData: $this->toInApp()
                );
            }

            public function getType(): string
            {
                return $this->payload['notification_type'] ?? 'unknown';
            }
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
