<?php

declare(strict_types=1);

namespace Nexus\ProcurementML\Contracts;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
