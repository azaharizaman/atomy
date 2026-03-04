<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\DTOs;

final readonly class DataOnboardingResult
{
    /**
     * @param array<int, string> $warnings
     * @param array<string, mixed> $details
     */
    public function __construct(
        public string $taskId,
        public int $recordsProcessed,
        public int $recordsFailed,
        public array $warnings,
        public array $details = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'records_processed' => $this->recordsProcessed,
            'records_failed' => $this->recordsFailed,
            'warnings' => $this->warnings,
            'details' => $this->details,
        ];
    }
}
