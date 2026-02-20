<?php

declare(strict_types=1);

namespace Nexus\Leave\Contracts;

interface AccrualStrategyResolverInterface
{
    public function resolve(string $strategyName): AccrualStrategyInterface;
}
