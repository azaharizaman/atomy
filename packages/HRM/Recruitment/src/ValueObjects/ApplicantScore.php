<?php

declare(strict_types=1);

namespace Nexus\Recruitment\ValueObjects;

final readonly class ApplicantScore
{
    public function __construct(
        public float $value,
    ) {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Score must be between 0 and 100');
        }
    }
}
