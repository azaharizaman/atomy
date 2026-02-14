<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\UseCases\Attendance;

use Nexus\HumanResourceOperations\DataProviders\AttendanceDataProvider;
use Nexus\HumanResourceOperations\Services\AttendanceRuleRegistry;

final readonly class DetectAttendanceAnomaliesHandler
{
    public function __construct(
        private AttendanceDataProvider $dataProvider,
        private AttendanceRuleRegistry $ruleRegistry
    ) {}

    /**
     * @return array<array{rule:string,severity:string,message:string}>
     */
    public function handle(
        string $employeeId,
        \DateTimeImmutable $timestamp,
        string $type,
        ?string $locationId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): array {
        $context = $this->dataProvider->getAttendanceContext(
            employeeId: $employeeId,
            timestamp: $timestamp,
            type: $type,
            locationId: $locationId,
            latitude: $latitude,
            longitude: $longitude
        );

        return $this->ruleRegistry->getAnomalies($this->ruleRegistry->validate($context));
    }
}
