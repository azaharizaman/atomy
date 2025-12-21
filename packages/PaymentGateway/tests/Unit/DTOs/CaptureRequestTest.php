<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CaptureRequest::class)]
final class CaptureRequestTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_minimal_parameters(): void
    {
        $request = new CaptureRequest(
            authorizationId: 'auth_12345',
        );

        $this->assertSame('auth_12345', $request->authorizationId);
        $this->assertNull($request->amount);
        $this->assertNull($request->description);
        $this->assertSame([], $request->metadata);
        $this->assertNull($request->idempotencyKey);
    }

    #[Test]
    public function it_can_be_created_with_all_parameters(): void
    {
        $amount = Money::of(5000, 'USD');
        $metadata = ['invoice' => 'INV-001'];

        $request = new CaptureRequest(
            authorizationId: 'auth_abc',
            amount: $amount,
            description: 'Partial capture for order',
            metadata: $metadata,
            idempotencyKey: 'idem_cap_123',
        );

        $this->assertSame('auth_abc', $request->authorizationId);
        $this->assertSame($amount, $request->amount);
        $this->assertSame('Partial capture for order', $request->description);
        $this->assertSame($metadata, $request->metadata);
        $this->assertSame('idem_cap_123', $request->idempotencyKey);
    }

    #[Test]
    public function it_creates_full_capture_request_via_factory(): void
    {
        $metadata = ['type' => 'full'];

        $request = CaptureRequest::full(
            authorizationId: 'auth_xyz',
            metadata: $metadata,
        );

        $this->assertSame('auth_xyz', $request->authorizationId);
        $this->assertNull($request->amount);
        $this->assertSame($metadata, $request->metadata);
    }

    #[Test]
    public function it_creates_partial_capture_request_via_factory(): void
    {
        $amount = Money::of(2500, 'EUR');
        $metadata = ['type' => 'partial'];

        $request = CaptureRequest::partial(
            authorizationId: 'auth_partial',
            amount: $amount,
            metadata: $metadata,
        );

        $this->assertSame('auth_partial', $request->authorizationId);
        $this->assertSame($amount, $request->amount);
        $this->assertSame($metadata, $request->metadata);
    }

    #[Test]
    public function it_detects_partial_capture_when_amount_is_set(): void
    {
        $request = CaptureRequest::partial(
            authorizationId: 'auth_test',
            amount: Money::of(1000, 'USD'),
        );

        $this->assertTrue($request->isPartialCapture());
    }

    #[Test]
    public function it_detects_full_capture_when_amount_is_null(): void
    {
        $request = CaptureRequest::full(
            authorizationId: 'auth_test',
        );

        $this->assertFalse($request->isPartialCapture());
    }

    #[Test]
    public function it_returns_amount_in_minor_units_when_set(): void
    {
        $request = CaptureRequest::partial(
            authorizationId: 'auth_test',
            amount: new Money(7550, 'USD'),
        );

        $this->assertSame(7550, $request->getAmountInMinorUnits());
    }

    #[Test]
    public function it_returns_null_for_amount_in_minor_units_when_not_set(): void
    {
        $request = CaptureRequest::full(
            authorizationId: 'auth_test',
        );

        $this->assertNull($request->getAmountInMinorUnits());
    }
}
