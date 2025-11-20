<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

use Exception;

/**
 * Base exception for all localization-related errors.
 *
 * All exceptions in the Localization package extend this base exception,
 * enabling catch-all handling of localization errors.
 */
abstract class LocalizationException extends Exception
{
}
