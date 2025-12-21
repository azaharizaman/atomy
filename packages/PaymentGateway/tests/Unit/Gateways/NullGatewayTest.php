<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Gateways;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\DTOs\CaptureRequest;
use Nexus\PaymentGateway\DTOs\RefundRequest;
use Nexus\PaymentGateway\DTOs\VoidRequest;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\GatewayStatus;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\Gateways\NullGateway;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NullGateway::class)]
final class NullGatewayTest extends TestCase
{
    private NullGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = new NullGateway();
    }

    #[Test]
    public function it_returns_provider(): void
    {
        $this->assertSame(GatewayProvider::STRIPE, $this->gateway->getProvider());
    }

    #[Test]
    public function it_returns_name(): void
    {
        $this->assertSame('Null Gateway (Disabled)', $this->gateway->getName());
    }

    #[Test]
    public function it_is_always_initialized(): void
    {
        $this->assertTrue($this->gateway->isInitialized());
    }

    #[Test]
    public function it_does_not_support_3ds(): void
    {
        $this->assertFalse($this->gateway->supports3ds());
    }

    #[Test]
    public function it_does_not_support_tokenization(): void
    {
        $this->assertFalse($this->gateway->supportsTokenization());
    }

    #[Test]
    public function it_does_not_support_partial_capture(): void
    {
        $this->assertFalse($this->gateway->supportsPartialCapture());
    }

    #[Test]
    public function it_does_not_support_partial_refund(): void
    {
        $this->assertFalse($this->gateway->supportsPartialRefund());
    }

    #[Test]
    public function it_does_not_support_void(): void
    {
        $this->assertFalse($this->gateway->supportsVoid());
    }

    #[Test]
    public function it_returns_unavailable_status(): void
    {
        $this->assertSame(GatewayStatus::UNAVAILABLE, $this->gateway->getStatus());
    }

    #[Test]
    public function it_throws_on_authorize(): void
    {
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, 'tok_visa');

        $this->expectException(AuthorizationFailedException::class);
        $this->expectExceptionMessage('disabled');

        $this->gateway->authorize($request);
    }

    #[Test]
    public function it_throws_on_capture(): void
    {
        $request = CaptureRequest::full('auth_123');

        $this->expectException(CaptureFailedException::class);
        $this->expectExceptionMessage('disabled');

        $this->gateway->capture($request);
    }

    #[Test]
    public function it_throws_on_refund(): void
    {
        $request = RefundRequest::full('cap_123');

        $this->expectException(RefundFailedException::class);
        $this->expectExceptionMessage('disabled');

        $this->gateway->refund($request);
    }

    #[Test]
    public function it_throws_on_void(): void
    {
        $request = VoidRequest::create('auth_123');

        $this->expectException(VoidFailedException::class);
        $this->expectExceptionMessage('disabled');

        $this->gateway->void($request);
    }
}
