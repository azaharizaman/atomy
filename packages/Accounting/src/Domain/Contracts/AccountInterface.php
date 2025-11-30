<?php

declare(strict_types=1);

namespace Nexus\Accounting\Domain\Contracts;

use Nexus\Accounting\Domain\ValueObjects\AccountCode;
use Nexus\Accounting\Domain\ValueObjects\AccountType;

/**
 * Domain contract for Chart of Accounts entries.
 */
interface AccountInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getCode(): AccountCode;

    public function getName(): string;

    public function getType(): AccountType;

    public function getParentId(): ?string;

    public function getLevel(): int;

    public function isActive(): bool;

    public function getDescription(): ?string;

    public function getMetadata(): array;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;
}
