<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

enum RecurrenceType: string
{
    case ONCE = 'once';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case CRON = 'cron';
}
