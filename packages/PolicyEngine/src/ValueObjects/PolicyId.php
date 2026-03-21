<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\ValueObjects;

final readonly class PolicyId
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new \InvalidArgumentException('PolicyId cannot be empty.');
        }
        $this->value = $normalized;
    }
}
