<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Workflows\Rma;

enum RmaStatus: string
{
    case PENDING_RECEIPT = 'pending_receipt';
    case PENDING_INSPECTION = 'pending_inspection';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
