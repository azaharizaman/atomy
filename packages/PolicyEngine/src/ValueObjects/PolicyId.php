<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\ValueObjects;

final readonly class PolicyId
{
    public function __construct(public string $value)
    {
        if (trim($this->value) === '') {
            throw new \InvalidArgumentException('PolicyId cannot be empty.');
        }
    }
}
