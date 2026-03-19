<?php

declare(strict_types=1);

namespace Nexus\Laravel\Identity\Notifications;

use Nexus\Notifier\Services\AbstractNotification;

final readonly class WelcomeNotification extends AbstractNotification
{
    public function __construct(private ?string $temporaryPassword)
    {
    }

    public function toEmail(): array
    {
        return [
            'subject' => 'Welcome to Atomy',
            'template' => 'welcome',
            'data' => ['temporary_password' => $this->temporaryPassword],
        ];
    }

    public function toSms(): ?string
    {
        return null;
    }

    public function toPush(): ?array
    {
        return null;
    }

    public function toInApp(): ?array
    {
        return null;
    }
}

