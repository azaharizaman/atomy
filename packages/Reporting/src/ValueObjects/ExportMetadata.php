<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

final readonly class ExportMetadata
{
    public function __construct(
        public string $title,
        public ?string $description,
        public \DateTimeImmutable $generatedAt,
        public string $schemaVersion
    ) {}
}
