<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

/**
 * Authorization Limit Interface
 */
interface AuthorizationLimitInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getUserId(): ?string;

    public function getRoleId(): ?string;

    public function getAmount(): float;

    public function getCurrency(): string;

    public function getTransactionType(): ?string;

    public function isActive(): bool;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;
}
