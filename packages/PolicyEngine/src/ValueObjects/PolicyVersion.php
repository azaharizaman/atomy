<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\ValueObjects;

final readonly class PolicyVersion
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new \InvalidArgumentException('PolicyVersion cannot be empty.');
        }
        $this->value = $normalized;
    }
}
