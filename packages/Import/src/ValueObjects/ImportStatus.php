<?php

declare(strict_types=1);

namespace Nexus\Import\ValueObjects;

/**
 * Import status enumeration
 */
enum ImportStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
