<?php

declare(strict_types=1);

namespace Nexus\Laravel\Notifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
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

    public function handle(?LoggerInterface $logger = null): void
    {
        $logger ??= new NullLogger();

        if (trim($this->toEmail) === '') {
            $logger->warning('Skipping email notification: missing recipient email', [
                'notification_id' => $this->notificationId,
            ]);
            return;
        }

        $view = sprintf('nexus-notifier::emails.%s', $this->template);

        Mail::send($view, ['data' => $this->data], function ($message): void {
            $message->from($this->fromEmail, $this->fromName);
            $message->to($this->toEmail, $this->toName);
            $message->subject($this->subject);
        });

        $logger->info('Email notification sent', [
            'notification_id' => $this->notificationId,
            'to' => $this->toEmail,
            'template' => $this->template,
        ]);
    }
}

