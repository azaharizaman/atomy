<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Enums;

enum SlaStatus: string
{
    case COMPLIANT = 'compliant';
    case AT_RISK = 'at_risk';
    case BREACHED = 'breached';
}
