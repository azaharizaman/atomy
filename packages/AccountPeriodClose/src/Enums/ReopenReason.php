<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Enums;

/**
 * Reasons for reopening a closed period.
 */
enum ReopenReason: string
{
    case CORRECTION = 'correction';
    case AUDIT_ADJUSTMENT = 'audit_adjustment';
    case LATE_INVOICE = 'late_invoice';
    case MANAGEMENT_REQUEST = 'management_request';
    case REGULATORY_REQUIREMENT = 'regulatory_requirement';
}
