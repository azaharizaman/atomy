<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Core\HealthChecks;

use Nexus\Monitoring\Contracts\CacheRepositoryInterface;
use Nexus\Monitoring\ValueObjects\HealthCheckResult;

/**
 * CacheHealthCheck
 *
 * Checks cache system availability by performing write/read/delete operations.
 * Verifies cache is both readable and writable.
 *
 * @package Nexus\Monitoring\Core\HealthChecks
 */
final class CacheHealthCheck extends AbstractHealthCheck
{
    public function __construct(
        private readonly CacheRepositoryInterface $cache,
        string $name = 'cache',
        int $priority = 30,
        int $timeout = 2,
        private readonly float $slowResponseThreshold = 0.5,
        ?int $cacheTtl = null // Don't cache cache health checks
    ) {
        parent::__construct($name, $priority, $timeout, $cacheTtl);
    }

    protected function performCheck(): HealthCheckResult
    {
        $testKey = 'monitoring:health_check:' . uniqid('', true);
        $testValue = 'health_check_test_value';
        $startTime = microtime(true);
        
        try {
            // Test write
            $writeSuccess = $this->cache->put($testKey, $testValue, 60);
            
            if (!$writeSuccess) {
                return $this->critical('Cache write operation failed', [
                    'operation' => 'write',
                ]);
            }
            
            // Test read
            $readValue = $this->cache->get($testKey);
            
            if ($readValue !== $testValue) {
                return $this->critical('Cache read operation failed', [
                    'operation' => 'read',
                    'expected' => $testValue,
                    'actual' => $readValue,
                ]);
            }
            
            // Test delete
            $deleteSuccess = $this->cache->forget($testKey);
            
            $operationTime = microtime(true) - $startTime;
            
            if ($operationTime > $this->slowResponseThreshold) {
                return $this->warning('Cache is responding slowly', [
                    'operation_time' => round($operationTime, 4),
                    'threshold' => $this->slowResponseThreshold,
                ]);
            }
            
            return $this->healthy('Cache is operational', [
                'operation_time' => round($operationTime, 4),
                'operations_tested' => ['write', 'read', 'delete'],
            ]);
            
        } catch (\Throwable $e) {
            return $this->offline('Cache is not accessible', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }
    }
}
