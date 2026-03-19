<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Nexus\Identity\Contracts\UserQueryInterface;
use Nexus\Identity\Exceptions\UserNotFoundException;
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
        public ?string $setupTokenId,
    ) {
    }

    public function handle(
        UserQueryInterface $userQuery,
        NotificationManagerInterface $notificationManager,
        ?LoggerInterface $logger = null,
    ): void {
        $logger ??= new NullLogger();

        try {
            $user = $userQuery->findById($this->userId);
        } catch (UserNotFoundException) {
            $logger->warning('Skipping welcome notification: user not found', ['user_id' => $this->userId]);
            return;
        }
        /** @var \Nexus\Notifier\Contracts\NotifiableInterface $user */

        $temporaryPassword = null;
        if (is_string($this->setupTokenId) && $this->setupTokenId !== '') {
            $resolved = Cache::get($this->cacheKey($this->setupTokenId));
            if (is_string($resolved) && $resolved !== '') {
                $temporaryPassword = $resolved;
            }
        }

        if (is_string($this->setupTokenId) && $this->setupTokenId !== '' && ($temporaryPassword === null || $temporaryPassword === '')) {
            $logger->warning('Skipping welcome notification: missing setup secret', ['user_id' => $this->userId]);
            return;
        }

        $notificationManager->send($user, new WelcomeNotification($temporaryPassword));
        if (is_string($this->setupTokenId) && $this->setupTokenId !== '') {
            Cache::forget($this->cacheKey($this->setupTokenId));
        }
        $logger->info('Welcome notification queued+sent', ['user_id' => $this->userId]);
    }

    private function cacheKey(string $setupTokenId): string
    {
        return 'identity:welcome-temp-password:' . $setupTokenId;
    }
}

