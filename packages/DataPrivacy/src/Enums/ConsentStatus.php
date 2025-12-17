<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Enums;

/**
 * Status of consent given by a data subject.
 */
enum ConsentStatus: string
{
    /**
     * Consent has been granted and is currently active.
     */
    case GRANTED = 'granted';

    /**
     * Consent has been withdrawn by the data subject.
     */
    case WITHDRAWN = 'withdrawn';

    /**
     * Consent has expired (time-limited consent).
     */
    case EXPIRED = 'expired';

    /**
     * Consent is pending (awaiting confirmation).
     */
    case PENDING = 'pending';

    /**
     * Consent was denied/refused.
     */
    case DENIED = 'denied';

    /**
     * Check if consent is currently valid for processing.
     */
    public function isValid(): bool
    {
        return $this === self::GRANTED;
    }

    /**
     * Check if consent is active (granted status).
     */
    public function isActive(): bool
    {
        return $this === self::GRANTED;
    }

    /**
     * Check if this is a terminal state.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::WITHDRAWN, self::EXPIRED, self::DENIED => true,
            default => false,
        };
    }

    /**
     * Get human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::GRANTED => 'Consent Granted',
            self::WITHDRAWN => 'Consent Withdrawn',
            self::EXPIRED => 'Consent Expired',
            self::PENDING => 'Consent Pending',
            self::DENIED => 'Consent Denied',
        };
    }
}
