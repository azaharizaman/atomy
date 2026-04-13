<?php

declare(strict_types=1);

namespace Nexus\QueryEngine\Contracts;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}