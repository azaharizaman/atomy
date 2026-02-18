<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Enums;

enum OrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING_CREDIT = 'pending_credit';
    case CREDIT_HOLD = 'credit_hold';
    case CONFIRMED = 'confirmed';
    case PARTIALLY_SHIPPED = 'partially_shipped';
    case FULLY_SHIPPED = 'fully_shipped';
    case PARTIALLY_INVOICED = 'partially_invoiced';
    case INVOICED = 'invoiced';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function canBeModified(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING_CREDIT]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING_CREDIT, self::CONFIRMED, self::CREDIT_HOLD]);
    }

    public function isActive(): bool
    {
        return !in_array($this, [self::CANCELLED, self::PAID]);
    }
}
