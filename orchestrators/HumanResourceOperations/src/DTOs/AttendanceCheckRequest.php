<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\DTOs;

/**
 * Request DTO for recording attendance check-in/check-out
 */
final readonly class AttendanceCheckRequest
{
    public function __construct(
        public string $employeeId,
        public \DateTimeImmutable $timestamp,
        public string $type, // 'check_in' or 'check_out'
        public ?string $locationId = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $deviceId = null,
        public ?array $metadata = null
    ) {}
}
