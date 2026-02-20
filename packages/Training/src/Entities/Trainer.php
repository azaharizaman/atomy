<?php

declare(strict_types=1);

namespace Nexus\Training\Entities;

final readonly class Trainer
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public array $specializations = [],
    ) {}
}
