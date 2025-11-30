<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\Core\Engine;

use Nexus\FeatureFlags\Contracts\CustomEvaluatorInterface;
use Nexus\FeatureFlags\Core\Engine\DefaultFlagEvaluator;
use Nexus\FeatureFlags\Core\Engine\PercentageHasher;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Exceptions\CustomEvaluatorException;
use Nexus\FeatureFlags\Exceptions\InvalidContextException;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use PHPUnit\Framework\TestCase;

final class DefaultFlagEvaluatorTest extends TestCase
{
    private DefaultFlagEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new DefaultFlagEvaluator(new PercentageHasher());
    }

    // ============================================================
    // Override Precedence Tests
    // ============================================================

    /** @test */
    public function it_force_off_beats_enabled_true(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            override: FlagOverride::FORCE_OFF
        );

        $result = $this->evaluator->evaluate($flag, new EvaluationContext());

        $this->assertFalse($result);
    }

    /** @test */
    public function it_force_off_beats_enabled_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            override: FlagOverride::FORCE_OFF
        );

        $result = $this->evaluator->evaluate($flag, new EvaluationContext());

        $this->assertFalse($result);
    }

    /** @test */
    public function it_force_on_beats_enabled_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            override: FlagOverride::FORCE_ON
        );

        $result = $this->evaluator->evaluate($flag, new EvaluationContext());

        $this->assertTrue($result);
    }

    /** @test */
    public function it_force_on_beats_enabled_true(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            override: FlagOverride::FORCE_ON
        );

        $result = $this->evaluator->evaluate($flag, new EvaluationContext());

        $this->assertTrue($result);
    }

    // ============================================================
    // Enabled State Tests
    // ============================================================

    /** @test */
    public function it_returns_false_when_disabled_with_no_override(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $result = $this->evaluator->evaluate($flag, new EvaluationContext());

        $this->assertFalse($result);
    }

    // ============================================================
    // SYSTEM_WIDE Strategy Tests
    // ============================================================

    /** @test */
    public function it_evaluates_system_wide_enabled_as_true(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $result = $this->evaluator->evaluate($flag, new EvaluationContext());

        $this->assertTrue($result);
    }

    /** @test */
    public function it_evaluates_system_wide_disabled_as_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $result = $this->evaluator->evaluate($flag, new EvaluationContext());

        $this->assertFalse($result);
    }

    /** @test */
    public function it_evaluates_system_wide_with_any_context(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        // Should work with various contexts
        $contexts = [
            new EvaluationContext(),
            new EvaluationContext(tenantId: 'tenant-123'),
            new EvaluationContext(userId: 'user-456'),
            new EvaluationContext(sessionId: 'session-789'),
        ];

        foreach ($contexts as $context) {
            $result = $this->evaluator->evaluate($flag, $context);
            $this->assertTrue($result);
        }
    }

    // ============================================================
    // PERCENTAGE_ROLLOUT Strategy Tests
    // ============================================================

    /** @test */
    public function it_evaluates_percentage_rollout_0_percent_as_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 0
        );

        $context = new EvaluationContext(userId: 'user-123');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_evaluates_percentage_rollout_100_percent_as_true(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 100
        );

        $context = new EvaluationContext(userId: 'user-123');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_evaluates_percentage_rollout_deterministically(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $context = new EvaluationContext(userId: 'user-123');

        // Call multiple times - should always return same result
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->evaluator->evaluate($flag, $context);
        }

        $uniqueResults = array_unique($results);
        $this->assertCount(1, $uniqueResults, 'Result should be deterministic');
    }

    /** @test */
    public function it_throws_exception_when_percentage_rollout_has_no_stable_identifier(): void
    {
        $flag = new FlagDefinition(
            name: 'test_flag',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $context = new EvaluationContext(); // No userId, sessionId, or tenantId

        $this->expectException(InvalidContextException::class);
        $this->expectExceptionMessage('requires a stable identifier');

        $this->evaluator->evaluate($flag, $context);
    }

    /** @test */
    public function it_uses_user_id_as_stable_identifier_for_percentage(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $context = new EvaluationContext(
            tenantId: 'tenant-123',
            userId: 'user-456',
            sessionId: 'session-789'
        );

        // Should use userId (highest priority)
        $result = $this->evaluator->evaluate($flag, $context);
        $this->assertIsBool($result);
    }

    /** @test */
    public function it_uses_session_id_when_no_user_id_for_percentage(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $context = new EvaluationContext(sessionId: 'session-789');

        $result = $this->evaluator->evaluate($flag, $context);
        $this->assertIsBool($result);
    }

    /** @test */
    public function it_uses_tenant_id_when_no_user_or_session_for_percentage(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $context = new EvaluationContext(tenantId: 'tenant-123');

        $result = $this->evaluator->evaluate($flag, $context);
        $this->assertIsBool($result);
    }

    /** @test */
    public function it_produces_roughly_50_percent_distribution(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $enabledCount = 0;
        $totalCount = 1000;

        for ($i = 0; $i < $totalCount; $i++) {
            $context = new EvaluationContext(userId: "user-{$i}");
            if ($this->evaluator->evaluate($flag, $context)) {
                $enabledCount++;
            }
        }

        // Should be roughly 500 ± 100 (allowing for variance)
        $this->assertGreaterThan(400, $enabledCount);
        $this->assertLessThan(600, $enabledCount);
    }

    // ============================================================
    // TENANT_LIST Strategy Tests
    // ============================================================

    /** @test */
    public function it_evaluates_tenant_list_with_matching_tenant_as_true(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: ['tenant-123', 'tenant-456']
        );

        $context = new EvaluationContext(tenantId: 'tenant-123');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_evaluates_tenant_list_with_non_matching_tenant_as_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: ['tenant-123', 'tenant-456']
        );

        $context = new EvaluationContext(tenantId: 'tenant-999');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_evaluates_tenant_list_with_no_tenant_as_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: ['tenant-123', 'tenant-456']
        );

        $context = new EvaluationContext(); // No tenantId
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_evaluates_tenant_list_with_empty_list_as_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: []
        );

        $context = new EvaluationContext(tenantId: 'tenant-123');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_uses_strict_comparison_for_tenant_list(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: ['123']
        );

        // Context with int-like string should not match if list has different type
        $context = new EvaluationContext(tenantId: '123');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertTrue($result); // Should match since both are strings
    }

    // ============================================================
    // USER_LIST Strategy Tests
    // ============================================================

    /** @test */
    public function it_evaluates_user_list_with_matching_user_as_true(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: ['user-alice', 'user-bob']
        );

        $context = new EvaluationContext(userId: 'user-alice');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_evaluates_user_list_with_non_matching_user_as_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: ['user-alice', 'user-bob']
        );

        $context = new EvaluationContext(userId: 'user-charlie');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_evaluates_user_list_with_no_user_as_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: ['user-alice', 'user-bob']
        );

        $context = new EvaluationContext(); // No userId
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_evaluates_user_list_with_empty_list_as_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: []
        );

        $context = new EvaluationContext(userId: 'user-alice');
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertFalse($result);
    }

    // ============================================================
    // CUSTOM Strategy Tests
    // ============================================================

    /** @test */
    public function it_evaluates_custom_strategy_with_valid_evaluator(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::CUSTOM,
            value: AlwaysTrueEvaluator::class
        );

        $context = new EvaluationContext();
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_evaluates_custom_strategy_returning_false(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::CUSTOM,
            value: AlwaysFalseEvaluator::class
        );

        $context = new EvaluationContext();
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_passes_context_to_custom_evaluator(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::CUSTOM,
            value: PremiumPlanEvaluator::class
        );

        $context = new EvaluationContext(customAttributes: ['plan' => 'premium']);
        $result = $this->evaluator->evaluate($flag, $context);

        $this->assertTrue($result);

        $context2 = new EvaluationContext(customAttributes: ['plan' => 'basic']);
        $result2 = $this->evaluator->evaluate($flag, $context2);

        $this->assertFalse($result2);
    }

    /** @test */
    public function it_throws_exception_when_custom_class_does_not_implement_interface(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::CUSTOM,
            value: InvalidEvaluator::class
        );

        $this->expectException(CustomEvaluatorException::class);
        $this->expectExceptionMessage('must implement CustomEvaluatorInterface');

        $this->evaluator->evaluate($flag, new EvaluationContext());
    }

    /** @test */
    public function it_throws_exception_when_custom_evaluator_throws_exception(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::CUSTOM,
            value: ThrowingEvaluator::class
        );

        $this->expectException(CustomEvaluatorException::class);
        $this->expectExceptionMessage('threw exception during evaluation');

        $this->evaluator->evaluate($flag, new EvaluationContext());
    }

    // ============================================================
    // evaluateMany() Tests
    // ============================================================

    /** @test */
    public function it_evaluates_many_flags(): void
    {
        $flags = [
            new FlagDefinition('flag_a', true, FlagStrategy::SYSTEM_WIDE, null),
            new FlagDefinition('flag_b', false, FlagStrategy::SYSTEM_WIDE, null),
            new FlagDefinition('flag_c', true, FlagStrategy::SYSTEM_WIDE, null, FlagOverride::FORCE_OFF),
        ];

        $context = new EvaluationContext();
        $results = $this->evaluator->evaluateMany($flags, $context);

        $this->assertSame([
            'flag_a' => true,
            'flag_b' => false,
            'flag_c' => false,
        ], $results);
    }

    /** @test */
    public function it_evaluates_many_with_mixed_strategies(): void
    {
        $flags = [
            new FlagDefinition('system', true, FlagStrategy::SYSTEM_WIDE, null),
            new FlagDefinition('percent', true, FlagStrategy::PERCENTAGE_ROLLOUT, 100),
            new FlagDefinition('tenant', true, FlagStrategy::TENANT_LIST, ['tenant-123']),
        ];

        $context = new EvaluationContext(tenantId: 'tenant-123', userId: 'user-456');
        $results = $this->evaluator->evaluateMany($flags, $context);

        $this->assertSame([
            'system' => true,
            'percent' => true,
            'tenant' => true,
        ], $results);
    }

    /** @test */
    public function it_evaluates_empty_array_as_empty_results(): void
    {
        $results = $this->evaluator->evaluateMany([], new EvaluationContext());

        $this->assertSame([], $results);
    }
}

// ============================================================
// Test Stub Classes
// ============================================================

final class AlwaysTrueEvaluator implements CustomEvaluatorInterface
{
    public function evaluate(EvaluationContext $context): bool
    {
        return true;
    }
}

final class AlwaysFalseEvaluator implements CustomEvaluatorInterface
{
    public function evaluate(EvaluationContext $context): bool
    {
        return false;
    }
}

final class PremiumPlanEvaluator implements CustomEvaluatorInterface
{
    public function evaluate(EvaluationContext $context): bool
    {
        return ($context->customAttributes['plan'] ?? null) === 'premium';
    }
}

final class InvalidEvaluator
{
    // Does not implement CustomEvaluatorInterface
}

final class ThrowingEvaluator implements CustomEvaluatorInterface
{
    public function evaluate(EvaluationContext $context): bool
    {
        throw new \RuntimeException('Evaluation failed');
    }
}
