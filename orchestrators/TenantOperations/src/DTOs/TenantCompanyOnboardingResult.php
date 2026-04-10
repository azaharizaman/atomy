<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

use Nexus\Common\Contracts\OperationResultInterface;

/**
 * Result DTO for the alpha company onboarding flow.
 */
final readonly class TenantCompanyOnboardingResult implements OperationResultInterface
{
    /**
     * @param array<int, array{rule: string, message: string}> $issues
     * @param array<string, mixed> $bootstrapData
     */
    public function __construct(
        private bool $success,
        public ?string $tenantId = null,
        public ?string $ownerUserId = null,
        private array $issues = [],
        private ?string $message = null,
        private array $bootstrapData = [],
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message ?? ($this->success ? 'Company onboarded successfully' : 'Company onboarding failed');
    }

    public function getData(): array
    {
        return array_filter([
            'tenant_id' => $this->tenantId,
            'owner_user_id' => $this->ownerUserId,
            'bootstrap' => $this->bootstrapData ?: null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * @param array<string, mixed> $bootstrapData
     */
    public static function success(
        string $tenantId,
        string $ownerUserId,
        array $bootstrapData = [],
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            tenantId: $tenantId,
            ownerUserId: $ownerUserId,
            message: $message ?? 'Company onboarded successfully',
            bootstrapData: $bootstrapData,
        );
    }

    /**
     * @param array<int, array{rule: string, message: string}> $issues
     */
    public static function failure(array $issues, ?string $message = null): self
    {
        return new self(
            success: false,
            issues: $issues,
            message: $message ?? 'Company onboarding failed',
        );
    }
}
