<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\Core\Decorators;

use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagEvaluatorInterface;
use Nexus\FeatureFlags\Core\Decorators\InMemoryMemoizedEvaluator;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use PHPUnit\Framework\TestCase;

final class InMemoryMemoizedEvaluatorTest extends TestCase
{
    // ========================================
    // Single Evaluation Caching Tests
    // ========================================

    public function test_evaluate_caches_result_on_first_call(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context = new EvaluationContext(tenantId: 'tenant-1', userId: 'user-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->once()) // Only called once
            ->method('evaluate')
            ->with($flag, $context)
            ->willReturn(true);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        // First call - should hit inner evaluator
        $result1 = $memoized->evaluate($flag, $context);
        $this->assertTrue($result1);

        // Second call - should return cached result
        $result2 = $memoized->evaluate($flag, $context);
        $this->assertTrue($result2);

        $stats = $memoized->getCacheStats();
        $this->assertSame(1, $stats['size']);
    }

    public function test_evaluate_uses_different_cache_keys_for_different_flags(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');
        $flag1->method('getChecksum')->willReturn('checksum1');

        $flag2 = $this->createStub(FlagDefinitionInterface::class);
        $flag2->method('getName')->willReturn('flag.two');
        $flag2->method('getChecksum')->willReturn('checksum2');

        $context = new EvaluationContext(tenantId: 'tenant-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->exactly(2))
            ->method('evaluate')
            ->willReturnCallback(function (FlagDefinitionInterface $flag) {
                return $flag->getName() === 'flag.one';
            });

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $result1 = $memoized->evaluate($flag1, $context);
        $result2 = $memoized->evaluate($flag2, $context);

        $this->assertTrue($result1);
        $this->assertFalse($result2);

        $stats = $memoized->getCacheStats();
        $this->assertSame(2, $stats['size']);
    }

    public function test_evaluate_uses_different_cache_keys_for_different_contexts(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context1 = new EvaluationContext(tenantId: 'tenant-1', userId: 'user-1');
        $context2 = new EvaluationContext(tenantId: 'tenant-1', userId: 'user-2');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->exactly(2))
            ->method('evaluate')
            ->willReturnCallback(function ($flag, EvaluationContext $ctx) {
                return $ctx->userId === 'user-1';
            });

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $result1 = $memoized->evaluate($flag, $context1);
        $result2 = $memoized->evaluate($flag, $context2);

        $this->assertTrue($result1);
        $this->assertFalse($result2);

        $stats = $memoized->getCacheStats();
        $this->assertSame(2, $stats['size']);
    }

    public function test_evaluate_uses_different_cache_keys_when_checksum_changes(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('test.flag');
        $flag1->method('getChecksum')->willReturn('checksum-v1');

        $flag2 = $this->createStub(FlagDefinitionInterface::class);
        $flag2->method('getName')->willReturn('test.flag'); // Same name
        $flag2->method('getChecksum')->willReturn('checksum-v2'); // Different checksum

        $context = new EvaluationContext(tenantId: 'tenant-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->exactly(2))
            ->method('evaluate')
            ->willReturnOnConsecutiveCalls(true, false);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $result1 = $memoized->evaluate($flag1, $context);
        $result2 = $memoized->evaluate($flag2, $context);

        $this->assertTrue($result1);
        $this->assertFalse($result2, 'Different checksum should bypass cache');

        $stats = $memoized->getCacheStats();
        $this->assertSame(2, $stats['size']);
    }

    public function test_evaluate_handles_null_tenant_id_in_cache_key(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context = new EvaluationContext(userId: 'user-1'); // No tenant

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->once())
            ->method('evaluate')
            ->willReturn(true);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $result1 = $memoized->evaluate($flag, $context);
        $result2 = $memoized->evaluate($flag, $context);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    public function test_evaluate_handles_null_stable_identifier_in_cache_key(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context = new EvaluationContext(tenantId: 'tenant-1'); // No user/session

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->once())
            ->method('evaluate')
            ->willReturn(true);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $result1 = $memoized->evaluate($flag, $context);
        $result2 = $memoized->evaluate($flag, $context);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    // ========================================
    // Bulk Evaluation Caching Tests
    // ========================================

    public function test_evaluateMany_returns_cached_results_without_calling_inner(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');
        $flag1->method('getChecksum')->willReturn('check1');

        $flag2 = $this->createStub(FlagDefinitionInterface::class);
        $flag2->method('getName')->willReturn('flag.two');
        $flag2->method('getChecksum')->willReturn('check2');

        $context = new EvaluationContext(tenantId: 'tenant-1', userId: 'user-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->once()) // Only first call
            ->method('evaluateMany')
            ->willReturn([
                'flag.one' => true,
                'flag.two' => false,
            ]);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        // First call - should hit inner
        $results1 = $memoized->evaluateMany([
            'flag.one' => $flag1,
            'flag.two' => $flag2,
        ], $context);

        // Second call - should use cache
        $results2 = $memoized->evaluateMany([
            'flag.one' => $flag1,
            'flag.two' => $flag2,
        ], $context);

        $this->assertSame($results1, $results2);
        $this->assertSame(['flag.one' => true, 'flag.two' => false], $results2);
    }

    public function test_evaluateMany_mixes_cached_and_uncached_flags(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');
        $flag1->method('getChecksum')->willReturn('check1');

        $flag2 = $this->createStub(FlagDefinitionInterface::class);
        $flag2->method('getName')->willReturn('flag.two');
        $flag2->method('getChecksum')->willReturn('check2');

        $flag3 = $this->createStub(FlagDefinitionInterface::class);
        $flag3->method('getName')->willReturn('flag.three');
        $flag3->method('getChecksum')->willReturn('check3');

        $context = new EvaluationContext(tenantId: 'tenant-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);

        // First call: evaluate flag1 and flag2
        $inner->expects($this->exactly(2))
            ->method('evaluateMany')
            ->willReturnOnConsecutiveCalls(
                ['flag.one' => true, 'flag.two' => false], // First call
                ['flag.three' => true]  // Second call (only uncached)
            );

        $memoized = new InMemoryMemoizedEvaluator($inner);

        // Cache flag1 and flag2
        $memoized->evaluateMany([
            'flag.one' => $flag1,
            'flag.two' => $flag2,
        ], $context);

        // Now evaluate all three (flag1, flag2 cached, flag3 uncached)
        $results = $memoized->evaluateMany([
            'flag.one' => $flag1,
            'flag.two' => $flag2,
            'flag.three' => $flag3,
        ], $context);

        $this->assertSame([
            'flag.one' => true,   // From cache
            'flag.two' => false,  // From cache
            'flag.three' => true, // Newly evaluated
        ], $results);
    }

    public function test_evaluateMany_caches_newly_evaluated_results(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');
        $flag1->method('getChecksum')->willReturn('check1');

        $context = new EvaluationContext(tenantId: 'tenant-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->once())
            ->method('evaluateMany')
            ->willReturn(['flag.one' => true]);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        // First bulk call
        $memoized->evaluateMany(['flag.one' => $flag1], $context);

        // Second single call should use cache
        $result = $memoized->evaluate($flag1, $context);

        $this->assertTrue($result);
    }

    // ========================================
    // Cache Management Tests
    // ========================================

    public function test_clearCache_empties_cache(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context = new EvaluationContext(tenantId: 'tenant-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->exactly(2)) // Called twice after clear
            ->method('evaluate')
            ->willReturn(true);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        // Cache result
        $memoized->evaluate($flag, $context);

        $stats = $memoized->getCacheStats();
        $this->assertSame(1, $stats['size']);

        // Clear cache
        $memoized->clearCache();

        $stats = $memoized->getCacheStats();
        $this->assertSame(0, $stats['size']);

        // Should call inner again
        $memoized->evaluate($flag, $context);
    }

    public function test_getCacheStats_returns_accurate_size(): void
    {
        $flag1 = $this->createStub(FlagDefinitionInterface::class);
        $flag1->method('getName')->willReturn('flag.one');
        $flag1->method('getChecksum')->willReturn('check1');

        $flag2 = $this->createStub(FlagDefinitionInterface::class);
        $flag2->method('getName')->willReturn('flag.two');
        $flag2->method('getChecksum')->willReturn('check2');

        $context = new EvaluationContext(tenantId: 'tenant-1');

        $inner = $this->createStub(FlagEvaluatorInterface::class);
        $inner->method('evaluate')->willReturn(true);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $stats = $memoized->getCacheStats();
        $this->assertSame(0, $stats['size']);

        $memoized->evaluate($flag1, $context);
        $stats = $memoized->getCacheStats();
        $this->assertSame(1, $stats['size']);

        $memoized->evaluate($flag2, $context);
        $stats = $memoized->getCacheStats();
        $this->assertSame(2, $stats['size']);
    }

    public function test_getCacheStats_returns_cache_keys(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context = new EvaluationContext(tenantId: 'tenant-1', userId: 'user-1');

        $inner = $this->createStub(FlagEvaluatorInterface::class);
        $inner->method('evaluate')->willReturn(true);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $memoized->evaluate($flag, $context);

        $stats = $memoized->getCacheStats();
        $this->assertCount(1, $stats['keys']);
        $this->assertIsString($stats['keys'][0]);
    }

    // ========================================
    // Cache Key Determinism Tests
    // ========================================

    public function test_cache_key_is_deterministic_for_same_inputs(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context = new EvaluationContext(tenantId: 'tenant-1', userId: 'user-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->once()) // Should only be called once
            ->method('evaluate')
            ->willReturn(true);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        // Evaluate multiple times with same inputs
        for ($i = 0; $i < 10; $i++) {
            $memoized->evaluate($flag, $context);
        }

        $stats = $memoized->getCacheStats();
        $this->assertSame(1, $stats['size'], 'Should only have 1 cache entry');
    }

    public function test_cache_key_includes_tenant_id(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context1 = new EvaluationContext(tenantId: 'tenant-1', userId: 'user-1');
        $context2 = new EvaluationContext(tenantId: 'tenant-2', userId: 'user-1');

        $inner = $this->createMock(FlagEvaluatorInterface::class);
        $inner->expects($this->exactly(2))
            ->method('evaluate')
            ->willReturn(true);

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $memoized->evaluate($flag, $context1);
        $memoized->evaluate($flag, $context2);

        $stats = $memoized->getCacheStats();
        $this->assertSame(2, $stats['size'], 'Different tenants should have different cache keys');
    }

    // ========================================
    // Performance Tests
    // ========================================

    public function test_caching_improves_performance_for_repeated_evaluations(): void
    {
        $flag = $this->createStub(FlagDefinitionInterface::class);
        $flag->method('getName')->willReturn('test.flag');
        $flag->method('getChecksum')->willReturn('abc123');

        $context = new EvaluationContext(tenantId: 'tenant-1');

        $callCount = 0;
        $inner = $this->createStub(FlagEvaluatorInterface::class);
        $inner->method('evaluate')->willReturnCallback(function () use (&$callCount) {
            $callCount++;
            usleep(1000); // Simulate slow evaluation (1ms)
            return true;
        });

        $memoized = new InMemoryMemoizedEvaluator($inner);

        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $memoized->evaluate($flag, $context);
        }
        $duration = microtime(true) - $start;

        $this->assertSame(1, $callCount, 'Inner should only be called once');
        $this->assertLessThan(0.01, $duration, 'Cached evaluations should be fast');
    }
}
