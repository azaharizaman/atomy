<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

final readonly class ScheduledJob
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public JobType $jobType,
        public string $targetId,
        public array $payload = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }
}
