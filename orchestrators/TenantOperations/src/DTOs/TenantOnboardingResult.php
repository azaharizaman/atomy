<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

/**
 * Result DTO for tenant onboarding.
 */
final readonly class TenantOnboardingResult
{
    /**
     * @param array<int, array{rule: string, message: string}> $issues
     */
    public function __construct(
        public bool $success,
        public ?string $tenantId = null,
        public ?string $adminUserId = null,
        public ?string $companyId = null,
        public array $issues = [],
        public ?string $message = null,
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(
        string $tenantId,
        string $adminUserId,
        string $companyId,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            tenantId: $tenantId,
            adminUserId: $adminUserId,
            companyId: $companyId,
            message: $message ?? 'Tenant onboarded successfully',
        );
    }

    /**
     * Create a failed result.
     *
     * @param array<int, array{rule: string, message: string}> $issues
     */
    public static function failure(array $issues, ?string $message = null): self
    {
        return new self(
            success: false,
            issues: $issues,
            message: $message ?? 'Tenant onboarding failed',
        );
    }
}
