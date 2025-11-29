<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\\Repository\\ApiTokenRepository')]
#[ORM\Table(name: 'api_tokens')]
#[ORM\Index(name: 'idx_api_tokens_user', columns: ['user_id'])]
final class ApiToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(type: 'string', length: 26)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $scopes = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @param string[] $scopes
     */
    public function __construct(
        string $id,
        string $userId,
        string $name,
        string $tokenHash,
        array $scopes = [],
        ?\DateTimeImmutable $expiresAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->tokenHash = $tokenHash;
        $this->scopes = $scopes;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function getUserId(): string { return $this->userId; }
    public function getName(): string { return $this->name; }
    /** @return string[] */
    public function getScopes(): array { return $this->scopes ?? []; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function getLastUsedAt(): ?\DateTimeImmutable { return $this->lastUsedAt; }
    public function getRevokedAt(): ?\DateTimeImmutable { return $this->revokedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function revoke(): void
    {
        $this->revokedAt = new \DateTimeImmutable();
    }

    public function markUsed(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }
}
