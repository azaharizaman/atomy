<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\Core\Engine;

use Nexus\FeatureFlags\Core\Engine\PercentageHasher;
use PHPUnit\Framework\TestCase;

final class PercentageHasherTest extends TestCase
{
    private PercentageHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new PercentageHasher();
    }

    /** @test */
    public function it_returns_deterministic_bucket_for_same_input(): void
    {
        $identifier = 'user-12345';
        $flagName = 'test_feature';

        // Call 10,000 times to verify determinism
        $buckets = [];
        for ($i = 0; $i < 10000; $i++) {
            $buckets[] = $this->hasher->getBucket($identifier, $flagName);
        }

        // All buckets should be identical
        $uniqueBuckets = array_unique($buckets);
        $this->assertCount(1, $uniqueBuckets, 'Bucket should be deterministic for same input');

        $bucket = $buckets[0];
        $this->assertGreaterThanOrEqual(0, $bucket);
        $this->assertLessThan(100, $bucket);
    }

    /** @test */
    public function it_returns_bucket_in_valid_range(): void
    {
        $bucket = $this->hasher->getBucket('user-123', 'test_flag');

        $this->assertIsInt($bucket);
        $this->assertGreaterThanOrEqual(0, $bucket);
        $this->assertLessThanOrEqual(99, $bucket);
    }

    /** @test */
    public function it_produces_different_buckets_for_different_identifiers(): void
    {
        $flagName = 'test_feature';
        $buckets = [];

        // Generate buckets for 100 different users
        for ($i = 0; $i < 100; $i++) {
            $buckets[] = $this->hasher->getBucket("user-{$i}", $flagName);
        }

        // Should have multiple different buckets (not all the same)
        $uniqueBuckets = array_unique($buckets);
        $this->assertGreaterThan(10, count($uniqueBuckets), 'Should produce varied buckets for different users');
    }

    /** @test */
    public function it_produces_different_buckets_for_different_flags(): void
    {
        $identifier = 'user-12345';

        $bucket1 = $this->hasher->getBucket($identifier, 'feature_a');
        $bucket2 = $this->hasher->getBucket($identifier, 'feature_b');

        // Same user should get different buckets for different flags
        // (not guaranteed, but highly likely with good hash function)
        // We'll test with multiple flags to ensure variety
        $buckets = [];
        for ($i = 0; $i < 50; $i++) {
            $buckets[] = $this->hasher->getBucket($identifier, "feature_{$i}");
        }

        $uniqueBuckets = array_unique($buckets);
        $this->assertGreaterThan(10, count($uniqueBuckets), 'Same user should get different buckets for different flags');
    }

    /** @test */
    public function it_produces_roughly_uniform_distribution(): void
    {
        $flagName = 'test_feature';
        $bucketCounts = array_fill(0, 100, 0);

        // Generate 10,000 buckets for different users
        for ($i = 0; $i < 10000; $i++) {
            $bucket = $this->hasher->getBucket("user-{$i}", $flagName);
            $bucketCounts[$bucket]++;
        }

        // Each bucket should get roughly 100 ± 50 assignments (10,000 / 100 = 100)
        // Allowing wider tolerance for statistical variance
        foreach ($bucketCounts as $bucket => $count) {
            $this->assertGreaterThan(
                30,
                $count,
                "Bucket {$bucket} has too few assignments ({$count}), distribution may be skewed"
            );
            $this->assertLessThan(
                170,
                $count,
                "Bucket {$bucket} has too many assignments ({$count}), distribution may be skewed"
            );
        }

        // Verify all 100 buckets got at least some assignments
        $nonEmptyBuckets = array_filter($bucketCounts, fn($count) => $count > 0);
        $this->assertCount(100, $nonEmptyBuckets, 'All 100 buckets should have at least one assignment');
    }

    /** @test */
    public function it_handles_empty_identifier(): void
    {
        $bucket = $this->hasher->getBucket('', 'test_flag');

        $this->assertIsInt($bucket);
        $this->assertGreaterThanOrEqual(0, $bucket);
        $this->assertLessThan(100, $bucket);
    }

    /** @test */
    public function it_handles_empty_flag_name(): void
    {
        $bucket = $this->hasher->getBucket('user-123', '');

        $this->assertIsInt($bucket);
        $this->assertGreaterThanOrEqual(0, $bucket);
        $this->assertLessThan(100, $bucket);
    }

    /** @test */
    public function it_handles_special_characters_in_identifier(): void
    {
        $specialIdentifiers = [
            'user@example.com',
            'user-name+tag',
            'user/with/slashes',
            'user\\with\\backslashes',
            'user with spaces',
            '用户-unicode',
        ];

        foreach ($specialIdentifiers as $identifier) {
            $bucket = $this->hasher->getBucket($identifier, 'test_flag');

            $this->assertIsInt($bucket);
            $this->assertGreaterThanOrEqual(0, $bucket);
            $this->assertLessThan(100, $bucket);
        }
    }

    /** @test */
    public function it_handles_very_long_identifiers(): void
    {
        $longIdentifier = str_repeat('a', 10000);
        $bucket = $this->hasher->getBucket($longIdentifier, 'test_flag');

        $this->assertIsInt($bucket);
        $this->assertGreaterThanOrEqual(0, $bucket);
        $this->assertLessThan(100, $bucket);
    }

    /** @test */
    public function it_produces_bucket_0_for_some_inputs(): void
    {
        // Test that bucket 0 is possible
        $buckets = [];
        for ($i = 0; $i < 1000; $i++) {
            $bucket = $this->hasher->getBucket("user-{$i}", "flag-{$i}");
            $buckets[] = $bucket;
        }

        $this->assertContains(0, $buckets, 'Bucket 0 should be possible');
    }

    /** @test */
    public function it_produces_bucket_99_for_some_inputs(): void
    {
        // Test that bucket 99 is possible
        $buckets = [];
        for ($i = 0; $i < 1000; $i++) {
            $bucket = $this->hasher->getBucket("user-{$i}", "flag-{$i}");
            $buckets[] = $bucket;
        }

        $this->assertContains(99, $buckets, 'Bucket 99 should be possible');
    }

    /** @test */
    public function it_is_stable_across_multiple_instances(): void
    {
        $hasher1 = new PercentageHasher();
        $hasher2 = new PercentageHasher();

        $bucket1 = $hasher1->getBucket('user-123', 'test_flag');
        $bucket2 = $hasher2->getBucket('user-123', 'test_flag');

        $this->assertSame($bucket1, $bucket2, 'Different instances should produce same bucket');
    }
}
