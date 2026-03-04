<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface PeriodOpenedEventInterface
{
    public function getPeriodId(): string;
}
