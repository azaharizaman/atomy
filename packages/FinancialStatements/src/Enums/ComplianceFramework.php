<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Enums;

/**
 * Compliance frameworks for financial statements.
 */
enum ComplianceFramework: string
{
    case GAAP = 'gaap';
    case IFRS = 'ifrs';
    case MFRS = 'mfrs';
    case FRS = 'frs';
    case CUSTOM = 'custom';
}
