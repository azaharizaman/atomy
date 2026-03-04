<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PeriodContextInterface
{
    public function getId(): string;

    public function getStartDate(): \DateTimeImmutable;

    public function getEndDate(): \DateTimeImmutable;

    public function isClosed(): bool;
}
