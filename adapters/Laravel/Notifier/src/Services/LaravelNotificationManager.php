<?php

declare(strict_types=1);

namespace Nexus\Laravel\Notifier\Services;

use DateTimeImmutable;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Facades\Cache;
use Nexus\Laravel\Notifier\Jobs\SendEmailNotificationJob;
use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Notifier\ValueObjects\ChannelType;
use Psr\Log\LoggerInterface;

final readonly class LaravelNotificationManager implements NotificationManagerInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private Queue $queue,
        private array $config,
        private LoggerInterface $logger,
    ) {
    }

    public function send(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        ?array $channels = null
    ): string {
        $notificationId = $this->generateNotificationId();
        $content = $notification->getContent();

        $targetChannels = $channels ?? $content->getAvailableChannels();

        foreach ($targetChannels as $channel) {
            $channelType = $channel instanceof ChannelType ? $channel : ChannelType::from((string) $channel);
            if ($channelType !== ChannelType::Email) {
                continue;
            }

            $emailData = $content->emailData;
            if ($emailData === null) {
                continue;
            }

            $job = new SendEmailNotificationJob(
                notificationId: $notificationId,
                toEmail: (string) ($recipient->getNotificationEmail() ?? ''),
                toName: (string) ($recipient->getNotificationIdentifier()),
                fromEmail: (string) ($this->config['from_email'] ?? 'no-reply@example.com'),
                fromName: (string) ($this->config['from_name'] ?? 'Atomy'),
                subject: (string) ($emailData['subject'] ?? 'Notification'),
                template: (string) ($emailData['template'] ?? 'generic'),
                data: $this->sanitizeQueueEmailData(is_array($emailData['data'] ?? null) ? (array) $emailData['data'] : []),
            );

            $this->queue->pushOn((string) ($this->config['queue'] ?? 'default'), $job);
        }

        $this->logger->info('Notification queued', [
            'notification_id' => $notificationId,
            'recipient' => $recipient->getNotificationIdentifier(),
            'type' => $notification->getType(),
        ]);

        return $notificationId;
    }

    public function sendBatch(array $recipients, NotificationInterface $notification, ?array $channels = null): array
    {
        $out = [];
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof NotifiableInterface) {
                continue;
            }
            $out[$recipient->getNotificationIdentifier()] = $this->send($recipient, $notification, $channels);
        }
        return $out;
    }

    public function schedule(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        DateTimeImmutable $scheduledAt,
        ?array $channels = null
    ): string {
        $notificationId = $this->generateNotificationId();
        $content = $notification->getContent();
        $targetChannels = $channels ?? $content->getAvailableChannels();

        $delaySeconds = max(0, $scheduledAt->getTimestamp() - time());

        foreach ($targetChannels as $channel) {
            $channelType = $channel instanceof ChannelType ? $channel : ChannelType::from((string) $channel);
            if ($channelType !== ChannelType::Email) {
                continue;
            }

            $emailData = $content->emailData;
            if ($emailData === null) {
                continue;
            }

            $job = (new SendEmailNotificationJob(
                notificationId: $notificationId,
                toEmail: (string) ($recipient->getNotificationEmail() ?? ''),
                toName: (string) ($recipient->getNotificationIdentifier()),
                fromEmail: (string) ($this->config['from_email'] ?? 'no-reply@example.com'),
                fromName: (string) ($this->config['from_name'] ?? 'Atomy'),
                subject: (string) ($emailData['subject'] ?? 'Notification'),
                template: (string) ($emailData['template'] ?? 'generic'),
                data: $this->sanitizeQueueEmailData(is_array($emailData['data'] ?? null) ? (array) $emailData['data'] : []),
            ))->delay($delaySeconds);

            $this->queue->laterOn((string) ($this->config['queue'] ?? 'default'), $delaySeconds, $job);
        }

        return $notificationId;
    }

    public function cancel(string $notificationId): bool
    {
        // Laravel queue doesn't support guaranteed removal by ID across backends.
        return false;
    }

    public function getStatus(string $notificationId): array
    {
        return [
            'status' => 'unknown',
            'channels' => [],
        ];
    }

    private function generateNotificationId(): string
    {
        return sprintf('notif_%s_%s', (new \DateTimeImmutable())->format('YmdHis'), bin2hex(random_bytes(8)));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeQueueEmailData(array $data): array
    {
        $temporaryPassword = $data['temporary_password'] ?? null;
        if (!is_string($temporaryPassword) || trim($temporaryPassword) === '') {
            return $data;
        }

        $token = bin2hex(random_bytes(16));
        Cache::put($this->temporaryPasswordCacheKey($token), $temporaryPassword, now()->addMinutes(15));

        unset($data['temporary_password']);
        $data['temporary_password_token'] = $token;

        return $data;
    }

    private function temporaryPasswordCacheKey(string $token): string
    {
        return 'notifier:temporary-password:' . $token;
    }
}

