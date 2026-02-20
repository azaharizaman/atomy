<?php

declare(strict_types=1);

namespace Nexus\Treasury\Contracts\Integration;

use DateTimeImmutable;

interface PeriodValidationInterface
{
    public function isPostingAllowed(DateTimeImmutable $date): bool;

    public function getCurrentPeriod(): ?array;

    public function getOpenPeriodForDate(DateTimeImmutable $date): ?array;

    public function validateDate(DateTimeImmutable $date): bool;
}
