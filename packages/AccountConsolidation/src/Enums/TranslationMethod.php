<?php

declare(strict_types=1);

namespace Nexus\AccountConsolidation\Enums;

/**
 * Currency translation methods.
 */
enum TranslationMethod: string
{
    case CURRENT_RATE = 'current_rate';
    case TEMPORAL = 'temporal';
    case HISTORICAL = 'historical';
    case AVERAGE = 'average';
}
