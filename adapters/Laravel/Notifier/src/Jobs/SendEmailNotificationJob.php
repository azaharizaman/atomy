<?php

declare(strict_types=1);

namespace Nexus\Laravel\Notifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Nexus\Laravel\Notifier\Adapters\PostmarkEmailAdapter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $notificationId,
        public string $toEmail,
        public string $toName,
        public string $fromEmail,
        public string $fromName,
        public string $subject,
        public string $template,
        public array $data,
    ) {
        unset($this->data['temporary_password']);
    }

    public function handle(PostmarkEmailAdapter $postmark, ?LoggerInterface $logger = null): void
    {
        $logger ??= new NullLogger();
        $resolved = $this->resolveSensitiveData($this->data);
        $data = $resolved['data'];
        $token = $resolved['temporary_password_token'];

        $postmark->sendTemplatedEmail(
            toEmail: $this->toEmail,
            toName: $this->toName,
            subject: $this->subject,
            template: $this->template,
            data: $data,
            fromEmail: $this->fromEmail,
            fromName: $this->fromName,
            notificationId: $this->notificationId,
        );
        if (is_string($token) && $token !== '') {
            Cache::forget($this->temporaryPasswordCacheKey($token));
        }

        $logger->info('Email notification sent', [
            'notification_id' => $this->notificationId,
            'to' => $this->toEmail,
            'template' => $this->template,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{data: array<string, mixed>, temporary_password_token: ?string}
     */
    private function resolveSensitiveData(array $data): array
    {
        $token = $data['temporary_password_token'] ?? null;
        if (!is_string($token) || trim($token) === '') {
            return [
                'data' => $data,
                'temporary_password_token' => null,
            ];
        }

        $secret = Cache::get($this->temporaryPasswordCacheKey($token));
        unset($data['temporary_password_token']);
        if (is_string($secret) && $secret !== '') {
            $data['temporary_password'] = $secret;
        }

        return [
            'data' => $data,
            'temporary_password_token' => $token,
        ];
    }

    private function temporaryPasswordCacheKey(string $token): string
    {
        return 'notifier:temporary-password:' . $token;
    }
}

