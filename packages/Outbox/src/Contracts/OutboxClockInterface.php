<?php

declare(strict_types=1);

namespace Nexus\Outbox\Contracts;

use DateTimeImmutable;

interface OutboxClockInterface
{
    public function now(): DateTimeImmutable;
}
