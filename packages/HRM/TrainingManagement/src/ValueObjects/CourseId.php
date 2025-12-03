<?php

declare(strict_types=1);

namespace Nexus\TrainingManagement\ValueObjects;

final readonly class CourseId
{
    public function __construct(
        public string $value,
    ) {}
    
    public function __toString(): string
    {
        return $this->value;
    }
}
