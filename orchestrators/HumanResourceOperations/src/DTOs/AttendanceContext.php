<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Context DTO aggregating data needed for attendance validation
 */
final readonly class AttendanceContext
{
    public function __construct(
        public string $employeeId,
        public \DateTimeImmutable $timestamp,
        public string $type,
        public ?string $scheduleId,
        public ?\DateTimeImmutable $scheduledStart,
        public ?\DateTimeImmutable $scheduledEnd,
        public ?string $locationId,
        public ?float $latitude,
        public ?float $longitude,
        public ?array $recentAttendance = null,
        public ?array $employeeWorkPattern = null,
        public ?array $metadata = null
    ) {}
}
