<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\ValueObjects;

enum CaseOutcome: string
{
    case SUBSTANTIATED = 'substantiated';
    case UNSUBSTANTIATED = 'unsubstantiated';
    case PENDING_INVESTIGATION = 'pending_investigation';
    case WITHDRAWN = 'withdrawn';
    case DISMISSED = 'dismissed';
}
