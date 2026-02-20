<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

interface CashConcentrationInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getName(): string;

    public function getSourceAccountIds(): array;

    public function getTargetAccountId(): string;

    public function getSweepThreshold(): Money;

    public function getSweepAmount(): Money;

    public function getScheduledTime(): ?string;

    public function getLastExecutedAt(): ?DateTimeImmutable;

    public function getNextExecutionAt(): ?DateTimeImmutable;

    public function isEnabled(): bool;

    public function getCreatedAt(): DateTimeImmutable;

    public function getUpdatedAt(): DateTimeImmutable;
}
