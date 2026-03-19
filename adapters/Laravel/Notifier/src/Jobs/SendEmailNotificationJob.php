<?php

declare(strict_types=1);

namespace Nexus\Laravel\Notifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    }

    public function handle(PostmarkEmailAdapter $postmark, ?LoggerInterface $logger = null): void
    {
        $logger ??= new NullLogger();

        $postmark->sendTemplatedEmail(
            toEmail: $this->toEmail,
            toName: $this->toName,
            subject: $this->subject,
            template: $this->template,
            data: $this->data,
            notificationId: $this->notificationId,
        );

        $logger->info('Email notification sent', [
            'notification_id' => $this->notificationId,
            'to' => $this->toEmail,
            'template' => $this->template,
        ]);
    }
}

