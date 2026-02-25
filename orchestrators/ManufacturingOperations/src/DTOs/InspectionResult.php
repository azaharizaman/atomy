<?php

declare(strict_types=1);

namespace Nexus\Orchestrators\ManufacturingOperations\DTOs;

readonly class InspectionResult
{
    public function __construct(
        public string $inspectionId,
        public bool $passed,
        public string $inspectorId,
        public \DateTimeImmutable $inspectedAt,
        public ?string $notes = null,
        public array $defects = [],
    ) {}
}
