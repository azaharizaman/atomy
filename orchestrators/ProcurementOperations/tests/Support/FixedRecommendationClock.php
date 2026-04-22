<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Support;

use DateTimeImmutable;
use Nexus\Common\Contracts\ClockInterface;

final readonly class FixedRecommendationClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-04-22T00:00:00Z');
    }
}
