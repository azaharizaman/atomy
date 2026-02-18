<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Rules;

interface RuleInterface
{
    public function check(object $context): RuleResult;

    public function getName(): string;
}
