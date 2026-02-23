<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\Exceptions;

/**
 * Exception for tenant onboarding errors.
 */
class TenantOnboardingException extends TenantOperationsException
{
    public static function codeNotUnique(string $code): self
    {
        return new self("Tenant code '{$code}' is already in use", ['code' => $code]);
    }

    public static function domainNotUnique(string $domain): self
    {
        return new self("Domain '{$domain}' is already in use", ['domain' => $domain]);
    }

    public static function invalidPlan(string $plan): self
    {
        return new self("Invalid plan: '{$plan}'", ['plan' => $plan]);
    }

    public static function creationFailed(string $reason): self
    {
        return new self("Failed to create tenant: {$reason}", ['reason' => $reason]);
    }
}
