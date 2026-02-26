<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Services;

use DateTimeImmutable;
use Nexus\Telemetry\Contracts\HealthCheckInterface;
use Nexus\Telemetry\Contracts\HealthCheckerInterface;
use Nexus\Telemetry\ValueObjects\HealthCheckResult;
use Nexus\Telemetry\ValueObjects\HealthStatus;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Orchestrates health check execution with prioritization, caching, and timeout handling.
 * 
 * Features:
 * - Critical check prioritization (run critical checks first)
 * - Configurable timeout per check with graceful degradation
 * - Optional result caching with TTL
 * - Exception handling with automatic OFFLINE status
 * - Comprehensive logging for all check executions
 * - Supports both HealthCheckInterface and ScheduledHealthCheckInterface
 * 
 * @see HealthCheckerInterface
 */
final class HealthCheckRunner implements HealthCheckerInterface
{
    /** @var array<string, HealthCheckInterface> */
    private array $checks = [];
    
    /** @var array<string, HealthCheckResult> */
    private array $cache = [];
    
    /** @var array<string, int> */
    private array $cacheTimestamps = [];
    
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly int $defaultTimeoutSeconds = 30,
        private readonly bool $enableCaching = false,
        private readonly int $cacheTtlSeconds = 60
    ) {}
    
    public function registerCheck(HealthCheckInterface $check): void
    {
        $name = $check->getName();
        
        if (isset($this->checks[$name])) {
            $this->logger->warning('Health check already registered, skipping duplicate', [
                'check_name' => $name,
            ]);
            return;
        }
        
        $this->checks[$name] = $check;
        
        $this->logger->debug('Health check registered', [
            'check_name' => $name,
            'is_critical' => $check->isCritical(),
        ]);
    }
    
    public function runChecks(): array
    {
        $results = [];
        
        // Sort checks: critical first, then normal
        $sortedChecks = $this->sortChecksByCriticality();
        
        foreach ($sortedChecks as $check) {
            $name = $check->getName();
            
            // Check cache first if enabled
            if ($this->enableCaching && $this->hasCachedResult($name)) {
                $results[$name] = $this->getCachedResult($name);
                continue;
            }
            
            // Execute the health check
            $result = $this->executeCheck($check);
            $results[$name] = $result;
            
            // Cache the result if caching is enabled
            if ($this->enableCaching) {
                $this->cacheResult($name, $result);
            }
            
            // Log the result
            $this->logger->debug('Health check executed', [
                'check_name' => $name,
                'status' => $result->status->value,
                'response_time_ms' => $result->responseTimeMs,
            ]);
        }
        
        return $results;
    }
    
    public function getCheckByName(string $name): ?HealthCheckInterface
    {
        return $this->checks[$name] ?? null;
    }
    
    /**
     * Clear all cached health check results.
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->cacheTimestamps = [];
        
        $this->logger->debug('Health check cache cleared');
    }
    
    /**
     * Execute a single health check with timeout and exception handling.
     */
    private function executeCheck(HealthCheckInterface $check): HealthCheckResult
    {
        $name = $check->getName();
        $startTime = microtime(true);
        
        try {
            // Set timeout using set_time_limit (basic timeout mechanism)
            // Note: In production, use pcntl_alarm or async execution for true timeouts
            $originalTimeLimit = ini_get('max_execution_time');
            set_time_limit($this->defaultTimeoutSeconds);
            
            $result = $check->check();
            
            // Restore original time limit
            set_time_limit((int) $originalTimeLimit);
            
            // Check if execution exceeded timeout threshold
            $executionTime = (microtime(true) - $startTime) * 1000;
            if ($executionTime > ($this->defaultTimeoutSeconds * 1000)) {
                $this->logger->warning('Health check timed out', [
                    'check_name' => $name,
                    'timeout_seconds' => $this->defaultTimeoutSeconds,
                    'actual_time_ms' => $executionTime,
                ]);
                
                return new HealthCheckResult(
                    checkName: $name,
                    status: HealthStatus::DEGRADED,
                    message: sprintf('Check exceeded timeout threshold (%ds)', $this->defaultTimeoutSeconds),
                    responseTimeMs: $executionTime,
                    metadata: ['timeout' => true],
                    checkedAt: new DateTimeImmutable()
                );
            }
            
            return $result;
            
        } catch (Throwable $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $this->logger->error('Health check failed with exception', [
                'check_name' => $name,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            
            return new HealthCheckResult(
                checkName: $name,
                status: HealthStatus::OFFLINE,
                message: sprintf('Check failed: %s', $e->getMessage()),
                responseTimeMs: $executionTime,
                metadata: [
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                ],
                checkedAt: new DateTimeImmutable()
            );
        }
    }
    
    /**
     * Sort health checks with critical checks first.
     *
     * @return array<HealthCheckInterface>
     */
    private function sortChecksByCriticality(): array
    {
        $checks = $this->checks;
        
        usort($checks, function (HealthCheckInterface $a, HealthCheckInterface $b): int {
            // Critical checks first (isCritical() === true)
            if ($a->isCritical() && !$b->isCritical()) {
                return -1;
            }
            if (!$a->isCritical() && $b->isCritical()) {
                return 1;
            }
            return 0;
        });
        
        return $checks;
    }
    
    /**
     * Check if a cached result exists and is still valid.
     */
    private function hasCachedResult(string $checkName): bool
    {
        if (!isset($this->cache[$checkName])) {
            return false;
        }
        
        $cacheAge = time() - $this->cacheTimestamps[$checkName];
        
        return $cacheAge < $this->cacheTtlSeconds;
    }
    
    /**
     * Get cached health check result.
     */
    private function getCachedResult(string $checkName): HealthCheckResult
    {
        return $this->cache[$checkName];
    }
    
    /**
     * Cache a health check result.
     */
    private function cacheResult(string $checkName, HealthCheckResult $result): void
    {
        $this->cache[$checkName] = $result;
        $this->cacheTimestamps[$checkName] = time();
    }
}
