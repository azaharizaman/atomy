<?php

declare(strict_types=1);

namespace Nexus\Export\Exceptions;

use Nexus\Export\ValueObjects\ExportDestination;

/**
 * Unsupported destination exception
 * 
 * Thrown when requested destination is not implemented (e.g., PRINTER in MVP)
 */
class UnsupportedDestinationException extends ExportException
{
    public static function forDestination(ExportDestination $destination): self
    {
        return new self("Unsupported export destination: {$destination->value}");
    }
}
