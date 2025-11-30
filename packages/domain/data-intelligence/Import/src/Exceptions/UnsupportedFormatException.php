<?php

declare(strict_types=1);

namespace Nexus\Import\Exceptions;

/**
 * Thrown when requested format has no registered parser
 */
class UnsupportedFormatException extends ImportException
{
    // Uses parent constructor directly
}
