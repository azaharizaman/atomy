<?php

declare(strict_types=1);

namespace Nexus\Messaging\Enums;

/**
 * Message flow direction
 * 
 * @package Nexus\Messaging
 */
enum Direction: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Inbound => 'Inbound',
            self::Outbound => 'Outbound',
        };
    }

    /**
     * Check if message was received from external party
     */
    public function isInbound(): bool
    {
        return $this === self::Inbound;
    }

    /**
     * Check if message was sent to external party
     */
    public function isOutbound(): bool
    {
        return $this === self::Outbound;
    }
}
