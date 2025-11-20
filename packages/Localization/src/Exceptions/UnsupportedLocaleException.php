<?php

declare(strict_types=1);

namespace Nexus\Localization\Exceptions;

/**
 * Exception thrown when a locale operation is not supported.
 *
 * This can occur when:
 * - Fallback chain exceeds maximum depth (3 hops)
 * - Locale is in draft or deprecated status when active is required
 */
final class UnsupportedLocaleException extends LocalizationException
{
}
