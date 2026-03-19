<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Laravel\Identity\Notifications\WelcomeNotification;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class SendWelcomeNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $userId,
        public ?string $temporaryPassword,
    ) {
    }

    public function handle(
        UserQueryInterface $userQuery,
        NotificationManagerInterface $notificationManager,
        ?LoggerInterface $logger = null,
    ): void {
        $logger ??= new NullLogger();

        $user = $userQuery->findById($this->userId);
        /** @var \Nexus\Notifier\Contracts\NotifiableInterface $user */

        $notificationManager->send($user, new WelcomeNotification($this->temporaryPassword));
        $logger->info('Welcome notification queued+sent', ['user_id' => $this->userId]);
    }
}

