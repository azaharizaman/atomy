<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Core\HealthChecks;

use Nexus\Telemetry\ValueObjects\HealthCheckResult;

/**
 * MemoryHealthCheck
 *
 * Monitors PHP memory usage and alerts when thresholds are exceeded.
 * Checks both current usage and memory limit.
 *
 * @package Nexus\Telemetry\Core\HealthChecks
 */
final class MemoryHealthCheck extends AbstractHealthCheck
{
    public function __construct(
        string $name = 'memory',
        int $priority = 40,
        int $timeout = 1,
        private readonly float $warningThreshold = 0.75,
        private readonly float $criticalThreshold = 0.90,
        ?int $cacheTtl = 30
    ) {
        parent::__construct($name, $priority, $timeout, $cacheTtl);
    }

    protected function performCheck(): HealthCheckResult
    {
        $memoryLimit = $this->getMemoryLimit();
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        if ($memoryLimit === -1) {
            // No memory limit set
            return $this->healthy('No memory limit configured', [
                'current_mb' => round($memoryUsage / (1024 ** 2), 2),
                'peak_mb' => round($memoryPeak / (1024 ** 2), 2),
                'limit' => 'unlimited',
            ]);
        }
        
        $usagePercentage = $memoryUsage / $memoryLimit;
        $peakPercentage = $memoryPeak / $memoryLimit;
        
        $metadata = [
            'current_mb' => round($memoryUsage / (1024 ** 2), 2),
            'peak_mb' => round($memoryPeak / (1024 ** 2), 2),
            'limit_mb' => round($memoryLimit / (1024 ** 2), 2),
            'usage_percentage' => round($usagePercentage * 100, 2),
            'peak_percentage' => round($peakPercentage * 100, 2),
        ];
        
        if ($usagePercentage >= $this->criticalThreshold) {
            return $this->critical(
                sprintf('Memory usage critical (%.1f%%)', $usagePercentage * 100),
                $metadata
            );
        }
        
        if ($usagePercentage >= $this->warningThreshold) {
            return $this->warning(
                sprintf('Memory usage high (%.1f%%)', $usagePercentage * 100),
                $metadata
            );
        }
        
        return $this->healthy('Memory usage is normal', $metadata);
    }

    /**
     * Get the PHP memory limit in bytes.
     * Returns -1 if unlimited.
     */
    private function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === false || $memoryLimit === '' || $memoryLimit === '-1') {
            return -1;
        }
        
        return $this->convertToBytes($memoryLimit);
    }

    /**
     * Convert PHP ini memory notation to bytes.
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;
        
        return match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
