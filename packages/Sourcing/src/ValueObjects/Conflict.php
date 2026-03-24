<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

final readonly class Conflict
{
    public function __construct(
        public string $type,
        public string $message,
    ) {
    }
}
