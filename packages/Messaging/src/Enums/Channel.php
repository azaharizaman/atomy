<?php

declare(strict_types=1);

namespace Nexus\Messaging\Enums;

/**
 * Communication channel abstraction
 * 
 * Defines high-level protocol categories without knowing vendor-specific implementation.
 * The application layer maps these to actual providers (Twilio, SendGrid, etc.)
 * 
 * @package Nexus\Messaging
 */
enum Channel: string
{
    case Email = 'email';
    case SMS = 'sms';
    case PhoneCall = 'phone_call';
    case Chat = 'chat';
    case WhatsApp = 'whatsapp';
    case iMessage = 'imessage';
    case Webhook = 'webhook';
    case InternalNote = 'internal_note';

    /**
     * Check if channel is real-time/synchronous
     */
    public function isSynchronous(): bool
    {
        return match ($this) {
            self::PhoneCall, self::Chat, self::WhatsApp, self::iMessage => true,
            self::Email, self::SMS, self::Webhook, self::InternalNote => false,
        };
    }

    /**
     * Check if channel supports attachments
     */
    public function supportsAttachments(): bool
    {
        return match ($this) {
            self::Email, self::WhatsApp, self::iMessage => true,
            self::SMS, self::PhoneCall, self::Chat, self::Webhook, self::InternalNote => false,
        };
    }

    /**
     * Check if channel is encrypted by default
     */
    public function isEncrypted(): bool
    {
        return match ($this) {
            self::WhatsApp, self::iMessage => true,
            self::Email, self::SMS, self::PhoneCall, self::Chat, self::Webhook, self::InternalNote => false,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::SMS => 'SMS',
            self::PhoneCall => 'Phone Call',
            self::Chat => 'Chat',
            self::WhatsApp => 'WhatsApp',
            self::iMessage => 'iMessage',
            self::Webhook => 'Webhook',
            self::InternalNote => 'Internal Note',
        };
    }
}
