<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nexus\Tenant\Contracts\TenantInterface;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: \App\Repository\TenantRepository::class)]
#[ORM\Table(name: 'tenants')]
class Tenant implements TenantInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $domain = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $subdomain = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $timezone = 'UTC';

    #[ORM\Column(type: 'string', length: 10)]
    private string $locale = 'en_US';

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency = 'USD';

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $code, string $name, string $email)
    {
        $this->id = (string) new Ulid();
        $this->code = $code;
        $this->name = $name;
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getStatus(): string { return $this->status; }
    public function getDomain(): ?string { return $this->domain; }
    public function getSubdomain(): ?string { return $this->subdomain; }
    public function getDatabaseName(): ?string { return null; }
    public function getTimezone(): string { return $this->timezone; }
    public function getLocale(): string { return $this->locale; }
    public function getCurrency(): string { return $this->currency; }
    public function getDateFormat(): string { return 'Y-m-d'; }
    public function getTimeFormat(): string { return 'H:i:s'; }
    public function getParentId(): ?string { return null; }
    public function getMetadata(): array { return $this->metadata; }
    public function getMetadataValue(string $key, mixed $default = null): mixed { return $this->metadata[$key] ?? $default; }
    public function isActive(): bool { return $this->status === 'active'; }
    public function isSuspended(): bool { return $this->status === 'suspended'; }
    public function isTrial(): bool { return $this->status === 'trial'; }
    public function isArchived(): bool { return $this->status === 'archived'; }
    public function getTrialEndsAt(): ?\DateTimeInterface { return null; }
    public function isTrialExpired(): bool { return false; }
    public function getStorageQuota(): ?int { return null; }
    public function getStorageUsed(): int { return 0; }
    public function getMaxUsers(): ?int { return null; }
    public function getRateLimit(): ?int { return null; }
    public function isReadOnly(): bool { return false; }
    public function getBillingCycleStartDate(): ?\DateTimeInterface { return null; }
    public function getOnboardingProgress(): int { return 0; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function getDeletedAt(): ?\DateTimeInterface { return null; }

    public function setStatus(string $status): void { $this->status = $status; }
}
