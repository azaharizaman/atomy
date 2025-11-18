<?php

declare(strict_types=1);

namespace Nexus\Notifier\ValueObjects;

/**
 * Notification Content
 *
 * Immutable value object containing all channel-specific content variations.
 */
final readonly class NotificationContent
{
    /**
     * @param array<string, mixed> $emailData Email content (subject, body, attachments)
     * @param string|null $smsText SMS message content
     * @param array<string, mixed>|null $pushData Push notification data (title, body, action)
     * @param array<string, mixed>|null $inAppData In-app message data (title, message, link)
     */
    public function __construct(
        public array $emailData = [],
        public ?string $smsText = null,
        public ?array $pushData = null,
        public ?array $inAppData = null,
    ) {}

    /**
     * Check if content exists for a specific channel
     */
    public function hasContentFor(ChannelType $channel): bool
    {
        return match ($channel) {
            ChannelType::Email => !empty($this->emailData),
            ChannelType::Sms => $this->smsText !== null,
            ChannelType::Push => $this->pushData !== null,
            ChannelType::InApp => $this->inAppData !== null,
        };
    }

    /**
     * Get content for a specific channel
     *
     * @return array<string, mixed>|string|null
     */
    public function getContentFor(ChannelType $channel): array|string|null
    {
        return match ($channel) {
            ChannelType::Email => $this->emailData,
            ChannelType::Sms => $this->smsText,
            ChannelType::Push => $this->pushData,
            ChannelType::InApp => $this->inAppData,
        };
    }

    /**
     * Get all available channels for this content
     *
     * @return array<ChannelType>
     */
    public function getAvailableChannels(): array
    {
        $channels = [];

        foreach (ChannelType::cases() as $channel) {
            if ($this->hasContentFor($channel)) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }
}
