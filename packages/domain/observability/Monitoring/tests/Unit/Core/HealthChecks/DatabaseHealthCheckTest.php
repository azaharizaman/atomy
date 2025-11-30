<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\Core\HealthChecks;

use Nexus\Monitoring\Core\HealthChecks\DatabaseHealthCheck;
use Nexus\Monitoring\ValueObjects\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseHealthCheck::class)]
#[Group('monitoring')]
#[Group('health-checks')]
class DatabaseHealthCheckTest extends TestCase
{
    #[Test]
    public function it_returns_healthy_when_database_is_accessible(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(1);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('query')->with('SELECT 1')->willReturn($stmt);
        $pdo->method('getAttribute')->with(\PDO::ATTR_DRIVER_NAME)->willReturn('mysql');

        $check = new DatabaseHealthCheck($pdo);
        $result = $check->execute();

        $this->assertSame(HealthStatus::HEALTHY, $result->status);
        $this->assertStringContainsString('accessible', $result->message);
        $this->assertArrayHasKey('query_time', $result->metadata);
        $this->assertArrayHasKey('driver', $result->metadata);
    }

    #[Test]
    public function it_returns_warning_when_database_is_slow(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(1);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('query')->willReturnCallback(function () use ($stmt) {
            usleep(1100000); // Sleep 1.1 seconds
            return $stmt;
        });

        $check = new DatabaseHealthCheck($pdo, slowQueryThreshold: 1.0);
        $result = $check->execute();

        $this->assertSame(HealthStatus::WARNING, $result->status);
        $this->assertStringContainsString('slowly', $result->message);
        $this->assertGreaterThan(1.0, $result->metadata['query_time']);
    }

    #[Test]
    public function it_returns_critical_when_query_returns_unexpected_result(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(99);

        $pdo = $this->createMock(\PDO::class);
        $pdo->method('query')->willReturn($stmt);

        $check = new DatabaseHealthCheck($pdo);
        $result = $check->execute();

        $this->assertSame(HealthStatus::CRITICAL, $result->status);
        $this->assertStringContainsString('unexpected result', $result->message);
    }

    #[Test]
    public function it_returns_offline_when_connection_fails(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('query')->willThrowException(new \PDOException('Connection refused'));

        $check = new DatabaseHealthCheck($pdo);
        $result = $check->execute();

        $this->assertSame(HealthStatus::OFFLINE, $result->status);
        $this->assertStringContainsString('not accessible', $result->message);
        $this->assertArrayHasKey('error', $result->metadata);
    }

    #[Test]
    public function it_uses_custom_name(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetchColumn')->willReturn(1);
        $pdo->method('query')->willReturn($stmt);

        $check = new DatabaseHealthCheck($pdo, name: 'primary_db');
        $result = $check->execute();

        $this->assertSame('primary_db', $result->checkName);
    }

    #[Test]
    public function it_has_high_priority_by_default(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $check = new DatabaseHealthCheck($pdo);

        $this->assertSame(10, $check->getPriority());
    }
}
