<?php

declare(strict_types=1);

namespace Nexus\Export\Exceptions;

use Nexus\Export\ValueObjects\ExportFormat;

/**
 * Unsupported format exception
 * 
 * Thrown when requested format is not supported by formatter
 */
class UnsupportedFormatException extends ExportException
{
    public static function forFormat(ExportFormat $format): self
    {
        return new self("Unsupported export format: {$format->value}");
    }
}
