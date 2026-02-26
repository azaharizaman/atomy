<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

enum ProductionPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';
}
