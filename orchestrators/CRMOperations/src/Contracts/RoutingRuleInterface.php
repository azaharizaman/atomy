<?php

declare(strict_types=1);

namespace Nexus\CRMOperations\Contracts;

interface RoutingRuleInterface
{
    public function evaluate(array $leadData): bool;

    public function getName(): string;

    public function getPriority(): int;
}
