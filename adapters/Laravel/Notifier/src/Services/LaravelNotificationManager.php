<?php

declare(strict_types=1);

namespace Nexus\Laravel\Notifier\Services;

use DateTimeImmutable;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Nexus\Laravel\Notifier\Jobs\SendEmailNotificationJob;
use Nexus\Laravel\Notifier\Support\NotifierCacheKeys;
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
        $dispatchedChannels = [];

        foreach ($targetChannels as $channel) {
            $channelType = $this->resolveChannelType($channel);
            if ($channelType === null) {
                continue;
            }
            if ($channelType !== ChannelType::Email) {
                continue;
            }

            $emailData = $content->emailData;
            if ($emailData === null) {
                continue;
            }

            $job = $this->buildSendEmailJob(
                notificationId: $notificationId,
                recipient: $recipient,
                emailData: $emailData,
                ttlSeconds: null,
            );

            $this->queue->pushOn((string) ($this->config['queue'] ?? 'default'), $job);
            $dispatchedChannels[] = ChannelType::Email->value;
        }

        Cache::put(NotifierCacheKeys::status($notificationId), 'queued', now()->addDay());
        Cache::put(NotifierCacheKeys::channels($notificationId), array_values(array_unique($dispatchedChannels)), now()->addDay());

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
        $dispatchedChannels = [];

        $delaySeconds = max(0, $scheduledAt->getTimestamp() - time());

        foreach ($targetChannels as $channel) {
            $channelType = $this->resolveChannelType($channel);
            if ($channelType === null) {
                continue;
            }
            if ($channelType !== ChannelType::Email) {
                continue;
            }

            $emailData = $content->emailData;
            if ($emailData === null) {
                continue;
            }

            $job = $this->buildSendEmailJob(
                notificationId: $notificationId,
                recipient: $recipient,
                emailData: $emailData,
                ttlSeconds: $delaySeconds,
            );

            $this->queue->laterOn((string) ($this->config['queue'] ?? 'default'), $delaySeconds, $job);
            $dispatchedChannels[] = ChannelType::Email->value;
        }

        Cache::put(NotifierCacheKeys::status($notificationId), 'queued', now()->addDay());
        Cache::put(NotifierCacheKeys::channels($notificationId), array_values(array_unique($dispatchedChannels)), now()->addDay());

        return $notificationId;
    }

    public function cancel(string $notificationId): bool
    {
        $cancelled = false;

        if (Schema::hasTable('jobs')) {
            $deleted = DB::table('jobs')
                ->where('payload', 'like', '%' . $notificationId . '%')
                ->delete();
            $cancelled = $deleted > 0;
        }

        if ($cancelled) {
            Cache::put(NotifierCacheKeys::status($notificationId), 'cancelled', now()->addDay());
        }

        return $cancelled;
    }

    public function getStatus(string $notificationId): array
    {
        $cachedStatus = Cache::get(NotifierCacheKeys::status($notificationId));
        $channels = Cache::get(NotifierCacheKeys::channels($notificationId), []);

        if (is_string($cachedStatus) && $cachedStatus !== '') {
            return [
                'status' => $cachedStatus,
                'channels' => is_array($channels) ? $channels : [],
            ];
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = DB::table('failed_jobs')
                ->where('payload', 'like', '%' . $notificationId . '%')
                ->exists();
            if ($failed) {
                return [
                    'status' => 'failed',
                    'channels' => is_array($channels) ? $channels : [],
                ];
            }
        }

        if (Schema::hasTable('jobs')) {
            $queued = DB::table('jobs')
                ->where('payload', 'like', '%' . $notificationId . '%')
                ->exists();
            if ($queued) {
                return [
                    'status' => 'queued',
                    'channels' => is_array($channels) ? $channels : [],
                ];
            }
        }

        return [
            'status' => 'unknown',
            'channels' => is_array($channels) ? $channels : [],
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
    private function sanitizeQueueEmailData(array $data, ?int $ttlSeconds = null): array
    {
        $temporaryPassword = $data['temporary_password'] ?? null;
        if (!is_string($temporaryPassword) || trim($temporaryPassword) === '') {
            return $data;
        }

        $token = bin2hex(random_bytes(16));
        $effectiveTtl = max(900, (int) ($ttlSeconds ?? 0) + 60);
        Cache::put($this->temporaryPasswordCacheKey($token), $temporaryPassword, now()->addSeconds($effectiveTtl));

        unset($data['temporary_password']);
        $data['temporary_password_token'] = $token;

        return $data;
    }

    private function temporaryPasswordCacheKey(string $token): string
    {
        return NotifierCacheKeys::temporaryPassword($token);
    }

    /**
     * @param mixed $channel
     */
    private function resolveChannelType(mixed $channel): ?ChannelType
    {
        if ($channel instanceof ChannelType) {
            return $channel;
        }

        try {
            return ChannelType::from((string) $channel);
        } catch (\ValueError) {
            $this->logger->warning('Skipping notification channel: invalid channel type', [
                'channel' => (string) $channel,
            ]);
            return null;
        }
    }

    /**
     * @param array<string, mixed> $emailData
     */
    private function buildSendEmailJob(
        string $notificationId,
        NotifiableInterface $recipient,
        array $emailData,
        ?int $ttlSeconds,
    ): SendEmailNotificationJob {
        return new SendEmailNotificationJob(
            notificationId: $notificationId,
            toEmail: (string) ($recipient->getNotificationEmail() ?? ''),
            toName: (string) ($recipient->getNotificationIdentifier()),
            fromEmail: (string) ($this->config['from_email'] ?? 'no-reply@example.com'),
            fromName: (string) ($this->config['from_name'] ?? 'Atomy'),
            subject: (string) ($emailData['subject'] ?? 'Notification'),
            template: (string) ($emailData['template'] ?? 'generic'),
            data: $this->sanitizeQueueEmailData(
                is_array($emailData['data'] ?? null) ? (array) $emailData['data'] : [],
                $ttlSeconds
            ),
        );
    }
}

