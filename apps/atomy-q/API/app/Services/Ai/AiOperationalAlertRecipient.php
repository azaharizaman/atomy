<?php

declare(strict_types=1);

namespace App\Services\Ai;

use InvalidArgumentException;
use Nexus\Notifier\Contracts\NotifiableInterface;

final readonly class AiOperationalAlertRecipient implements NotifiableInterface
{
    private string $email;

    public function __construct(string $email)
    {
        $normalizedEmail = trim($email);
        if ($normalizedEmail === '') {
            throw new InvalidArgumentException('AI operational alert recipient email cannot be empty.');
        }

        $this->email = strtolower($normalizedEmail);
    }

    public function getNotificationEmail(): ?string
    {
        return $this->email;
    }

    public function getNotificationPhone(): ?string
    {
        return null;
    }

    /**
     * @return array<string>
     */
    public function getNotificationDeviceTokens(): array
    {
        return [];
    }

    public function getNotificationLocale(): string
    {
        return (string) (config('operational_alert.locale') ?? config('app.locale', 'en'));
    }

    public function getNotificationTimezone(): string
    {
        return (string) (config('operational_alert.timezone') ?? config('app.timezone', 'UTC'));
    }

    public function getNotificationIdentifier(): string
    {
        return 'ai-ops:' . substr(sha1($this->email), 0, 16);
    }
}
