<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

final readonly class ExportDefinition
{
    /** @param array<string, mixed> $structure @param array<string, mixed> $formatHints */
    public function __construct(
        public ExportMetadata $metadata,
        public array $structure,
        public array $formatHints = []
    ) {}
}
