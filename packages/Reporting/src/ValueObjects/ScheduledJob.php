<?php

declare(strict_types=1);

namespace Nexus\Reporting\ValueObjects;

final readonly class ScheduledJob
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        private string $id,
        private JobType $jobType,
        private string $targetId,
        private array $payload = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getJobType(): JobType
    {
        return $this->jobType;
    }

    public function getTargetId(): string
    {
        return $this->targetId;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
