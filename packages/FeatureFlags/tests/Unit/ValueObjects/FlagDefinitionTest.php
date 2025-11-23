<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\ValueObjects;

use Nexus\FeatureFlags\Contracts\CustomEvaluatorInterface;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Exceptions\InvalidFlagDefinitionException;
use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use Nexus\FeatureFlags\ValueObjects\FlagDefinition;
use PHPUnit\Framework\TestCase;

final class FlagDefinitionTest extends TestCase
{
    /** @test */
    public function it_creates_valid_system_wide_flag(): void
    {
        $flag = new FlagDefinition(
            name: 'test_feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $this->assertSame('test_feature', $flag->getName());
        $this->assertTrue($flag->isEnabled());
        $this->assertSame(FlagStrategy::SYSTEM_WIDE, $flag->getStrategy());
        $this->assertNull($flag->getValue());
        $this->assertNull($flag->getOverride());
        $this->assertSame([], $flag->getMetadata());
        $this->assertIsString($flag->getChecksum());
        $this->assertSame(64, strlen($flag->getChecksum())); // SHA-256
    }

    /** @test */
    public function it_creates_valid_percentage_rollout_flag(): void
    {
        $flag = new FlagDefinition(
            name: 'gradual_rollout',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 25
        );

        $this->assertSame('gradual_rollout', $flag->getName());
        $this->assertSame(FlagStrategy::PERCENTAGE_ROLLOUT, $flag->getStrategy());
        $this->assertSame(25, $flag->getValue());
    }

    /** @test */
    public function it_creates_valid_tenant_list_flag(): void
    {
        $tenants = ['tenant-123', 'tenant-456'];
        $flag = new FlagDefinition(
            name: 'premium_module',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: $tenants
        );

        $this->assertSame($tenants, $flag->getValue());
    }

    /** @test */
    public function it_creates_valid_user_list_flag(): void
    {
        $users = ['user-alice', 'user-bob'];
        $flag = new FlagDefinition(
            name: 'beta_access',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: $users
        );

        $this->assertSame($users, $flag->getValue());
    }

    /** @test */
    public function it_creates_valid_custom_flag(): void
    {
        $flag = new FlagDefinition(
            name: 'advanced_targeting',
            enabled: true,
            strategy: FlagStrategy::CUSTOM,
            value: StubCustomEvaluator::class
        );

        $this->assertSame(StubCustomEvaluator::class, $flag->getValue());
    }

    /** @test */
    public function it_accepts_flag_with_override(): void
    {
        $flag = new FlagDefinition(
            name: 'kill_switch',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            override: FlagOverride::FORCE_OFF
        );

        $this->assertSame(FlagOverride::FORCE_OFF, $flag->getOverride());
    }

    /** @test */
    public function it_accepts_flag_with_metadata(): void
    {
        $metadata = [
            'description' => 'Test feature',
            'created_by' => 'admin',
            'tags' => ['experimental', 'ui'],
        ];

        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            metadata: $metadata
        );

        $this->assertSame($metadata, $flag->getMetadata());
    }

    /** @test */
    public function it_accepts_valid_name_with_dots(): void
    {
        $flag = new FlagDefinition(
            name: 'module.analytics.dashboard',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $this->assertSame('module.analytics.dashboard', $flag->getName());
    }

    /** @test */
    public function it_accepts_valid_name_with_underscores(): void
    {
        $flag = new FlagDefinition(
            name: 'new_checkout_v2',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $this->assertSame('new_checkout_v2', $flag->getName());
    }

    /** @test */
    public function it_accepts_valid_name_with_numbers(): void
    {
        $flag = new FlagDefinition(
            name: 'feature_2024_q1',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $this->assertSame('feature_2024_q1', $flag->getName());
    }

    /** @test */
    public function it_rejects_empty_name(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('Flag name cannot be empty');

        new FlagDefinition(
            name: '',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
    }

    /** @test */
    public function it_rejects_name_with_uppercase(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('Flag name must match pattern');

        new FlagDefinition(
            name: 'NewFeature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
    }

    /** @test */
    public function it_rejects_name_with_hyphens(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('Flag name must match pattern');

        new FlagDefinition(
            name: 'new-feature',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
    }

    /** @test */
    public function it_rejects_name_with_special_characters(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('Flag name must match pattern');

        new FlagDefinition(
            name: 'feature@name',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
    }

    /** @test */
    public function it_rejects_name_exceeding_100_characters(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('Flag name cannot exceed 100 characters');

        new FlagDefinition(
            name: str_repeat('a', 101),
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );
    }

    /** @test */
    public function it_accepts_name_with_exactly_100_characters(): void
    {
        $name = str_repeat('a', 100);
        $flag = new FlagDefinition(
            name: $name,
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $this->assertSame($name, $flag->getName());
    }

    /** @test */
    public function it_rejects_system_wide_with_non_null_value(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('expected null');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: 'invalid'
        );
    }

    /** @test */
    public function it_rejects_percentage_with_non_integer_value(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('expected int');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: '25'
        );
    }

    /** @test */
    public function it_rejects_percentage_below_zero(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('Percentage value must be between 0 and 100');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: -1
        );
    }

    /** @test */
    public function it_rejects_percentage_above_100(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('Percentage value must be between 0 and 100');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 101
        );
    }

    /** @test */
    public function it_accepts_percentage_at_boundaries(): void
    {
        $flagZero = new FlagDefinition(
            name: 'test_zero',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 0
        );

        $flagHundred = new FlagDefinition(
            name: 'test_hundred',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 100
        );

        $this->assertSame(0, $flagZero->getValue());
        $this->assertSame(100, $flagHundred->getValue());
    }

    /** @test */
    public function it_rejects_tenant_list_with_non_array_value(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('expected array');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: 'tenant-123'
        );
    }

    /** @test */
    public function it_rejects_tenant_list_with_non_string_items(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('must be strings');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::TENANT_LIST,
            value: ['tenant-123', 456]
        );
    }

    /** @test */
    public function it_rejects_user_list_with_non_array_value(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('expected array');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::USER_LIST,
            value: 'user-123'
        );
    }

    /** @test */
    public function it_rejects_custom_with_non_string_value(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('expected class-string');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::CUSTOM,
            value: ['SomeClass']
        );
    }

    /** @test */
    public function it_rejects_custom_with_non_existent_class(): void
    {
        $this->expectException(InvalidFlagDefinitionException::class);
        $this->expectExceptionMessage('Custom evaluator class not found');

        new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::CUSTOM,
            value: 'NonExistentClass'
        );
    }

    /** @test */
    public function it_generates_deterministic_checksum(): void
    {
        $flag1 = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $flag2 = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $this->assertSame($flag1->getChecksum(), $flag2->getChecksum());
    }

    /** @test */
    public function it_generates_different_checksum_when_enabled_changes(): void
    {
        $flag1 = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $flag2 = new FlagDefinition(
            name: 'test',
            enabled: false,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        $this->assertNotSame($flag1->getChecksum(), $flag2->getChecksum());
    }

    /** @test */
    public function it_generates_different_checksum_when_value_changes(): void
    {
        $flag1 = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 25
        );

        $flag2 = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::PERCENTAGE_ROLLOUT,
            value: 50
        );

        $this->assertNotSame($flag1->getChecksum(), $flag2->getChecksum());
    }

    /** @test */
    public function it_generates_different_checksum_when_override_changes(): void
    {
        $flag1 = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            override: FlagOverride::FORCE_OFF
        );

        $flag2 = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null,
            override: FlagOverride::FORCE_ON
        );

        $this->assertNotSame($flag1->getChecksum(), $flag2->getChecksum());
    }

    /** @test */
    public function it_is_immutable(): void
    {
        $flag = new FlagDefinition(
            name: 'test',
            enabled: true,
            strategy: FlagStrategy::SYSTEM_WIDE,
            value: null
        );

        // Verify all properties are readonly by checking class definition
        $reflection = new \ReflectionClass($flag);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }
}

// Stub class for testing CUSTOM strategy
final class StubCustomEvaluator implements CustomEvaluatorInterface
{
    public function evaluate(EvaluationContext $context): bool
    {
        return true;
    }
}
