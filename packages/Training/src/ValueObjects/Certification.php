<?php

declare(strict_types=1);

namespace Nexus\Training\ValueObjects;

final readonly class Certification
{
    public function __construct(
        public string $name,
        public \DateTimeImmutable $issuedAt,
        public ?\DateTimeImmutable $expiresAt = null,
    ) {}
}
