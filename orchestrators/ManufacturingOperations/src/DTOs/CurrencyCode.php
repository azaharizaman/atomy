<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

enum CurrencyCode: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case JPY = 'JPY';
    case CAD = 'CAD';
    case AUD = 'AUD';
    case MYR = 'MYR';
}
