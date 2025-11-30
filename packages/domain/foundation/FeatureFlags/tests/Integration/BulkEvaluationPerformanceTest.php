<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Integration;

use Nexus\FeatureFlags\Core\Decorators\InMemoryMemoizedEvaluator;
use Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator;
use Nexus\FeatureFlags\Core\Engine\PercentageHasher;
use Nexus\FeatureFlags\Core\Repository\InMemoryFlagRepository;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Services\FeatureFlagManager;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Performance test for bulk flag evaluation.
 *
 * Requirements:
 * - Evaluate 20 flags in < 100ms (without external I/O)
 * - Memoization should reduce redundant evaluations
 * - Bulk operations should be more efficient than individual calls
 */
final class BulkEvaluationPerformanceTest extends TestCase
{
    private FeatureFlagManager $manager;
    private InMemoryFlagRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryFlagRepository();

        $baseEvaluator = new DefaultFlagEvaluator(new PercentageHasher());
        $memoizedEvaluator = new InMemoryMemoizedEvaluator($baseEvaluator);

        $logger = new NullLogger();

        $this->manager = new FeatureFlagManager($this->repository, $memoizedEvaluator, $logger);

        // Seed repository with 20 flags
        for ($i = 1; $i <= 20; $i++) {
            $this->repository->save(new FlagDefinition(
                name: "feature.flag.{$i}",
                enabled: true,
                strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
                value: 50 // 50% rollout
            ));
        }
    }

    public function test_bulk_evaluation_completes_within_100ms(): void
    {
        $flagNames = [];
        for ($i = 1; $i <= 20; $i++) {
            $flagNames[] = "feature.flag.{$i}";
        }

        $context = new EvaluationContext(userId: 'user-test-123');

        $startTime = microtime(true);

        $results = $this->manager->evaluateMany($flagNames, $context);

        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        $this->assertCount(20, $results);
        $this->assertLessThan(100, $duration, "Bulk evaluation took {$duration}ms (expected < 100ms)");
    }

    public function test_memoization_improves_performance_for_repeated_evaluations(): void
    {
        $context = new EvaluationContext(userId: 'user-test-456');

        // First call - cold cache
        $startTime1 = microtime(true);
        $this->manager->isEnabled('feature.flag.1', $context);
        $duration1 = (microtime(true) - $startTime1) * 1000;

        // Second call - memoized
        $startTime2 = microtime(true);
        $this->manager->isEnabled('feature.flag.1', $context);
        $duration2 = (microtime(true) - $startTime2) * 1000;

        $this->assertLessThan($duration1, $duration2, 'Memoized call should be faster');
        $this->assertLessThan(1, $duration2, 'Memoized call should be < 1ms');
    }

    public function test_bulk_evaluation_faster_than_individual_calls(): void
    {
        $flagNames = ['feature.flag.1', 'feature.flag.2', 'feature.flag.3', 'feature.flag.4', 'feature.flag.5'];
        $context = new EvaluationContext(userId: 'user-perf-test');

        // Individual calls
        $startTime1 = microtime(true);
        foreach ($flagNames as $name) {
            $this->manager->isEnabled($name, $context);
        }
        $durationIndividual = (microtime(true) - $startTime1) * 1000;

        // Reset memoization cache for fair comparison
        $this->setUp();

        // Bulk call
        $startTime2 = microtime(true);
        $this->manager->evaluateMany($flagNames, $context);
        $durationBulk = (microtime(true) - $startTime2) * 1000;

        $this->assertLessThanOrEqual(
            $durationIndividual,
            $durationBulk,
            "Bulk evaluation ({$durationBulk}ms) should be <= individual calls ({$durationIndividual}ms)"
        );
    }

    public function test_evaluating_100_flags_individually_with_memoization(): void
    {
        // Add more flags
        for ($i = 21; $i <= 100; $i++) {
            $this->repository->save(new FlagDefinition(
                name: "feature.flag.{$i}",
                enabled: true,
                strategy: FlagStrategy::SYSTEM_WIDE
            ));
        }

        $context = new EvaluationContext(userId: 'user-stress-test');

        $startTime = microtime(true);

        for ($i = 1; $i <= 100; $i++) {
            $this->manager->isEnabled("feature.flag.{$i}", $context);
        }

        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(200, $duration, "100 individual evaluations took {$duration}ms (expected < 200ms)");
    }

    public function test_evaluating_100_flags_in_bulk(): void
    {
        // Add more flags
        for ($i = 21; $i <= 100; $i++) {
            $this->repository->save(new FlagDefinition(
                name: "feature.flag.{$i}",
                enabled: true,
                strategy: FlagStrategy::SYSTEM_WIDE
            ));
        }

        $flagNames = [];
        for ($i = 1; $i <= 100; $i++) {
            $flagNames[] = "feature.flag.{$i}";
        }

        $context = new EvaluationContext(userId: 'user-bulk-stress-test');

        $startTime = microtime(true);

        $results = $this->manager->evaluateMany($flagNames, $context);

        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertCount(100, $results);
        $this->assertLessThan(150, $duration, "Bulk evaluation of 100 flags took {$duration}ms (expected < 150ms)");
    }

    public function test_percentage_rollout_hashing_performance(): void
    {
        $hasher = new PercentageHasher();

        $startTime = microtime(true);

        // Hash 10,000 user+flag combinations
        for ($i = 0; $i < 10000; $i++) {
            $hasher->getBucket("user-{$i}", "feature.flag.test");
        }

        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(100, $duration, "10k hashing operations took {$duration}ms (expected < 100ms)");
    }

    public function test_repository_bulk_load_performance(): void
    {
        $flagNames = [];
        for ($i = 1; $i <= 50; $i++) {
            $flagNames[] = "feature.flag.{$i}";

            if ($i > 20) {
                $this->repository->save(new FlagDefinition(
                    name: "feature.flag.{$i}",
                    enabled: true,
                    strategy: FlagStrategy::SYSTEM_WIDE
                ));
            }
        }

        $startTime = microtime(true);

        $results = $this->repository->findMany($flagNames);

        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertCount(50, $results);
        $this->assertLessThan(10, $duration, "Bulk repository load took {$duration}ms (expected < 10ms)");
    }
}
