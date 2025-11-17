<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * SLA status enum.
 */
enum SlaStatus: string
{
    case ON_TRACK = 'on_track';
    case AT_RISK = 'at_risk';
    case BREACHED = 'breached';
}
