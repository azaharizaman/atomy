<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\Entities;

final readonly class Evidence
{
    public function __construct(
        public string $id,
        public string $caseId,
        public string $type,
        public string $description,
        public ?string $filePath = null,
        public \DateTimeImmutable $submittedAt = new \DateTimeImmutable(),
        public ?string $submittedBy = null,
    ) {}
}
