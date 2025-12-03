<?php

declare(strict_types=1);

namespace Nexus\LeaveManagement\Contracts;

interface AccrualStrategyResolverInterface
{
    public function resolve(string $strategyName): AccrualStrategyInterface;
}
