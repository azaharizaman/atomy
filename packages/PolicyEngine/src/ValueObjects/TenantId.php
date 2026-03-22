<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\ValueObjects;

final readonly class TenantId
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));
        if ($normalized === '') {
            throw new \InvalidArgumentException('TenantId cannot be empty.');
        }
        $this->value = $normalized;
    }
}
