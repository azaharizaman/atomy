<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use DateTimeImmutable;
use DateTimeZone;
use Nexus\Common\Contracts\ClockInterface;

final readonly class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
