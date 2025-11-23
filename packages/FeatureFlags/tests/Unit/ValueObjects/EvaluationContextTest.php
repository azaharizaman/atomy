<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Tests\Unit\ValueObjects;

use Nexus\FeatureFlags\ValueObjects\EvaluationContext;
use PHPUnit\Framework\TestCase;

final class EvaluationContextTest extends TestCase
{
    /** @test */
    public function it_creates_context_with_all_fields(): void
    {
        $context = new EvaluationContext(
            tenantId: 'tenant-123',
            userId: 'user-456',
            sessionId: 'session-789',
            customAttributes: ['plan' => 'premium', 'country' => 'MY']
        );

        $this->assertSame('tenant-123', $context->tenantId);
        $this->assertSame('user-456', $context->userId);
        $this->assertSame('session-789', $context->sessionId);
        $this->assertSame(['plan' => 'premium', 'country' => 'MY'], $context->customAttributes);
    }

    /** @test */
    public function it_creates_context_with_minimal_fields(): void
    {
        $context = new EvaluationContext();

        $this->assertNull($context->tenantId);
        $this->assertNull($context->userId);
        $this->assertNull($context->sessionId);
        $this->assertSame([], $context->customAttributes);
    }

    /** @test */
    public function it_creates_context_from_array_with_snake_case_keys(): void
    {
        $data = [
            'tenant_id' => 'tenant-123',
            'user_id' => 'user-456',
            'session_id' => 'session-789',
            'plan' => 'premium',
            'country' => 'MY',
        ];

        $context = EvaluationContext::fromArray($data);

        $this->assertSame('tenant-123', $context->tenantId);
        $this->assertSame('user-456', $context->userId);
        $this->assertSame('session-789', $context->sessionId);
        $this->assertSame(['plan' => 'premium', 'country' => 'MY'], $context->customAttributes);
    }

    /** @test */
    public function it_creates_context_from_array_with_camel_case_keys(): void
    {
        $data = [
            'tenantId' => 'tenant-123',
            'userId' => 'user-456',
            'sessionId' => 'session-789',
        ];

        $context = EvaluationContext::fromArray($data);

        $this->assertSame('tenant-123', $context->tenantId);
        $this->assertSame('user-456', $context->userId);
        $this->assertSame('session-789', $context->sessionId);
    }

    /** @test */
    public function it_creates_context_from_array_with_mixed_case_keys(): void
    {
        $data = [
            'tenant_id' => 'tenant-123',
            'userId' => 'user-456',
            'sessionId' => 'session-789',
        ];

        $context = EvaluationContext::fromArray($data);

        $this->assertSame('tenant-123', $context->tenantId);
        $this->assertSame('user-456', $context->userId);
        $this->assertSame('session-789', $context->sessionId);
    }

    /** @test */
    public function it_puts_unknown_keys_into_custom_attributes(): void
    {
        $data = [
            'user_id' => 'user-123',
            'plan' => 'premium',
            'region' => 'APAC',
            'feature_x_enabled' => true,
        ];

        $context = EvaluationContext::fromArray($data);

        $this->assertSame('user-123', $context->userId);
        $this->assertSame([
            'plan' => 'premium',
            'region' => 'APAC',
            'feature_x_enabled' => true,
        ], $context->customAttributes);
    }

    /** @test */
    public function it_handles_empty_array(): void
    {
        $context = EvaluationContext::fromArray([]);

        $this->assertNull($context->tenantId);
        $this->assertNull($context->userId);
        $this->assertNull($context->sessionId);
        $this->assertSame([], $context->customAttributes);
    }

    /** @test */
    public function it_ignores_non_string_identity_values(): void
    {
        $data = [
            'tenant_id' => 123, // int instead of string
            'user_id' => null,
            'session_id' => ['invalid'],
        ];

        $context = EvaluationContext::fromArray($data);

        $this->assertNull($context->tenantId);
        $this->assertNull($context->userId);
        $this->assertNull($context->sessionId);
    }

    /** @test */
    public function it_returns_user_id_as_stable_identifier_first(): void
    {
        $context = new EvaluationContext(
            tenantId: 'tenant-123',
            userId: 'user-456',
            sessionId: 'session-789'
        );

        $this->assertSame('user-456', $context->getStableIdentifier());
    }

    /** @test */
    public function it_returns_session_id_as_stable_identifier_when_no_user_id(): void
    {
        $context = new EvaluationContext(
            tenantId: 'tenant-123',
            sessionId: 'session-789'
        );

        $this->assertSame('session-789', $context->getStableIdentifier());
    }

    /** @test */
    public function it_returns_tenant_id_as_stable_identifier_when_no_user_or_session(): void
    {
        $context = new EvaluationContext(
            tenantId: 'tenant-123'
        );

        $this->assertSame('tenant-123', $context->getStableIdentifier());
    }

    /** @test */
    public function it_returns_null_as_stable_identifier_when_all_empty(): void
    {
        $context = new EvaluationContext();

        $this->assertNull($context->getStableIdentifier());
    }

    /** @test */
    public function it_converts_to_array(): void
    {
        $context = new EvaluationContext(
            tenantId: 'tenant-123',
            userId: 'user-456',
            sessionId: 'session-789',
            customAttributes: ['plan' => 'premium']
        );

        $array = $context->toArray();

        $this->assertSame([
            'tenant_id' => 'tenant-123',
            'user_id' => 'user-456',
            'session_id' => 'session-789',
            'custom_attributes' => ['plan' => 'premium'],
        ], $array);
    }

    /** @test */
    public function it_converts_minimal_context_to_array(): void
    {
        $context = new EvaluationContext();

        $array = $context->toArray();

        $this->assertSame([
            'tenant_id' => null,
            'user_id' => null,
            'session_id' => null,
            'custom_attributes' => [],
        ], $array);
    }

    /** @test */
    public function it_is_immutable(): void
    {
        $context = new EvaluationContext(
            tenantId: 'tenant-123',
            userId: 'user-456'
        );

        // Verify all properties are readonly
        $reflection = new \ReflectionClass($context);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    /** @test */
    public function it_roundtrips_through_array(): void
    {
        $original = new EvaluationContext(
            tenantId: 'tenant-123',
            userId: 'user-456',
            sessionId: 'session-789',
            customAttributes: ['plan' => 'premium', 'country' => 'MY']
        );

        $array = $original->toArray();
        $reconstructed = EvaluationContext::fromArray($array);

        $this->assertSame($original->tenantId, $reconstructed->tenantId);
        $this->assertSame($original->userId, $reconstructed->userId);
        $this->assertSame($original->sessionId, $reconstructed->sessionId);
        $this->assertSame($original->customAttributes, $reconstructed->customAttributes);
    }
}
