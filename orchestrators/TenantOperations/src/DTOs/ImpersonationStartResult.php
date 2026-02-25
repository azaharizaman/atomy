<?php

declare(strict_types=1);

namespace Nexus\TenantOperations\DTOs;

use Nexus\Common\Contracts\OperationResultInterface;

/**
 * Result DTO for starting impersonation.
 * 
 * Implements OperationResultInterface for standardization.
 */
final readonly class ImpersonationStartResult implements OperationResultInterface
{
    public function __construct(
        private bool $success,
        public ?string $sessionId = null,
        public ?string $adminUserId = null,
        public ?string $targetTenantId = null,
        private ?string $message = null,
        public ?string $startedAt = null,
        public ?string $expiresAt = null,
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
        return $this->message ?? ($this->success ? 'Impersonation started successfully' : 'Failed to start impersonation');
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        return array_filter([
            'session_id' => $this->sessionId,
            'admin_user_id' => $this->adminUserId,
            'target_tenant_id' => $this->targetTenantId,
            'started_at' => $this->startedAt,
            'expires_at' => $this->expiresAt,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getIssues(): array
    {
        return [];
    }

    public static function success(
        string $sessionId,
        string $adminUserId,
        string $targetTenantId,
        string $expiresAt,
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            sessionId: $sessionId,
            adminUserId: $adminUserId,
            targetTenantId: $targetTenantId,
            message: $message ?? 'Impersonation started successfully',
            startedAt: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            expiresAt: $expiresAt,
        );
    }

    public static function failure(?string $message = null): self
    {
        return new self(
            success: false,
            message: $message ?? 'Failed to start impersonation',
        );
    }
}
