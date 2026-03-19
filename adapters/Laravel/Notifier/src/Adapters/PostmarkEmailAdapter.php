<?php

declare(strict_types=1);

namespace Nexus\Laravel\Notifier\Adapters;

use Illuminate\Contracts\Mail\Mailer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class PostmarkEmailAdapter
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private Mailer $mailer,
        private array $config,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sendTemplatedEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $template,
        array $data,
        ?string $notificationId = null,
    ): void {
        if (trim($toEmail) === '') {
            $this->logger->warning('Skipping email: missing recipient email', [
                'notification_id' => $notificationId,
            ]);
            return;
        }

        $view = sprintf('nexus-notifier::emails.%s', $template);

        $this->mailer->send($view, ['data' => $data], function ($message) use ($toEmail, $toName, $subject): void {
            $message->from(
                (string) ($this->config['from_email'] ?? 'no-reply@example.com'),
                (string) ($this->config['from_name'] ?? 'Atomy'),
            );
            $message->to($toEmail, $toName);
            $message->subject($subject);
        });
    }
}

