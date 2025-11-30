<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Domain\Identity\Contracts\UserInterface as NexusUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

#[ORM\Entity(repositoryClass: 'App\\Repository\\UserRepository')]
#[ORM\Table(name: 'users')]
final class User implements NexusUserInterface, SymfonyUserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255, name: 'password_hash')]
    private string $passwordHash;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(type: 'string', length: 32)]
    private string $status = 'active';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    private ?string $tenantId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerifiedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $passwordChangedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $locked = false;

    #[ORM\Column(type: 'boolean')]
    private bool $mfaEnabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    

    public function __construct(
        string $id,
        string $email,
        string $passwordHash,
        array $roles = ['ROLE_USER'],
        string $status = 'active',
        ?string $name = null,
        ?string $tenantId = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->roles = $roles;
        $this->status = $status;
        $this->name = $name;
        $this->tenantId = $tenantId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\Column(type: 'integer')]
    private int $failedLoginAttempts = 0;

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

    public function setPasswordHash(string $hash): void
    {
        $this->passwordHash = $hash;
        $this->passwordChangedAt = new \DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
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
        return $this->status === 'active' && !$this->locked;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getFailedLoginAttempts(): int
    {
        return $this->failedLoginAttempts;
    }

    public function incrementFailedLoginAttempts(): int
    {
        $this->failedLoginAttempts++;
        return $this->failedLoginAttempts;
    }

    public function resetFailedLoginAttempts(): void
    {
        $this->failedLoginAttempts = 0;
    }

    public function lock(string $reason = ''): void
    {
        $this->locked = true;
    }

    public function unlock(): void
    {
        $this->locked = false;
        $this->resetFailedLoginAttempts();
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

    // Symfony UserInterface
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    // for PasswordAuthenticatedUserInterface
    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
        // no-op for now
    }
}
