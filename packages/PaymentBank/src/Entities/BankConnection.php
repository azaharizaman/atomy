<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

use Nexus\PaymentBank\Enums\ConsentStatus;
use Nexus\PaymentBank\Enums\ProviderType;

final readonly class BankConnection implements BankConnectionInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private ProviderType $providerType,
        private string $providerConnectionId,
        private string $accessToken,
        private ?string $refreshToken,
        private ?\DateTimeImmutable $expiresAt,
        private ConsentStatus $consentStatus,
        private array $metadata,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getProviderType(): ProviderType
    {
        return $this->providerType;
    }

    public function getProviderName(): string
    {
        return $this->providerType->value;
    }

    public function getProviderConnectionId(): string
    {
        return $this->providerConnectionId;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getCredentials(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt?->format('c'),
            'provider_connection_id' => $this->providerConnectionId,
        ];
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getConsentStatus(): ConsentStatus
    {
        return $this->consentStatus;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withAccessToken(string $token, ?\DateTimeImmutable $expiresAt = null): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->providerType,
            $this->providerConnectionId,
            $token,
            $this->refreshToken,
            $expiresAt ?? $this->expiresAt,
            $this->consentStatus,
            $this->metadata,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function withRefreshToken(?string $token): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->providerType,
            $this->providerConnectionId,
            $this->accessToken,
            $token,
            $this->expiresAt,
            $this->consentStatus,
            $this->metadata,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function withConsentStatus(ConsentStatus $status): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->providerType,
            $this->providerConnectionId,
            $this->accessToken,
            $this->refreshToken,
            $this->expiresAt,
            $status,
            $this->metadata,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->providerType,
            $this->providerConnectionId,
            $this->accessToken,
            $this->refreshToken,
            $this->expiresAt,
            $this->consentStatus,
            array_merge($this->metadata, $metadata),
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function requiresRelinking(): bool
    {
        return $this->consentStatus === ConsentStatus::EXPIRED
            || $this->consentStatus === ConsentStatus::REVOKED
            || $this->isExpired();
    }
}
