<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Core\HealthChecks;

use Nexus\Telemetry\ValueObjects\HealthCheckResult;

/**
 * DatabaseHealthCheck
 *
 * Checks database connectivity and query execution time.
 * Uses a simple SELECT 1 query to verify the connection.
 *
 * @package Nexus\Telemetry\Core\HealthChecks
 */
final class DatabaseHealthCheck extends AbstractHealthCheck
{
    public function __construct(
        private readonly \PDO $connection,
        string $name = 'database',
        int $priority = 10,
        int $timeout = 3,
        private readonly float $slowQueryThreshold = 1.0,
        ?int $cacheTtl = 10
    ) {
        parent::__construct($name, $priority, $timeout, $cacheTtl);
    }

    protected function performCheck(): HealthCheckResult
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->connection->query('SELECT 1');
            $result = $stmt->fetchColumn();
            
            $queryTime = microtime(true) - $startTime;
            
            if ($result !== 1 && $result !== '1') {
                return $this->critical('Database query returned unexpected result', [
                    'expected' => 1,
                    'actual' => $result,
                    'query_time' => round($queryTime, 4),
                ]);
            }
            
            if ($queryTime > $this->slowQueryThreshold) {
                return $this->warning('Database is responding slowly', [
                    'query_time' => round($queryTime, 4),
                    'threshold' => $this->slowQueryThreshold,
                ]);
            }
            
            return $this->healthy('Database is accessible', [
                'query_time' => round($queryTime, 4),
                'driver' => $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME),
            ]);
            
        } catch (\PDOException $e) {
            return $this->offline('Database is not accessible', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);
        }
    }
}
