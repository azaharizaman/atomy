<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\SSO\ValueObjects\CallbackState;

/**
 * Test for CallbackState value object
 * 
 * TDD Cycle 3: RED phase
 */
final class CallbackStateTest extends TestCase
{
    public function test_it_can_be_created_with_required_fields(): void
    {
        $createdAt = new \DateTimeImmutable('2025-11-24 10:00:00');
        $expiresAt = new \DateTimeImmutable('2025-11-24 10:10:00');

        $state = new CallbackState(
            token: 'random-state-token-123',
            metadata: ['provider' => 'azure', 'tenant_id' => 'T1'],
            createdAt: $createdAt,
            expiresAt: $expiresAt
        );

        $this->assertSame('random-state-token-123', $state->token);
        $this->assertSame(['provider' => 'azure', 'tenant_id' => 'T1'], $state->metadata);
        $this->assertSame($createdAt, $state->createdAt);
        $this->assertSame($expiresAt, $state->expiresAt);
    }

    public function test_it_can_check_if_expired(): void
    {
        $expiredState = new CallbackState(
            token: 'token-1',
            metadata: [],
            createdAt: new \DateTimeImmutable('-20 minutes'),
            expiresAt: new \DateTimeImmutable('-10 minutes')
        );

        $validState = new CallbackState(
            token: 'token-2',
            metadata: [],
            createdAt: new \DateTimeImmutable('-5 minutes'),
            expiresAt: new \DateTimeImmutable('+5 minutes')
        );

        $this->assertTrue($expiredState->isExpired());
        $this->assertFalse($validState->isExpired());
    }

    public function test_it_is_immutable(): void
    {
        $state = new CallbackState(
            token: 'token-123',
            metadata: [],
            createdAt: new \DateTimeImmutable(),
            expiresAt: new \DateTimeImmutable('+10 minutes')
        );

        $this->expectException(\Error::class);
        $state->token = 'modified';
    }
}
