<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Nexus\IdentityOperations\Services\NotificationSenderInterface;
use Nexus\Laravel\Identity\Jobs\SendWelcomeNotificationJob;
use Tests\TestCase;

final class IdentityWelcomeQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_operations_send_welcome_enqueues_job(): void
    {
        Queue::fake();

        /** @var NotificationSenderInterface $sender */
        $sender = $this->app->make(NotificationSenderInterface::class);

        // We don't need a real user persisted in this test since we're asserting enqueue only.
        $sender->sendWelcome('user-id', 'temp-pass');

        Queue::assertPushed(SendWelcomeNotificationJob::class);
    }
}

