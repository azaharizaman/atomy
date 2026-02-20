<?php

declare(strict_types=1);

namespace Nexus\Treasury\Models;

use Nexus\Treasury\Contracts\AuthorizationLimitInterface;

final readonly class AuthorizationLimit implements AuthorizationLimitInterface
{
    public function __construct(
        private string $id,
        private string $tenantId,
        private ?string $userId,
        private ?string $roleId,
        private float $amount,
        private string $currency,
        private ?string $transactionType,
        private bool $isActive,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getRoleId(): ?string
    {
        return $this->roleId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withIsActive(bool $isActive): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->userId,
            $this->roleId,
            $this->amount,
            $this->currency,
            $this->transactionType,
            $isActive,
            $this->createdAt,
            new \DateTimeImmutable(),
        );
    }

    public function withAmount(float $amount): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->userId,
            $this->roleId,
            $amount,
            $this->currency,
            $this->transactionType,
            $this->isActive,
            $this->createdAt,
            new \DateTimeImmutable(),
        );
    }

    public function withCurrency(string $currency): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->userId,
            $this->roleId,
            $this->amount,
            $currency,
            $this->transactionType,
            $this->isActive,
            $this->createdAt,
            new \DateTimeImmutable(),
        );
    }
}
