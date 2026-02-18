<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Enums;

enum CreditCheckResult: string
{
    case APPROVED = 'approved';
    case WARNING = 'warning';
    case DENIED = 'denied';
    case ON_HOLD = 'on_hold';

    public function canProceed(): bool
    {
        return in_array($this, [self::APPROVED, self::WARNING]);
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::DENIED, self::ON_HOLD]);
    }
}
