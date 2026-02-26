<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Tests\Unit\Services;

use Nexus\Telemetry\Contracts\MetricRetentionInterface;
use Nexus\Telemetry\Contracts\MetricStorageInterface;
use Nexus\Telemetry\Services\MetricRetentionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(MetricRetentionService::class)]
#[Group('monitoring')]
#[Group('retention')]
final class MetricRetentionServiceTest extends TestCase
{
    private MetricStorageInterface $storage;
    private MetricRetentionInterface $retentionPolicy;
    private LoggerInterface $logger;
    private MetricRetentionService $service;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(MetricStorageInterface::class);
        $this->retentionPolicy = $this->createMock(MetricRetentionInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new MetricRetentionService(
            $this->storage,
            $this->retentionPolicy,
            $this->logger
        );
    }

    #[Test]
    public function it_prunes_expired_metrics(): void
    {
        $retentionPeriod = 86400 * 30; // 30 days
        $cutoffTime = time() - $retentionPeriod;

        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn($retentionPeriod);

        $this->storage->expects($this->once())
            ->method('deleteMetricsOlderThan')
            ->with(
                $this->callback(fn($time) => abs($time - $cutoffTime) <= 1),
                null
            )
            ->willReturn(150);

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $prunedCount = $this->service->pruneExpiredMetrics();

        $this->assertSame(150, $prunedCount);
    }

    #[Test]
    public function it_prunes_with_batch_size(): void
    {
        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn(86400);

        $this->storage->expects($this->once())
            ->method('deleteMetricsOlderThan')
            ->with($this->anything(), 100)
            ->willReturn(100);

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $prunedCount = $this->service->pruneExpiredMetrics(100);

        $this->assertSame(100, $prunedCount);
    }

    #[Test]
    public function it_logs_pruning_activity(): void
    {
        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn(86400);

        $this->storage->expects($this->once())
            ->method('deleteMetricsOlderThan')
            ->willReturn(50);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) {
                if (str_contains($message, 'Starting')) {
                    $this->assertArrayHasKey('cutoff_timestamp', $context);
                    $this->assertArrayHasKey('cutoff_date', $context);
                } elseif (str_contains($message, 'completed')) {
                    $this->assertArrayHasKey('pruned_count', $context);
                    $this->assertSame(50, $context['pruned_count']);
                }
            });

        $this->service->pruneExpiredMetrics();
    }

    #[Test]
    public function it_handles_pruning_errors(): void
    {
        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn(86400);

        $exception = new \RuntimeException('Storage error');

        $this->storage->expects($this->once())
            ->method('deleteMetricsOlderThan')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Metric retention cleanup failed',
                $this->callback(function (array $context) {
                    return $context['error'] === 'Storage error'
                        && $context['exception'] === \RuntimeException::class;
                })
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Storage error');

        $this->service->pruneExpiredMetrics();
    }

    #[Test]
    public function it_prunes_specific_metric(): void
    {
        $retentionPeriod = 86400 * 7; // 7 days
        $cutoffTime = time() - $retentionPeriod;

        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn($retentionPeriod);

        $this->storage->expects($this->once())
            ->method('deleteMetric')
            ->with(
                'api.requests',
                $this->callback(fn($time) => abs($time - $cutoffTime) <= 1)
            )
            ->willReturn(25);

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $prunedCount = $this->service->pruneMetric('api.requests');

        $this->assertSame(25, $prunedCount);
    }

    #[Test]
    public function it_logs_specific_metric_pruning(): void
    {
        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn(86400);

        $this->storage->expects($this->once())
            ->method('deleteMetric')
            ->with('cache.hits', $this->anything())
            ->willReturn(10);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) {
                if (str_contains($message, 'Pruning specific')) {
                    $this->assertSame('cache.hits', $context['metric_key']);
                } elseif (str_contains($message, 'pruned successfully')) {
                    $this->assertSame('cache.hits', $context['metric_key']);
                    $this->assertSame(10, $context['pruned_count']);
                }
            });

        $this->service->pruneMetric('cache.hits');
    }

    #[Test]
    public function it_handles_specific_metric_pruning_errors(): void
    {
        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn(86400);

        $this->storage->expects($this->once())
            ->method('deleteMetric')
            ->willThrowException(new \RuntimeException('Delete failed'));

        $this->logger->expects($this->once())
            ->method('info');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Metric pruning failed',
                $this->callback(function (array $context) {
                    return $context['metric_key'] === 'test.metric'
                        && $context['error'] === 'Delete failed';
                })
            );

        $this->expectException(\RuntimeException::class);

        $this->service->pruneMetric('test.metric');
    }

    #[Test]
    public function it_provides_retention_statistics(): void
    {
        $retentionPeriod = 86400 * 30; // 30 days
        $cutoffTime = time() - $retentionPeriod;

        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn($retentionPeriod);

        $this->storage->expects($this->once())
            ->method('countMetricsOlderThan')
            ->with($this->callback(fn($time) => abs($time - $cutoffTime) <= 1))
            ->willReturn(500);

        $stats = $this->service->getRetentionStats();

        $this->assertIsArray($stats);
        $this->assertSame($retentionPeriod, $stats['retention_period_seconds']);
        $this->assertSame(30.0, $stats['retention_period_days']);
        $this->assertArrayHasKey('cutoff_timestamp', $stats);
        $this->assertArrayHasKey('cutoff_date', $stats);
        $this->assertSame(500, $stats['metrics_eligible_for_cleanup']);
    }

    #[Test]
    public function it_checks_if_cleanup_is_needed(): void
    {
        $this->retentionPolicy->expects($this->exactly(2))
            ->method('getRetentionPeriod')
            ->willReturn(86400);

        $this->storage->expects($this->exactly(2))
            ->method('countMetricsOlderThan')
            ->willReturnOnConsecutiveCalls(1500, 500);

        $this->assertTrue($this->service->needsCleanup(1000));
        $this->assertFalse($this->service->needsCleanup(1000));
    }

    #[Test]
    public function it_uses_custom_threshold_for_cleanup_check(): void
    {
        $this->retentionPolicy->expects($this->once())
            ->method('getRetentionPeriod')
            ->willReturn(86400);

        $this->storage->expects($this->once())
            ->method('countMetricsOlderThan')
            ->willReturn(150);

        $this->assertTrue($this->service->needsCleanup(100));
    }

    #[Test]
    public function it_exposes_retention_policy(): void
    {
        $policy = $this->service->getRetentionPolicy();

        $this->assertSame($this->retentionPolicy, $policy);
    }
}
