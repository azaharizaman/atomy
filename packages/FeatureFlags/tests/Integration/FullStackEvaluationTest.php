<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Integration;

use Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator;
use Nexus\FeatureFlags\Core\Engine\PercentageHasher;
use Nexus\FeatureFlags\Core\Repository\InMemoryFlagRepository;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Services\FeatureFlagManager;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Full-stack integration test for feature flag evaluation.
 *
 * Tests the complete flow from manager → repository → evaluator.
 */
final class FullStackEvaluationTest extends TestCase
{
    private FeatureFlagManager $manager;
    private InMemoryFlagRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryFlagRepository();
        $evaluator = new DefaultFlagEvaluator(new PercentageHasher());
        $logger = new NullLogger();

        $this->manager = new FeatureFlagManager($this->repository, $evaluator, $logger);
    }

    // ========================================
    // SYSTEM_WIDE Strategy Tests
    // ========================================

    public function test_system_wide_enabled_flag_returns_true(): void
    {
        $flag = new FlagDefinition(
            name: 'global.feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('global.feature');

        $this->assertTrue($result);
    }

    public function test_system_wide_disabled_flag_returns_false(): void
    {
        $flag = new FlagDefinition(
            name: 'global.feature',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('global.feature');

        $this->assertFalse($result);
    }

    // ========================================
    // PERCENTAGE_ROLLOUT Strategy Tests
    // ========================================

    public function test_percentage_rollout_enables_users_within_threshold(): void
    {
        $flag = new FlagDefinition(
            name: 'gradual.rollout',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50 // 50% rollout
        );

        $this->repository->save($flag);

        // Find a user in bucket 0-49 (enabled)
        $enabledUser = $this->findUserInBucket(0, 49, 'gradual.rollout');

        $result = $this->manager->isEnabled('gradual.rollout', [
            'userId' => $enabledUser,
        ]);

        $this->assertTrue($result);
    }

    public function test_percentage_rollout_disables_users_outside_threshold(): void
    {
        $flag = new FlagDefinition(
            name: 'gradual.rollout',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50 // 50% rollout
        );

        $this->repository->save($flag);

        // Find a user in bucket 50-99 (disabled)
        $disabledUser = $this->findUserInBucket(50, 99, 'gradual.rollout');

        $result = $this->manager->isEnabled('gradual.rollout', [
            'userId' => $disabledUser,
        ]);

        $this->assertFalse($result);
    }

    // ========================================
    // TENANT_LIST Strategy Tests
    // ========================================

    public function test_tenant_list_enables_whitelisted_tenant(): void
    {
        $flag = new FlagDefinition(
            name: 'enterprise.feature',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: ['tenant-premium', 'tenant-enterprise']
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('enterprise.feature', [
            'tenantId' => 'tenant-premium',
        ]);

        $this->assertTrue($result);
    }

    public function test_tenant_list_disables_non_whitelisted_tenant(): void
    {
        $flag = new FlagDefinition(
            name: 'enterprise.feature',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: ['tenant-premium', 'tenant-enterprise']
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('enterprise.feature', [
            'tenantId' => 'tenant-basic',
        ]);

        $this->assertFalse($result);
    }

    // ========================================
    // USER_LIST Strategy Tests
    // ========================================

    public function test_user_list_enables_whitelisted_user(): void
    {
        $flag = new FlagDefinition(
            name: 'beta.feature',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: ['user-alice', 'user-bob']
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('beta.feature', [
            'userId' => 'user-alice',
        ]);

        $this->assertTrue($result);
    }

    public function test_user_list_disables_non_whitelisted_user(): void
    {
        $flag = new FlagDefinition(
            name: 'beta.feature',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: ['user-alice', 'user-bob']
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('beta.feature', [
            'userId' => 'user-charlie',
        ]);

        $this->assertFalse($result);
    }

    // ========================================
    // Override Tests
    // ========================================

    public function test_force_on_override_enables_flag_regardless_of_strategy(): void
    {
        $flag = new FlagDefinition(
            name: 'maintenance.mode',
            enabled: false, // Disabled
            strategy: FlagStrategy::SYSTEM_WIDE,
            override: FlagOverride::FORCE_ON // But forced on
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('maintenance.mode');

        $this->assertTrue($result, 'FORCE_ON should override enabled=false');
    }

    public function test_force_off_override_disables_flag_regardless_of_strategy(): void
    {
        $flag = new FlagDefinition(
            name: 'buggy.feature',
            enabled: true, // Enabled
            strategy: FlagStrategy::SYSTEM_WIDE,
            override: FlagOverride::FORCE_OFF // But forced off (kill switch)
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('buggy.feature');

        $this->assertFalse($result, 'FORCE_OFF should override enabled=true');
    }

    // ========================================
    // Tenant Inheritance Tests
    // ========================================

    public function test_tenant_specific_flag_overrides_global_flag(): void
    {
        // Global flag: disabled
        $globalFlag = new FlagDefinition(
            name: 'new.feature',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        // Tenant-specific flag: enabled
        $tenantFlag = new FlagDefinition(
            name: 'new.feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $this->repository->save($globalFlag);

        // Save as tenant-specific
        $this->repository->storage["tenant:premium-123:flag:new.feature"] = $tenantFlag;

        $result = $this->manager->isEnabled('new.feature', [
            'tenantId' => 'premium-123',
        ]);

        $this->assertTrue($result, 'Tenant-specific flag should override global');
    }

    public function test_global_flag_used_when_no_tenant_specific_flag_exists(): void
    {
        $globalFlag = new FlagDefinition(
            name: 'new.feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        );

        $this->repository->save($globalFlag);

        $result = $this->manager->isEnabled('new.feature', [
            'tenantId' => 'tenant-123',
        ]);

        $this->assertTrue($result, 'Should fall back to global flag');
    }

    // ========================================
    // Bulk Evaluation Tests
    // ========================================

    public function test_evaluateMany_returns_correct_results_for_mixed_flags(): void
    {
        $this->repository->save(new FlagDefinition(
            name: 'flag.one',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE
        ));

        $this->repository->save(new FlagDefinition(
            name: 'flag.two',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE
        ));

        $this->repository->save(new FlagDefinition(
            name: 'flag.three',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: ['user-alice']
        ));

        $results = $this->manager->evaluateMany(
            ['flag.one', 'flag.two', 'flag.three', 'flag.nonexistent'],
            ['userId' => 'user-alice']
        );

        $this->assertSame([
            'flag.one' => true,
            'flag.two' => false,
            'flag.three' => true,
            'flag.nonexistent' => false, // Not found
        ], $results);
    }

    // ========================================
    // Fail-Closed Security Tests
    // ========================================

    public function test_nonexistent_flag_returns_false_by_default(): void
    {
        $result = $this->manager->isEnabled('does.not.exist');

        $this->assertFalse($result, 'Fail-closed: unknown flags should be disabled');
    }

    public function test_nonexistent_flag_returns_custom_default_when_specified(): void
    {
        $result = $this->manager->isEnabled('does.not.exist', defaultIfNotFound: true);

        $this->assertTrue($result);
    }

    // ========================================
    // Context Variations Tests
    // ========================================

    public function test_evaluation_respects_evaluation_context_object(): void
    {
        $flag = new FlagDefinition(
            name: 'user.feature',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: ['user-bob']
        );

        $this->repository->save($flag);

        $context = new EvaluationContext(userId: 'user-bob');

        $result = $this->manager->isEnabled('user.feature', $context);

        $this->assertTrue($result);
    }

    public function test_evaluation_respects_array_context(): void
    {
        $flag = new FlagDefinition(
            name: 'tenant.feature',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: ['tenant-xyz']
        );

        $this->repository->save($flag);

        $result = $this->manager->isEnabled('tenant.feature', [
            'tenantId' => 'tenant-xyz',
        ]);

        $this->assertTrue($result);
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Find a user ID whose hash falls within the specified bucket range.
     */
    private function findUserInBucket(int $minBucket, int $maxBucket, string $flagName): string
    {
        $hasher = new PercentageHasher();

        for ($i = 0; $i < 10000; $i++) {
            $userId = "user-{$i}";
            $bucket = $hasher->getBucket($userId, $flagName);

            if ($bucket >= $minBucket && $bucket <= $maxBucket) {
                return $userId;
            }
        }

        throw new \RuntimeException("Could not find user in bucket range {$minBucket}-{$maxBucket}");
    }
}
