<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;

final readonly class RfqInvitationReminderRecipient implements NotifiableInterface
{
    public function __construct(private RfqInvitationRecord $invitation)
    {
    }

    public function getNotificationEmail(): ?string
    {
        return $this->invitation->vendorEmail;
    }

    public function getNotificationPhone(): ?string
    {
        return null;
    }

    public function getNotificationDeviceTokens(): array
    {
        return [];
    }

    public function getNotificationLocale(): string
    {
        return 'en';
    }

    public function getNotificationTimezone(): string
    {
        return 'UTC';
    }

    public function getNotificationIdentifier(): string
    {
        return $this->invitation->vendorName
            ?? $this->invitation->vendorEmail
            ?? $this->invitation->id;
    }
}
