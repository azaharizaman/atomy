<?php

declare(strict_types=1);

namespace Nexus\Laravel\Idempotency\Clock;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;

final class LaravelIdempotencyClock implements IdempotencyClockInterface
{
    public function now(): DateTimeImmutable
    {
        return CarbonImmutable::now();
    }
}
