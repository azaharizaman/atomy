<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant;
use App\Models\UserLocalePreference;
use Illuminate\Support\Facades\Auth;
use Nexus\Localization\Contracts\LocaleResolverInterface;
use Nexus\Localization\Contracts\LocaleRepositoryInterface;
use Nexus\Localization\ValueObjects\Locale;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Database locale resolver implementation.
 *
 * Resolves user's active locale using precedence:
 * 1. User preference (user_locale_preferences table)
 * 2. Tenant default (tenants.locale)
 * 3. System default (config)
 *
 * Only active locales are returned; draft/deprecated trigger fallback.
 */
final class DbLocaleResolver implements LocaleResolverInterface
{
    public function __construct(
        private readonly LocaleRepositoryInterface $localeRepository,
        private readonly TenantContextInterface $tenantContext,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(): Locale
    {
        $user = Auth::user();

        if ($user) {
            return $this->resolveForUser($user->getId());
        }

        // No user - use tenant or system default
        if ($this->tenantContext->hasTenant()) {
            return $this->resolveForTenant($this->tenantContext->getCurrentTenantId());
        }

        return $this->getSystemDefault();
    }

    /**
     * {@inheritDoc}
     */
    public function resolveForUser(string $userId): Locale
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        if ($tenantId) {
            // Check user preference
            $preference = UserLocalePreference::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($preference) {
                $locale = new Locale($preference->locale_code);

                // Ensure locale is active
                if ($this->localeRepository->isActiveLocale($locale)) {
                    return $locale;
                }
            }

            // Fall back to tenant default
            return $this->resolveForTenant($tenantId);
        }

        return $this->getSystemDefault();
    }

    /**
     * {@inheritDoc}
     */
    public function resolveForTenant(string $tenantId): Locale
    {
        $tenant = Tenant::find($tenantId);

        if ($tenant && $tenant->locale) {
            try {
                $locale = new Locale($tenant->locale);

                // Ensure locale is active
                if ($this->localeRepository->isActiveLocale($locale)) {
                    return $locale;
                }
            } catch (\Exception $e) {
                // Invalid locale code in tenant settings - fall back to system default
            }
        }

        return $this->getSystemDefault();
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemDefault(): Locale
    {
        $defaultCode = config('localization.default_locale', 'en_US');

        return new Locale($defaultCode);
    }
}
