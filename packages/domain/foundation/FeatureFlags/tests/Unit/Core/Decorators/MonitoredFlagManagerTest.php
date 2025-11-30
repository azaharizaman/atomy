<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\Core\Decorators;

use Nexus\FeatureFlags\Contracts\FeatureFlagManagerInterface;
use Nexus\FeatureFlags\Core\Decorators\MonitoredFlagManager;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use PHPUnit\Framework\TestCase;

final class MonitoredFlagManagerTest extends TestCase
{
    // ========================================
    // Basic Delegation Tests
    // ========================================

    public function test_isEnabled_delegates_to_inner_manager(): void
    {
        $inner = $this->createMock(FeatureFlagManagerInterface::class);
        $inner->expects($this->once())
            ->method('isEnabled')
            ->with('test.flag', [], false)
            ->willReturn(true);

        $monitored = new MonitoredFlagManager($inner, null);

        $result = $monitored->isEnabled('test.flag');

        $this->assertTrue($result);
    }

    public function test_isDisabled_delegates_to_inner_manager(): void
    {
        $inner = $this->createMock(FeatureFlagManagerInterface::class);
        $inner->expects($this->once())
            ->method('isEnabled')
            ->with('test.flag', [], false)
            ->willReturn(false);

        $monitored = new MonitoredFlagManager($inner, null);

        $result = $monitored->isDisabled('test.flag');

        $this->assertTrue($result);
    }

    public function test_evaluateMany_delegates_to_inner_manager(): void
    {
        $inner = $this->createMock(FeatureFlagManagerInterface::class);
        $inner->expects($this->once())
            ->method('evaluateMany')
            ->with(['flag.one', 'flag.two'], [])
            ->willReturn(['flag.one' => true, 'flag.two' => false]);

        $monitored = new MonitoredFlagManager($inner, null);

        $results = $monitored->evaluateMany(['flag.one', 'flag.two']);

        $this->assertSame(['flag.one' => true, 'flag.two' => false], $results);
    }

    // ========================================
    // Metrics Tracking Tests (With Telemetry)
    // ========================================

    public function test_isEnabled_tracks_timing_metric(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('isEnabled')->willReturn(true);

        $telemetry = $this->createMock(\stdClass::class);
        $telemetry->expects($this->once())
            ->method('timing')
            ->with(
                'flag_evaluation_duration_ms',
                $this->isType('float'),
                ['flag_name' => 'test.flag', 'result' => 'true']
            );

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $monitored->isEnabled('test.flag');
    }

    public function test_isEnabled_tracks_counter_metric(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('isEnabled')->willReturn(false);

        $telemetry = $this->createMock(\stdClass::class);
        $telemetry->expects($this->once())
            ->method('increment')
            ->with(
                'flag_evaluation_total',
                1,
                ['flag_name' => 'test.flag', 'result' => 'false']
            );

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $monitored->isEnabled('test.flag');
    }

    public function test_isEnabled_tracks_error_metric_when_inner_throws(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('isEnabled')->willThrowException(new \RuntimeException('Evaluation failed'));

        $telemetry = $this->createMock(\stdClass::class);
        $telemetry->expects($this->once())
            ->method('increment')
            ->with(
                'flag_evaluation_errors_total',
                1,
                ['flag_name' => 'test.flag', 'error_type' => \RuntimeException::class]
            );

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $this->expectException(\RuntimeException::class);
        $monitored->isEnabled('test.flag');
    }

    public function test_evaluateMany_tracks_bulk_timing_metric(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('evaluateMany')->willReturn([
            'flag.one' => true,
            'flag.two' => false,
        ]);

        $telemetry = $this->createMock(\stdClass::class);
        $telemetry->expects($this->once())
            ->method('timing')
            ->with(
                'bulk_evaluation_duration_ms',
                $this->isType('float'),
                ['flag_count' => '2']
            );

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $monitored->evaluateMany(['flag.one', 'flag.two']);
    }

    public function test_evaluateMany_tracks_bulk_counter_metric(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('evaluateMany')->willReturn([
            'flag.one' => true,
            'flag.two' => false,
            'flag.three' => true,
        ]);

        $telemetry = $this->createMock(\stdClass::class);
        $telemetry->expects($this->once())
            ->method('increment')
            ->with(
                'bulk_evaluation_total',
                1,
                ['flag_count' => '3']
            );

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $monitored->evaluateMany(['flag.one', 'flag.two', 'flag.three']);
    }

    public function test_evaluateMany_tracks_result_distribution_gauge(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('evaluateMany')->willReturn([
            'flag.one' => true,
            'flag.two' => false,
            'flag.three' => true,
            'flag.four' => true,
        ]);

        $telemetry = $this->createMock(\stdClass::class);
        $telemetry->expects($this->exactly(2))
            ->method('gauge')
            ->withConsecutive(
                ['bulk_evaluation_true_count', 3.0], // 3 true
                ['bulk_evaluation_false_count', 1.0] // 1 false
            );

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $monitored->evaluateMany(['flag.one', 'flag.two', 'flag.three', 'flag.four']);
    }

    public function test_evaluateMany_tracks_error_metric_when_inner_throws(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('evaluateMany')->willThrowException(new \RuntimeException('Bulk evaluation failed'));

        $telemetry = $this->createMock(\stdClass::class);
        $telemetry->expects($this->once())
            ->method('increment')
            ->with(
                'bulk_evaluation_errors_total',
                1,
                ['flag_count' => '2', 'error_type' => \RuntimeException::class]
            );

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $this->expectException(\RuntimeException::class);
        $monitored->evaluateMany(['flag.one', 'flag.two']);
    }

    // ========================================
    // No-Op Tests (Without Telemetry)
    // ========================================

    public function test_isEnabled_works_without_telemetry(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('isEnabled')->willReturn(true);

        $monitored = new MonitoredFlagManager($inner, null);

        $result = $monitored->isEnabled('test.flag');

        $this->assertTrue($result, 'Should work normally without telemetry');
    }

    public function test_evaluateMany_works_without_telemetry(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('evaluateMany')->willReturn([
            'flag.one' => true,
            'flag.two' => false,
        ]);

        $monitored = new MonitoredFlagManager($inner, null);

        $results = $monitored->evaluateMany(['flag.one', 'flag.two']);

        $this->assertSame(['flag.one' => true, 'flag.two' => false], $results);
    }

    public function test_isEnabled_handles_telemetry_without_timing_method(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('isEnabled')->willReturn(true);

        // Telemetry object without timing() method
        $telemetry = new class {
            public function increment(string $key, float $value, array $tags): void
            {
                // No-op
            }
        };

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $result = $monitored->isEnabled('test.flag');

        $this->assertTrue($result, 'Should work even if telemetry lacks timing method');
    }

    public function test_isEnabled_handles_telemetry_without_increment_method(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('isEnabled')->willReturn(true);

        // Telemetry object without increment() method
        $telemetry = new class {
            public function timing(string $key, float $ms, array $tags): void
            {
                // No-op
            }
        };

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $result = $monitored->isEnabled('test.flag');

        $this->assertTrue($result, 'Should work even if telemetry lacks increment method');
    }

    // ========================================
    // Context Passthrough Tests
    // ========================================

    public function test_isEnabled_passes_context_to_inner(): void
    {
        $context = new EvaluationContext(tenantId: 'tenant-123', userId: 'user-456');

        $inner = $this->createMock(FeatureFlagManagerInterface::class);
        $inner->expects($this->once())
            ->method('isEnabled')
            ->with('test.flag', $context, false)
            ->willReturn(true);

        $monitored = new MonitoredFlagManager($inner, null);

        $monitored->isEnabled('test.flag', $context);
    }

    public function test_isEnabled_passes_defaultIfNotFound_to_inner(): void
    {
        $inner = $this->createMock(FeatureFlagManagerInterface::class);
        $inner->expects($this->once())
            ->method('isEnabled')
            ->with('test.flag', [], true)
            ->willReturn(false);

        $monitored = new MonitoredFlagManager($inner, null);

        $monitored->isEnabled('test.flag', defaultIfNotFound: true);
    }

    public function test_evaluateMany_passes_context_to_inner(): void
    {
        $context = new EvaluationContext(tenantId: 'tenant-123');

        $inner = $this->createMock(FeatureFlagManagerInterface::class);
        $inner->expects($this->once())
            ->method('evaluateMany')
            ->with(['flag.one'], $context)
            ->willReturn(['flag.one' => true]);

        $monitored = new MonitoredFlagManager($inner, null);

        $monitored->evaluateMany(['flag.one'], $context);
    }

    // ========================================
    // Integration Tests
    // ========================================

    public function test_full_monitoring_flow_for_successful_evaluation(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('isEnabled')->willReturn(true);

        $telemetry = $this->createMock(\stdClass::class);

        // Expect both timing and counter
        $telemetry->expects($this->exactly(2))
            ->method($this->logicalOr('timing', 'increment'));

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $result = $monitored->isEnabled('test.flag');

        $this->assertTrue($result);
    }

    public function test_full_monitoring_flow_for_bulk_evaluation(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('evaluateMany')->willReturn([
            'flag.one' => true,
            'flag.two' => false,
        ]);

        $telemetry = $this->createMock(\stdClass::class);

        // Expect timing (1) + increment (1) + gauge (2) = 4 total calls
        $telemetry->expects($this->exactly(4))
            ->method($this->logicalOr('timing', 'increment', 'gauge'));

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $monitored->evaluateMany(['flag.one', 'flag.two']);
    }

    public function test_monitoring_does_not_affect_exception_propagation(): void
    {
        $inner = $this->createStub(FeatureFlagManagerInterface::class);
        $inner->method('isEnabled')->willThrowException(new \RuntimeException('Test error'));

        $telemetry = $this->createStub(\stdClass::class);

        $monitored = new MonitoredFlagManager($inner, $telemetry);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        $monitored->isEnabled('test.flag');
    }
}
