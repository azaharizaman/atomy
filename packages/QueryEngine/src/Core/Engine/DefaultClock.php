<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Core\Engine;

use Nexus\QueryEngine\Contracts\ClockInterface;

final readonly class DefaultClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}