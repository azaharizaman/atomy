<?php

declare(strict_types=1);

namespace Nexus\Recruitment\ValueObjects;

final readonly class JobCode
{
    public function __construct(
        public string $value,
    ) {}
    
    public function __toString(): string
    {
        return $this->value;
    }
}
