<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\\Repository\\SessionRepository')]
#[ORM\Table(name: 'sessions')]
#[ORM\Index(name: 'idx_sessions_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_sessions_expires', columns: ['expires_at'])]
final class Session
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 128, unique: true)]
    private string $token;

    #[ORM\Column(type: 'string', length: 26)]
    private string $userId;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $deviceFingerprint = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastActivityAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        string $id,
        string $token,
        string $userId,
        \DateTimeImmutable $expiresAt,
        ?array $metadata = null,
        ?string $deviceFingerprint = null
    ) {
        $this->id = $id;
        $this->token = $token;
        $this->userId = $userId;
        $this->expiresAt = $expiresAt;
        $this->metadata = $metadata;
        $this->deviceFingerprint = $deviceFingerprint;
        $this->createdAt = new \DateTimeImmutable();
        $this->lastActivityAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getToken(): string { return $this->token; }
    public function getUserId(): string { return $this->userId; }
    /** @return array<string, mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getDeviceFingerprint(): ?string { return $this->deviceFingerprint; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function getLastActivityAt(): \DateTimeImmutable { return $this->lastActivityAt; }
    public function getRevokedAt(): ?\DateTimeImmutable { return $this->revokedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isRevoked();
    }

    public function revoke(): void
    {
        $this->revokedAt = new \DateTimeImmutable();
    }

    public function updateActivity(): void
    {
        $this->lastActivityAt = new \DateTimeImmutable();
    }

    public function extend(\DateTimeImmutable $newExpiresAt): void
    {
        $this->expiresAt = $newExpiresAt;
        $this->lastActivityAt = new \DateTimeImmutable();
    }
}
