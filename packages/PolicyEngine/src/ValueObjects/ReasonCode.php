<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\ValueObjects;

final readonly class ReasonCode
{
    public function __construct(public string $value)
    {
        if (trim($this->value) === '') {
            throw new \InvalidArgumentException('ReasonCode cannot be empty.');
        }
    }
}
