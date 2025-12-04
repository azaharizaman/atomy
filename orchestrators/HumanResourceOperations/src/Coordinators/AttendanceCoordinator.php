<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Coordinators;

use Nexus\HumanResourceOperations\DataProviders\AttendanceDataProvider;
use Nexus\HumanResourceOperations\DTOs\AttendanceCheckRequest;
use Nexus\HumanResourceOperations\DTOs\AttendanceCheckResult;
use Nexus\HumanResourceOperations\Services\AttendanceRuleRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates attendance check-in/check-out with anomaly detection
 * 
 * Following Advanced Orchestrator Pattern:
 * - Coordinators are Traffic Cops (orchestrate flow)
 * - DataProviders aggregate data
 * - Rules validate in isolation
 */
final readonly class AttendanceCoordinator
{
    public function __construct(
        private AttendanceDataProvider $dataProvider,
        private AttendanceRuleRegistry $ruleRegistry,
        // TODO: Inject AttendanceManager from Nexus\Hrm package
        // private AttendanceManagerInterface $attendanceManager,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * Process attendance check (in/out) with anomaly detection
     */
    public function processAttendanceCheck(AttendanceCheckRequest $request): AttendanceCheckResult
    {
        $this->logger->info('Processing attendance check', [
            'employee_id' => $request->employeeId,
            'type' => $request->type,
            'timestamp' => $request->timestamp->format('Y-m-d H:i:s')
        ]);

        // Step 1: Aggregate data (Traffic Cop delegates to Data Provider)
        $context = $this->dataProvider->getAttendanceContext(
            employeeId: $request->employeeId,
            timestamp: $request->timestamp,
            type: $request->type,
            locationId: $request->locationId,
            latitude: $request->latitude,
            longitude: $request->longitude
        );

        // Step 2: Validate via Rules (Traffic Cop delegates to Rule Registry)
        $validationResults = $this->ruleRegistry->validate($context);

        // Step 3: Extract anomalies
        $anomalies = $this->ruleRegistry->getAnomalies($validationResults);

        // Step 4: Record attendance (Traffic Cop delegates to Service)
        // TODO: Call Nexus\Hrm AttendanceManager to persist record
        $attendanceId = $this->recordAttendance($request);

        $this->logger->info('Attendance check processed', [
            'attendance_id' => $attendanceId,
            'has_anomalies' => !empty($anomalies),
            'anomaly_count' => count($anomalies)
        ]);

        return new AttendanceCheckResult(
            success: true,
            attendanceId: $attendanceId,
            recordedAt: new \DateTimeImmutable(),
            anomalies: !empty($anomalies) ? $anomalies : null,
            message: empty($anomalies) 
                ? 'Attendance recorded successfully'
                : sprintf('Attendance recorded with %d anomaly(ies) detected', count($anomalies))
        );
    }

    /**
     * @skeleton
     */
    private function recordAttendance(AttendanceCheckRequest $request): string
    {
        // TODO: Implement via Nexus\Hrm AttendanceManager
        // This should create attendance record in database
        
        // Generate a cryptographically secure UUIDv4 for attendanceId
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // set version to 4
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // set variant to RFC 4122
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'ATT-' . $uuid;
    }
}
