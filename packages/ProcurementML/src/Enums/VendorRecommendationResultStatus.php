<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Enums;

enum VendorRecommendationResultStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
}
