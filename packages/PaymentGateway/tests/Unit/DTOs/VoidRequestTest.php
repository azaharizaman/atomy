<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\DTOs;

use Nexus\PaymentGateway\DTOs\VoidRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VoidRequest::class)]
final class VoidRequestTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_minimal_parameters(): void
    {
        $request = new VoidRequest(
            authorizationId: 'auth_12345',
        );

        $this->assertSame('auth_12345', $request->authorizationId);
        $this->assertNull($request->reason);
        $this->assertSame([], $request->metadata);
        $this->assertNull($request->idempotencyKey);
    }

    #[Test]
    public function it_can_be_created_with_all_parameters(): void
    {
        $metadata = ['source' => 'system', 'triggered_by' => 'timeout'];

        $request = new VoidRequest(
            authorizationId: 'auth_abc',
            reason: 'Customer cancelled order',
            metadata: $metadata,
            idempotencyKey: 'idem_void_123',
        );

        $this->assertSame('auth_abc', $request->authorizationId);
        $this->assertSame('Customer cancelled order', $request->reason);
        $this->assertSame($metadata, $request->metadata);
        $this->assertSame('idem_void_123', $request->idempotencyKey);
    }

    #[Test]
    public function it_creates_void_request_via_factory(): void
    {
        $request = VoidRequest::create(
            authorizationId: 'auth_xyz',
            reason: 'Duplicate authorization',
        );

        $this->assertSame('auth_xyz', $request->authorizationId);
        $this->assertSame('Duplicate authorization', $request->reason);
        $this->assertSame([], $request->metadata);
    }

    #[Test]
    public function it_creates_void_request_without_reason_via_factory(): void
    {
        $request = VoidRequest::create(
            authorizationId: 'auth_simple',
        );

        $this->assertSame('auth_simple', $request->authorizationId);
        $this->assertNull($request->reason);
    }
}
