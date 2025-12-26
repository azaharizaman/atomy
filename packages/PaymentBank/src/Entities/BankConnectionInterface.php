<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Entities;

use Nexus\PaymentBank\Enums\ConsentStatus;
use Nexus\PaymentBank\Enums\ProviderType;

interface BankConnectionInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getProviderType(): ProviderType;
    public function getProviderName(): string;
    public function getProviderConnectionId(): string;
    public function getAccessToken(): string;
    public function getCredentials(): array;
    public function getRefreshToken(): ?string;
    public function getExpiresAt(): ?\DateTimeImmutable;
    public function getConsentStatus(): ConsentStatus;
    public function getMetadata(): array;
    public function getCreatedAt(): \DateTimeImmutable;
    public function getUpdatedAt(): \DateTimeImmutable;

    public function withAccessToken(string $token, ?\DateTimeImmutable $expiresAt = null): self;
    public function withRefreshToken(?string $token): self;
    public function withConsentStatus(ConsentStatus $status): self;
    public function withMetadata(array $metadata): self;
    public function isExpired(): bool;
    public function requiresRelinking(): bool;
}
