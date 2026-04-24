<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Enums;

enum ProviderResultStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
    case MANUAL_ACTION_REQUIRED = 'manual_action_required';
}
