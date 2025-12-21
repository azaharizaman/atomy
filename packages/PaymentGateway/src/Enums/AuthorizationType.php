<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Enums;

/**
 * Types of payment authorization.
 */
enum AuthorizationType: string
{
    /**
     * Pre-authorization: Hold funds without capture.
     */
    case PREAUTH = 'preauth';

    /**
     * Authorize and capture in single transaction.
     */
    case AUTH_CAPTURE = 'auth_capture';

    /**
     * Delayed capture: Auth now, capture later.
     */
    case DELAYED_CAPTURE = 'delayed_capture';

    /**
     * Incremental authorization: Increase auth amount.
     */
    case INCREMENTAL = 'incremental';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PREAUTH => 'Pre-Authorization',
            self::AUTH_CAPTURE => 'Authorize & Capture',
            self::DELAYED_CAPTURE => 'Delayed Capture',
            self::INCREMENTAL => 'Incremental Authorization',
        };
    }

    /**
     * Check if capture is automatic.
     */
    public function isAutoCapture(): bool
    {
        return $this === self::AUTH_CAPTURE;
    }
}
