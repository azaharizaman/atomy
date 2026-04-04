<?php

declare(strict_types=1);

namespace App\Services\SourcingOperations;

use Illuminate\Support\Facades\App;
use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\SourcingOperations\DTOs\RfqInvitationRecord;

final readonly class RfqInvitationReminderRecipient implements NotifiableInterface
{
    public function __construct(private RfqInvitationRecord $invitation)
    {
    }

    public function getNotificationEmail(): ?string
    {
        if ($this->invitation->vendorEmail === null) {
            return null;
        }

        $email = trim($this->invitation->vendorEmail);

        return $email !== '' ? $email : null;
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
        $locale = App::getLocale();

        return trim($locale) !== '' ? $locale : 'en';
    }

    public function getNotificationTimezone(): string
    {
        $timezone = (string) config('app.timezone', 'UTC');

        return trim($timezone) !== '' ? $timezone : 'UTC';
    }

    public function getNotificationIdentifier(): string
    {
        $label = $this->invitation->vendorName;
        if ($label === null || trim($label) === '') {
            $label = $this->invitation->vendorEmail;
        }

        $label = $label !== null && trim($label) !== '' ? trim($label) : 'unknown';

        return $this->invitation->id . ':' . $label;
    }
}
