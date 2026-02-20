<?php

declare(strict_types=1);

namespace Nexus\Leave\Enums;

enum AccrualFrequency: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case FIXED_ALLOCATION = 'fixed_allocation';
}
