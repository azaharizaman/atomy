<?php

declare(strict_types=1);

namespace Nexus\SSO\Tests\Unit\Services;

use Nexus\SSO\Contracts\CallbackStateValidatorInterface;
use Nexus\SSO\Contracts\StateStorageInterface;
use Nexus\SSO\Exceptions\InvalidCallbackStateException;
use Nexus\SSO\Services\CallbackStateValidator;
use Nexus\SSO\ValueObjects\CallbackState;
use PHPUnit\Framework\TestCase;

final class CallbackStateValidatorTest extends TestCase
{
    private StateStorageInterface $storage;
    private CallbackStateValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(StateStorageInterface::class);
        $this->validator = new CallbackStateValidator($this->storage, ttlSeconds: 600);
    }

    public function test_it_generates_state_with_metadata(): void
    {
        $metadata = [
            'provider' => 'azure',
            'tenant_id' => 'tenant-123',
            'return_url' => '/dashboard',
        ];

        $this->storage->expects($this->once())
            ->method('store')
            ->with(
                $this->isType('string'),
                $metadata,
                600
            );

        $state = $this->validator->generateState($metadata);

        $this->assertInstanceOf(CallbackState::class, $state);
        $this->assertNotEmpty($state->token);
        $this->assertSame($metadata, $state->metadata);
    }

    public function test_it_validates_existing_state(): void
    {
        $token = 'valid-token-123';
        $metadata = ['provider' => 'google', 'tenant_id' => 'tenant-456'];

        $this->storage->expects($this->once())
            ->method('retrieve')
            ->with($token)
            ->willReturn($metadata);

        $state = $this->validator->validateState($token);

        $this->assertInstanceOf(CallbackState::class, $state);
        $this->assertSame($token, $state->token);
        $this->assertSame($metadata, $state->metadata);
    }

    public function test_it_throws_when_state_not_found(): void
    {
        $this->storage->expects($this->once())
            ->method('retrieve')
            ->with('invalid-token')
            ->willReturn(null);

        $this->expectException(InvalidCallbackStateException::class);
        $this->expectExceptionMessage('Invalid or expired state token');

        $this->validator->validateState('invalid-token');
    }

    public function test_it_throws_when_state_expired(): void
    {
        $token = 'expired-token';

        $this->storage->expects($this->once())
            ->method('retrieve')
            ->with($token)
            ->willReturn(null); // Storage returns null for expired tokens

        $this->expectException(InvalidCallbackStateException::class);

        $this->validator->validateState($token);
    }

    public function test_it_invalidates_state(): void
    {
        $token = 'token-to-invalidate';

        $this->storage->expects($this->once())
            ->method('delete')
            ->with($token);

        $this->validator->invalidateState($token);
    }

    public function test_generated_state_has_unique_token(): void
    {
        $this->storage->expects($this->exactly(2))
            ->method('store');

        $state1 = $this->validator->generateState(['test' => '1']);
        $state2 = $this->validator->generateState(['test' => '2']);

        $this->assertNotSame($state1->token, $state2->token);
    }

    public function test_generated_state_token_has_sufficient_entropy(): void
    {
        $this->storage->expects($this->once())
            ->method('store');

        $state = $this->validator->generateState([]);

        // Token should be at least 32 characters (128 bits of entropy)
        $this->assertGreaterThanOrEqual(32, strlen($state->token));
    }
}
