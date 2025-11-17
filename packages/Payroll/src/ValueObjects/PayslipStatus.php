<?php

declare(strict_types=1);

namespace Nexus\Payroll\ValueObjects;

/**
 * Payslip status value object.
 */
enum PayslipStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    
    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    public function isEditable(): bool
    {
        return match($this) {
            self::DRAFT => true,
            self::PENDING_APPROVAL, self::APPROVED, self::PAID, self::CANCELLED => false,
        };
    }
}
