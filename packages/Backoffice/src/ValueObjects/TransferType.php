<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum TransferType: string
{
    case PROMOTION = 'promotion';
    case LATERAL_MOVE = 'lateral_move';
    case DEMOTION = 'demotion';
    case RELOCATION = 'relocation';
}
