<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

enum JobType: string
{
    case EXPORT_REPORT = 'export_report';
    case DISTRIBUTE_REPORT = 'distribute_report';
}
