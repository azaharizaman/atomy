<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\ValueObjects;

final readonly class CaseNumber
{
    public function __construct(
        public string $value,
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Case number cannot be empty');
        }
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
}
