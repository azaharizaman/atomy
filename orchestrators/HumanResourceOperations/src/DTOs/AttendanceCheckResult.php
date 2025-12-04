<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Result DTO for attendance recording operations
 */
final readonly class AttendanceCheckResult
{
    public function __construct(
        public bool $success,
        public string $attendanceId,
        public \DateTimeImmutable $recordedAt,
        public ?array $anomalies = null,
        public ?string $message = null
    ) {}

    public function hasAnomalies(): bool
    {
        return !empty($this->anomalies);
    }
}
