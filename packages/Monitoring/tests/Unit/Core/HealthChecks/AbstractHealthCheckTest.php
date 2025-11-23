<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\Core\HealthChecks;

use Nexus\Monitoring\Core\HealthChecks\AbstractHealthCheck;
use Nexus\Monitoring\ValueObjects\HealthCheckResult;
use Nexus\Monitoring\ValueObjects\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractHealthCheck::class)]
#[Group('monitoring')]
#[Group('health-checks')]
class AbstractHealthCheckTest extends TestCase
{
    #[Test]
    public function it_executes_health_check_successfully(): void
    {
        $check = new class('test_check') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy('All systems operational');
            }
        };

        $result = $check->execute();

        $this->assertSame('test_check', $result->checkName);
        $this->assertSame(HealthStatus::HEALTHY, $result->status);
        $this->assertSame('All systems operational', $result->message);
    }

    #[Test]
    public function it_catches_exceptions_and_returns_critical_status(): void
    {
        $check = new class('failing_check') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                throw new \RuntimeException('Something went wrong');
            }
        };

        $result = $check->execute();

        $this->assertSame('failing_check', $result->checkName);
        $this->assertSame(HealthStatus::CRITICAL, $result->status);
        $this->assertStringContainsString('Health check failed', $result->message);
        $this->assertStringContainsString('Something went wrong', $result->message);
        $this->assertArrayHasKey('exception', $result->metadata);
        $this->assertSame('RuntimeException', $result->metadata['exception']);
    }

    #[Test]
    public function it_returns_configured_name(): void
    {
        $check = new class('custom_name') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy();
            }
        };

        $this->assertSame('custom_name', $check->getName());
    }

    #[Test]
    public function it_returns_default_priority(): void
    {
        $check = new class('test') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy();
            }
        };

        $this->assertSame(50, $check->getPriority());
    }

    #[Test]
    public function it_returns_custom_priority(): void
    {
        $check = new class('test', priority: 10) extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy();
            }
        };

        $this->assertSame(10, $check->getPriority());
    }

    #[Test]
    public function it_returns_default_timeout(): void
    {
        $check = new class('test') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy();
            }
        };

        $this->assertSame(5, $check->getTimeout());
    }

    #[Test]
    public function it_returns_custom_timeout(): void
    {
        $check = new class('test', timeout: 10) extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy();
            }
        };

        $this->assertSame(10, $check->getTimeout());
    }

    #[Test]
    public function it_returns_null_cache_ttl_by_default(): void
    {
        $check = new class('test') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy();
            }
        };

        $this->assertNull($check->getCacheTtl());
    }

    #[Test]
    public function it_returns_custom_cache_ttl(): void
    {
        $check = new class('test', cacheTtl: 60) extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy();
            }
        };

        $this->assertSame(60, $check->getCacheTtl());
    }

    #[Test]
    public function it_creates_healthy_result_with_helper(): void
    {
        $check = new class('test') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->healthy('Everything is fine', ['detail' => 'value']);
            }
        };

        $result = $check->execute();

        $this->assertSame(HealthStatus::HEALTHY, $result->status);
        $this->assertSame('Everything is fine', $result->message);
        $this->assertSame(['detail' => 'value'], $result->metadata);
    }

    #[Test]
    public function it_creates_warning_result_with_helper(): void
    {
        $check = new class('test') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->warning('Minor issue detected', ['severity' => 'low']);
            }
        };

        $result = $check->execute();

        $this->assertSame(HealthStatus::WARNING, $result->status);
        $this->assertSame('Minor issue detected', $result->message);
    }

    #[Test]
    public function it_creates_degraded_result_with_helper(): void
    {
        $check = new class('test') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->degraded('Performance degraded');
            }
        };

        $result = $check->execute();

        $this->assertSame(HealthStatus::DEGRADED, $result->status);
    }

    #[Test]
    public function it_creates_critical_result_with_helper(): void
    {
        $check = new class('test') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->critical('Critical failure');
            }
        };

        $result = $check->execute();

        $this->assertSame(HealthStatus::CRITICAL, $result->status);
    }

    #[Test]
    public function it_creates_offline_result_with_helper(): void
    {
        $check = new class('test') extends AbstractHealthCheck {
            protected function performCheck(): HealthCheckResult
            {
                return $this->offline('Service unavailable');
            }
        };

        $result = $check->execute();

        $this->assertSame(HealthStatus::OFFLINE, $result->status);
    }
}
