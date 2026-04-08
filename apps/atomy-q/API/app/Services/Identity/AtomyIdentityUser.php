<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User as UserModel;
use Nexus\Identity\Contracts\UserInterface;

/**
 * Maps Atomy-Q {@see UserModel} to Nexus {@see UserInterface}.
 */
final readonly class AtomyIdentityUser implements UserInterface
{
    public function __construct(private UserModel $user)
    {
    }

    public function getId(): string
    {
        return (string) $this->user->id;
    }

    public function getEmail(): string
    {
        return (string) $this->user->email;
    }

    public function getPasswordHash(): string
    {
        return (string) $this->user->password_hash;
    }

    public function getStatus(): string
    {
        return (string) $this->user->status;
    }

    public function getName(): ?string
    {
        $name = trim((string) $this->user->name);

        return $name === '' ? null : $name;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->user->created_at?->toImmutable() ?? new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->user->updated_at?->toImmutable() ?? new \DateTimeImmutable();
    }

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->user->email_verified_at?->toImmutable();
    }

    public function isActive(): bool
    {
        return $this->user->status === 'active';
    }

    public function isLocked(): bool
    {
        return $this->user->status === 'locked';
    }

    public function isEmailVerified(): bool
    {
        return $this->user->email_verified_at !== null;
    }

    public function getTenantId(): ?string
    {
        return $this->user->tenant_id !== null ? (string) $this->user->tenant_id : null;
    }

    public function getPasswordChangedAt(): ?\DateTimeInterface
    {
        return null;
    }

    public function hasMfaEnabled(): bool
    {
        return (bool) $this->user->mfa_enabled;
    }

    public function getMetadata(): ?array
    {
        return is_array($this->user->metadata) ? $this->user->metadata : null;
    }
}
