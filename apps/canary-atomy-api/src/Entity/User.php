<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Identity\Contracts\UserInterface as NexusUserInterface;
use Nexus\Identity\ValueObjects\RoleEnum;
use Nexus\Identity\ValueObjects\UserStatus;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User Entity.
 * 
 * Represents a person with access to the system.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['user:read']]),
        new Get(normalizationContext: ['groups' => ['user:read']]),
        new Post(denormalizationContext: ['groups' => ['user:write']], security: 'is_granted("ROLE_ADMIN")'),
        new Patch(denormalizationContext: ['groups' => ['user:write']], security: 'is_granted("ROLE_ADMIN")'),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    shortName: 'User'
)]
class User implements SymfonyUserInterface, PasswordAuthenticatedUserInterface, NexusUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups(['user:read'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write'])]
    private string $email;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:write'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 20, enumType: UserStatus::class)]
    #[Groups(['user:read', 'user:write'])]
    private UserStatus $status = UserStatus::PENDING_ACTIVATION;

    #[ORM\Column(type: 'string', length: 26, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $tenantId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['user:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeImmutable $passwordChangedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:read'])]
    private bool $mfaEnabled = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?array $metadata = null;

    public function __construct(string $email)
    {
        $this->id = (new Ulid())->toBase32();
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
    }

    // Symfony UserInterface
    public function getUserIdentifier(): string { return $this->email; }
    
    public function getRoles(): array 
    { 
        $roles = $this->roles; 
        $roles[] = RoleEnum::USER->value; 
        return array_unique($roles); 
    }
    
    public function setRoles(array $roles): self 
    { 
        $this->roles = $roles; 
        return $this; 
    }

    /**
     * @param RoleEnum[] $roles
     */
    public function setEnumRoles(array $roles): self
    {
        $this->roles = array_map(fn(RoleEnum $role) => $role->value, $roles);
        return $this;
    }

    public function addRole(RoleEnum $role): self
    {
        if (!in_array($role->value, $this->roles, true)) {
            $this->roles[] = $role->value;
        }
        return $this;
    }

    public function hasRole(RoleEnum $role): bool
    {
        return in_array($role->value, $this->getRoles(), true);
    }

    public function eraseCredentials(): void {}

    // PasswordAuthenticatedUserInterface
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    // Nexus UserInterface
    public function getId(): string { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getPasswordHash(): string { return $this->password; }
    public function getStatus(): string { return $this->status->value; }
    public function getName(): ?string { return $this->name; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt ?? $this->createdAt; }
    public function getEmailVerifiedAt(): ?\DateTimeInterface { return $this->emailVerifiedAt; }
    public function isActive(): bool { return $this->status === UserStatus::ACTIVE; }
    public function isLocked(): bool { return $this->status === UserStatus::LOCKED; }
    public function isEmailVerified(): bool { return $this->emailVerifiedAt !== null; }
    public function getTenantId(): ?string { return $this->tenantId; }
    public function getPasswordChangedAt(): ?\DateTimeInterface { return $this->passwordChangedAt; }
    public function hasMfaEnabled(): bool { return $this->mfaEnabled; }
    public function getMetadata(): ?array { return $this->metadata; }

    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function setStatus(UserStatus $status): self { $this->status = $status; return $this; }
    public function setTenantId(?string $tenantId): self { $this->tenantId = $tenantId; return $this; }
    public function setEmailVerifiedAt(?\DateTimeImmutable $date): self { $this->emailVerifiedAt = $date; return $this; }
}
