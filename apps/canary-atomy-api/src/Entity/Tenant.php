<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\TenantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Enums\TenantStatus;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tenant Entity.
 * 
 * Represents an organization or workspace in the multi-tenant system.
 */
#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenants')]
#[ApiResource(
    operations: [
        new GetCollection(normalizationContext: ['groups' => ['tenant:read']]),
        new Get(normalizationContext: ['groups' => ['tenant:read']]),
        new Post(denormalizationContext: ['groups' => ['tenant:write']], security: 'is_granted("ROLE_SUPER_ADMIN")'),
        new Patch(denormalizationContext: ['groups' => ['tenant:write']], security: 'is_granted("ROLE_SUPER_ADMIN")'),
        new Delete(security: 'is_granted("ROLE_SUPER_ADMIN")'),
    ],
    normalizationContext: ['groups' => ['tenant:read']],
    denormalizationContext: ['groups' => ['tenant:write']],
    shortName: 'Tenant'
)]
class Tenant implements TenantInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups(['tenant:read'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Email]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $email;

    #[ORM\Column(type: 'string', length: 20, enumType: TenantStatus::class)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private TenantStatus $status = TenantStatus::Pending;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?string $domain = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?string $subdomain = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $databaseName = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $timezone = 'UTC';

    #[ORM\Column(type: 'string', length: 10)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $locale = 'en_US';

    #[ORM\Column(type: 'string', length: 3)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $currency = 'USD';

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $dateFormat = 'Y-m-d';

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private string $timeFormat = 'H:i:s';

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?string $parentId = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private array $metadata = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['tenant:read'])]
    private ?\DateTimeInterface $trialEndsAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?int $storageQuota = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['tenant:read'])]
    private int $storageUsed = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?int $maxUsers = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private ?int $rateLimit = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['tenant:read', 'tenant:write'])]
    private bool $readOnly = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['tenant:read'])]
    private ?\DateTimeInterface $billingCycleStartDate = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['tenant:read'])]
    private int $onboardingProgress = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['tenant:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['tenant:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['tenant:read'])]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(string $code, string $name, string $email)
    {
        $this->id = (new Ulid())->toBase32();
        $this->code = $code;
        $this->name = $name;
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getStatus(): string { return $this->status->value; }
    public function getDomain(): ?string { return $this->domain; }
    public function getSubdomain(): ?string { return $this->subdomain; }
    public function getDatabaseName(): ?string { return $this->databaseName; }
    public function getTimezone(): string { return $this->timezone; }
    public function getLocale(): string { return $this->locale; }
    public function getCurrency(): string { return $this->currency; }
    public function getDateFormat(): string { return $this->dateFormat; }
    public function getTimeFormat(): string { return $this->timeFormat; }
    public function getParentId(): ?string { return $this->parentId; }
    public function getMetadata(): array { return $this->metadata; }
    public function getMetadataValue(string $key, mixed $default = null): mixed { return $this->metadata[$key] ?? $default; }
    public function isActive(): bool { return $this->status->isActive(); }
    public function isSuspended(): bool { return $this->status->isSuspended(); }
    public function isTrial(): bool { return $this->status->isTrial(); }
    public function isArchived(): bool { return $this->status->isArchived(); }
    public function getTrialEndsAt(): ?\DateTimeInterface { return $this->trialEndsAt; }
    public function isTrialExpired(): bool { return $this->trialEndsAt !== null && $this->trialEndsAt < new \DateTime(); }
    public function getStorageQuota(): ?int { return $this->storageQuota; }
    public function getStorageUsed(): int { return $this->storageUsed; }
    public function getMaxUsers(): ?int { return $this->maxUsers; }
    public function getRateLimit(): ?int { return $this->rateLimit; }
    public function isReadOnly(): bool { return $this->readOnly; }
    public function getBillingCycleStartDate(): ?\DateTimeInterface { return $this->billingCycleStartDate; }
    public function getOnboardingProgress(): int { return $this->onboardingProgress; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function getDeletedAt(): ?\DateTimeInterface { return $this->deletedAt; }

    public function setStatus(TenantStatus $status): void { $this->status = $status; }
    public function setDomain(?string $domain): void { $this->domain = $domain; }
    public function setSubdomain(?string $subdomain): void { $this->subdomain = $subdomain; }
    public function setTimezone(string $timezone): void { $this->timezone = $timezone; }
    public function setLocale(string $locale): void { $this->locale = $locale; }
    public function setCurrency(string $currency): void { $this->currency = $currency; }
    public function setMetadata(array $metadata): void { $this->metadata = $metadata; }
}
