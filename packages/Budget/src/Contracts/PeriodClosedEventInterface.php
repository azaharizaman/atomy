<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PeriodClosedEventInterface
{
    public function getPeriodId(): string;
}
