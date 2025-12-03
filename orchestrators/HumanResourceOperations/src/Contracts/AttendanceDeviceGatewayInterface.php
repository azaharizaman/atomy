<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Contracts;

interface AttendanceDeviceGatewayInterface
{
    public function recordCheckIn(string $employeeId, \DateTimeImmutable $timestamp, array $metadata = []): void;
    
    public function recordCheckOut(string $employeeId, \DateTimeImmutable $timestamp, array $metadata = []): void;
}
