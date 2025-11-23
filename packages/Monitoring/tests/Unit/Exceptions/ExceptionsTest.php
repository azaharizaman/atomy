<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\Exceptions;

use Nexus\Monitoring\Exceptions\AlertDispatchException;
use Nexus\Monitoring\Exceptions\CardinalityLimitExceededException;
use Nexus\Monitoring\Exceptions\HealthCheckFailedException;
use Nexus\Monitoring\Exceptions\InvalidMetricException;
use Nexus\Monitoring\Exceptions\MonitoringException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MonitoringException::class)]
#[CoversClass(CardinalityLimitExceededException::class)]
#[CoversClass(HealthCheckFailedException::class)]
#[CoversClass(InvalidMetricException::class)]
#[CoversClass(AlertDispatchException::class)]
#[Group('monitoring')]
#[Group('exceptions')]
final class ExceptionsTest extends TestCase
{
    #[Test]
    public function monitoring_exception_is_base_exception(): void
    {
        $exception = new MonitoringException('Test error');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('Test error', $exception->getMessage());
    }

    #[Test]
    public function cardinality_limit_exceeded_exception_for_global_limit(): void
    {
        $exception = CardinalityLimitExceededException::globalLimit(1000, 1500);

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('1000', $exception->getMessage());
        $this->assertStringContainsString('1500', $exception->getMessage());
        $this->assertStringContainsString('global', $exception->getMessage());
    }

    #[Test]
    public function cardinality_limit_exceeded_exception_for_metric_limit(): void
    {
        $exception = CardinalityLimitExceededException::metricLimit('api.requests', 100, 150);

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('api.requests', $exception->getMessage());
        $this->assertStringContainsString('100', $exception->getMessage());
        $this->assertStringContainsString('150', $exception->getMessage());
    }

    #[Test]
    public function health_check_failed_exception_for_check(): void
    {
        $exception = HealthCheckFailedException::forCheck('database', 'Connection refused');

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('database', $exception->getMessage());
        $this->assertStringContainsString('Connection refused', $exception->getMessage());
    }

    #[Test]
    public function health_check_failed_exception_for_timeout(): void
    {
        $exception = HealthCheckFailedException::timeout('external-api', 5);

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('external-api', $exception->getMessage());
        $this->assertStringContainsString('5 seconds', $exception->getMessage());
        $this->assertStringContainsString('timed out', $exception->getMessage());
    }

    #[Test]
    public function invalid_metric_exception_for_invalid_name(): void
    {
        $exception = InvalidMetricException::invalidName('Invalid-Name');

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('Invalid-Name', $exception->getMessage());
        $this->assertStringContainsString('pattern', $exception->getMessage());
    }

    #[Test]
    public function invalid_metric_exception_for_invalid_value(): void
    {
        $exception = InvalidMetricException::invalidValue('memory_usage', 'not-a-number');

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('memory_usage', $exception->getMessage());
        $this->assertStringContainsString('string', $exception->getMessage());
        $this->assertStringContainsString('float', $exception->getMessage());
    }

    #[Test]
    public function invalid_metric_exception_for_invalid_tags(): void
    {
        $exception = InvalidMetricException::invalidTags('api.requests', 'tag values must be scalar');

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('api.requests', $exception->getMessage());
        $this->assertStringContainsString('scalar', $exception->getMessage());
    }

    #[Test]
    public function alert_dispatch_exception_for_failed_dispatch(): void
    {
        $previous = new \RuntimeException('SMTP error');
        $exception = AlertDispatchException::dispatchFailed('email', 'SMTP connection failed', $previous);

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('email', $exception->getMessage());
        $this->assertStringContainsString('SMTP connection failed', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function alert_dispatch_exception_for_no_channels(): void
    {
        $exception = AlertDispatchException::noChannelsConfigured();

        $this->assertInstanceOf(MonitoringException::class, $exception);
        $this->assertStringContainsString('No alert channels', $exception->getMessage());
    }

    #[Test]
    public function exceptions_preserve_code_and_previous(): void
    {
        $previous = new \Exception('Original error');
        $exception = new MonitoringException('Monitoring error', ['key' => 'value'], 500, $previous);

        $this->assertSame(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(['key' => 'value'], $exception->getContext());
    }
}
