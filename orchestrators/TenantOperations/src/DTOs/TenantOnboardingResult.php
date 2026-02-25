<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

use Nexus\Common\Contracts\OperationResultInterface;

/**
 * Result DTO for tenant onboarding.
 * 
 * Implements OperationResultInterface for standardization.
 */
final readonly class TenantOnboardingResult implements OperationResultInterface
{
    /**
     * @param array<int, array{rule: string, message: string}> $issues
     */
    public function __construct(
        private bool $success,
        public ?string $tenantId = null,
        public ?string $adminUserId = null,
        public ?string $companyId = null,
        private array $issues = [],
        private ?string $message = null,
    ) {}

    /**
     * @inheritDoc
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string
    {
        return $this->message ?? ($this->success ? 'Tenant onboarded successfully' : 'Tenant onboarding failed');
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        return array_filter([
            'tenant_id' => $this->tenantId,
            'admin_user_id' => $this->adminUserId,
            'company_id' => $this->companyId,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

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
