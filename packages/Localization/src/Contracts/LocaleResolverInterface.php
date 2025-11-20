<?php

declare(strict_types=1);

namespace Nexus\Localization\Contracts;

use Nexus\Localization\ValueObjects\Locale;

/**
 * Locale resolver interface.
 *
 * Resolves the current user's active locale using precedence:
 * 1. User-level preference
 * 2. Tenant-level default
 * 3. System default
 *
 * This interface must be implemented in the application layer.
 */
interface LocaleResolverInterface
{
    /**
     * Resolve the current user's active locale.
     *
     * Resolution precedence:
     * 1. User preference from user_locale_preferences table
     * 2. Tenant default from tenants.locale
     * 3. System default from configuration
     *
     * Only active locales are returned; draft/deprecated trigger fallback.
     */
    public function resolve(): Locale;

    /**
     * Resolve locale for a specific user.
     */
    public function resolveForUser(string $userId): Locale;

    /**
     * Resolve locale for a specific tenant (no user override).
     */
    public function resolveForTenant(string $tenantId): Locale;

    /**
     * Get the system default locale from configuration.
     */
    public function getSystemDefault(): Locale;
}
