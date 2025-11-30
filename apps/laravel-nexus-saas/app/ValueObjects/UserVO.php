<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Nexus\Identity\Contracts\UserInterface;

/**
 * User Value Object implementing Nexus UserInterface
 *
 * Maps Laravel Eloquent User model data to domain contract
 */
final readonly class UserVO implements UserInterface
{
    public function __construct(
        private string $id,
        private string $email,
        private string $passwordHash,
        private string $status,
        private ?string $name,
        private \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private ?\DateTimeInterface $emailVerifiedAt,
        private ?string $tenantId = null,
        private ?\DateTimeInterface $passwordChangedAt = null,
        private bool $mfaEnabled = false,
        private ?array $metadata = null,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->emailVerifiedAt;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getPasswordChangedAt(): ?\DateTimeInterface
    {
        return $this->passwordChangedAt;
    }

    public function hasMfaEnabled(): bool
    {
        return $this->mfaEnabled;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Factory method to create UserVO from Eloquent model
     */
    public static function fromEloquent(\App\Models\User $model): self
    {
        return new self(
            id: (string) $model->id,
            email: $model->email,
            passwordHash: $model->password,
            status: $model->status ?? 'active',
            name: $model->name,
            createdAt: $model->created_at ?? new \DateTimeImmutable(),
            updatedAt: $model->updated_at ?? new \DateTimeImmutable(),
            emailVerifiedAt: $model->email_verified_at,
            tenantId: $model->tenant_id ?? null,
            passwordChangedAt: $model->password_changed_at ?? null,
            mfaEnabled: $model->two_factor_confirmed_at !== null,
            metadata: $model->metadata ?? null,
        );
    }
}
