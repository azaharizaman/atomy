<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Core\HealthChecks;

use Nexus\Monitoring\ValueObjects\HealthCheckResult;

/**
 * DiskSpaceHealthCheck
 *
 * Monitors disk space usage and alerts when thresholds are exceeded.
 * Checks both percentage and absolute free space.
 *
 * @package Nexus\Monitoring\Core\HealthChecks
 */
final class DiskSpaceHealthCheck extends AbstractHealthCheck
{
    public function __construct(
        private readonly string $path = '/',
        string $name = 'disk_space',
        int $priority = 40,
        int $timeout = 1,
        private readonly float $warningThreshold = 0.80,
        private readonly float $criticalThreshold = 0.90,
        private readonly ?int $minFreeGb = null,
        ?int $cacheTtl = 60
    ) {
        parent::__construct($name, $priority, $timeout, $cacheTtl);
    }

    protected function performCheck(): HealthCheckResult
    {
        if (!is_dir($this->path) && !is_file($this->path)) {
            return $this->critical('Path does not exist', [
                'path' => $this->path,
            ]);
        }
        
        $totalSpace = disk_total_space($this->path);
        $freeSpace = disk_free_space($this->path);
        
        if ($totalSpace === false || $freeSpace === false) {
            return $this->critical('Unable to determine disk space', [
                'path' => $this->path,
            ]);
        }
        
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercentage = $usedSpace / $totalSpace;
        $freeGb = $freeSpace / (1024 ** 3);
        
        $metadata = [
            'path' => $this->path,
            'total_gb' => round($totalSpace / (1024 ** 3), 2),
            'used_gb' => round($usedSpace / (1024 ** 3), 2),
            'free_gb' => round($freeGb, 2),
            'usage_percentage' => round($usagePercentage * 100, 2),
        ];
        
        // Check minimum free space in GB if configured
        if ($this->minFreeGb !== null && $freeGb < $this->minFreeGb) {
            return $this->critical(
                sprintf('Free disk space below minimum (%d GB)', $this->minFreeGb),
                $metadata
            );
        }
        
        // Check percentage thresholds
        if ($usagePercentage >= $this->criticalThreshold) {
            return $this->critical(
                sprintf('Disk usage critical (%.1f%%)', $usagePercentage * 100),
                $metadata
            );
        }
        
        if ($usagePercentage >= $this->warningThreshold) {
            return $this->warning(
                sprintf('Disk usage high (%.1f%%)', $usagePercentage * 100),
                $metadata
            );
        }
        
        return $this->healthy('Disk space is adequate', $metadata);
    }
}
