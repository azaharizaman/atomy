<?php

declare(strict_types=1);

namespace Nexus\PolicyEngine\ValueObjects;

final readonly class Obligation
{
    public function __construct(public string $key, public mixed $value)
    {
        if (trim($this->key) === '') {
            throw new \InvalidArgumentException('Obligation key cannot be empty.');
        }
    }
}
