<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\ValueObjects;

final readonly class PolicyVersion
{
    public function __construct(public string $value)
    {
        if (trim($this->value) === '') {
            throw new \InvalidArgumentException('PolicyVersion cannot be empty.');
        }
    }
}
