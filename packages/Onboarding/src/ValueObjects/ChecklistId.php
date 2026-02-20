<?php

declare(strict_types=1);

namespace Nexus\Onboarding\ValueObjects;

final readonly class ChecklistId
{
    public function __construct(
        public string $value,
    ) {}
    
    public function __toString(): string
    {
        return $this->value;
    }
}
