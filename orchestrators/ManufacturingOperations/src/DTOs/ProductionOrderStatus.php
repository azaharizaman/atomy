<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

enum ProductionOrderStatus: string
{
    case Planned = 'Planned';
    case Released = 'Released';
    case InProgress = 'InProgress';
    case Completed = 'Completed';
    case Closed = 'Closed';
}
