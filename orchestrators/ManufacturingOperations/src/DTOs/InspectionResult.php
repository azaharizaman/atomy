<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\DTOs;

readonly class InspectionResult
{
    /**
     * @param array<int, mixed> $defects
     */
    public function __construct(
        public string $inspectionId,
        public bool $passed,
        public string $inspectorId,
        public \DateTimeImmutable $inspectedAt,
        public ?string $notes = null,
        public array $defects = [],
    ) {}
}
