<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Contracts;

use DateTimeImmutable;

interface IdempotencyClockInterface
{
    public function now(): DateTimeImmutable;
}
