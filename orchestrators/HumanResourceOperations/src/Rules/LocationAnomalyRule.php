<?php

declare(strict_types=1);

namespace Nexus\HumanResourceOperations\Rules;

use Nexus\HumanResourceOperations\Contracts\AttendanceRuleInterface;
use Nexus\HumanResourceOperations\DTOs\AttendanceContext;
use Nexus\HumanResourceOperations\DTOs\RuleCheckResult;

/**
 * Detects location anomalies (check-in from unusual location)
 */
final readonly class LocationAnomalyRule implements AttendanceRuleInterface
{
    private const float MAX_DISTANCE_KM = 5.0; // 5km from office

    public function __construct(
        private ?float $officeLatitude = null,
        private ?float $officeLongitude = null
    ) {}

    public function check(AttendanceContext $context): RuleCheckResult
    {
        // Skip if location tracking disabled or office location not configured
        if (!$this->officeLatitude || !$this->officeLongitude) {
            return new RuleCheckResult(
                passed: true,
                ruleName: $this->getName(),
                message: 'Location tracking not configured'
            );
        }
        
        // Skip if employee location not provided
        if (!$context->latitude || !$context->longitude) {
            return new RuleCheckResult(
                passed: true,
                ruleName: $this->getName(),
                message: 'Employee location not provided'
            );
        }
        
        $distance = $this->calculateDistance(
            $this->officeLatitude,
            $this->officeLongitude,
            $context->latitude,
            $context->longitude
        );
        
        $passed = $distance <= self::MAX_DISTANCE_KM;
        
        $message = $passed
            ? sprintf('Check-in within acceptable range (%.2f km)', $distance)
            : sprintf(
                'Warning: Check-in from unusual location (%.2f km from office, threshold: %.1f km)',
                $distance,
                self::MAX_DISTANCE_KM
            );
        
        return new RuleCheckResult(
            passed: $passed,
            ruleName: $this->getName(),
            message: $message,
            metadata: [
                'distance_km' => $distance,
                'threshold_km' => self::MAX_DISTANCE_KM
            ]
        );
    }

    public function getName(): string
    {
        return 'Location Anomaly Rule';
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
