<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Notifications;

use Illuminate\Support\Facades\Queue;
use Nexus\Laravel\Notifier\Jobs\SendEmailNotificationJob;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Tests\TestCase;

final class PostmarkQueueTest extends TestCase
{
    public function test_notification_manager_queues_email_job_for_welcome_notification(): void
    {
        Queue::fake();

        /** @var NotificationManagerInterface $notifier */
        $notifier = $this->app->make(NotificationManagerInterface::class);

        $recipient = new class implements \Nexus\Notifier\Contracts\NotifiableInterface {
            public function getNotificationIdentifier(): string { return 'user-1'; }
            public function getNotificationEmail(): ?string { return 'user@example.com'; }
            public function getNotificationPhone(): ?string { return null; }
            public function getNotificationDeviceTokens(): array { return []; }
            public function getNotificationLocale(): string { return 'en'; }
            public function getNotificationTimezone(): string { return 'UTC'; }
        };

        $notification = new readonly class extends \Nexus\Notifier\Services\AbstractNotification {
            public function toEmail(): array {
                return [
                    'subject' => 'Welcome to Atomy',
                    'template' => 'welcome',
                    'data' => ['temporary_password' => 'temp-pass'],
                ];
            }
            public function toSms(): string { return ''; }
            public function toPush(): array { return ['title' => '', 'body' => '']; }
            public function toInApp(): array { return ['title' => '', 'message' => '']; }
        };

        $notifier->send($recipient, $notification);

        Queue::assertPushed(SendEmailNotificationJob::class);
    }
}

