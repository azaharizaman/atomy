<?php

declare(strict_types=1);

namespace Nexus\Notifier\ValueObjects;

/**
 * Channel Type
 *
 * Represents available notification delivery channels.
 */
enum ChannelType: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Push = 'push';
    case InApp = 'in_app';

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Push => 'Push Notification',
            self::InApp => 'In-App Message',
        };
    }

    /**
     * Check if this channel requires external connectivity
     */
    public function requiresExternalProvider(): bool
    {
        return match ($this) {
            self::Email, self::Sms, self::Push => true,
            self::InApp => false,
        };
    }
}
