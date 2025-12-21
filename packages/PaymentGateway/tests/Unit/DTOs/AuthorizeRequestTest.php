<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\DTOs\AuthorizeRequest;
use Nexus\PaymentGateway\Enums\AuthorizationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthorizeRequest::class)]
final class AuthorizeRequestTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_minimal_parameters(): void
    {
        $amount = Money::of(10000, 'USD');
        $request = new AuthorizeRequest(
            amount: $amount,
            paymentMethodToken: 'tok_visa',
        );

        $this->assertSame($amount, $request->amount);
        $this->assertSame('tok_visa', $request->paymentMethodToken);
        $this->assertNull($request->customerId);
        $this->assertSame(AuthorizationType::PREAUTH, $request->authorizationType);
        $this->assertNull($request->description);
        $this->assertNull($request->statementDescriptor);
        $this->assertNull($request->orderId);
        $this->assertNull($request->customerEmail);
        $this->assertNull($request->customerIp);
        $this->assertFalse($request->capture);
        $this->assertSame([], $request->billingAddress);
        $this->assertSame([], $request->shippingAddress);
        $this->assertSame([], $request->metadata);
        $this->assertNull($request->idempotencyKey);
    }

    #[Test]
    public function it_can_be_created_with_all_parameters(): void
    {
        $amount = Money::of(25000, 'EUR');
        $billingAddress = [
            'line1' => '123 Main St',
            'city' => 'Berlin',
            'country' => 'DE',
        ];
        $shippingAddress = [
            'line1' => '456 Oak Ave',
            'city' => 'Munich',
            'country' => 'DE',
        ];
        $metadata = ['order_id' => 'ORDER-123', 'source' => 'web'];

        $request = new AuthorizeRequest(
            amount: $amount,
            paymentMethodToken: 'tok_mastercard',
            customerId: 'cus_12345',
            authorizationType: AuthorizationType::AUTH_CAPTURE,
            description: 'Order #123 payment',
            statementDescriptor: 'ACME CORP',
            orderId: 'ORDER-123',
            customerEmail: 'john@example.com',
            customerIp: '192.168.1.1',
            capture: true,
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
            metadata: $metadata,
            idempotencyKey: 'idem_abc123',
        );

        $this->assertSame($amount, $request->amount);
        $this->assertSame('tok_mastercard', $request->paymentMethodToken);
        $this->assertSame('cus_12345', $request->customerId);
        $this->assertSame(AuthorizationType::AUTH_CAPTURE, $request->authorizationType);
        $this->assertSame('Order #123 payment', $request->description);
        $this->assertSame('ACME CORP', $request->statementDescriptor);
        $this->assertSame('ORDER-123', $request->orderId);
        $this->assertSame('john@example.com', $request->customerEmail);
        $this->assertSame('192.168.1.1', $request->customerIp);
        $this->assertTrue($request->capture);
        $this->assertSame($billingAddress, $request->billingAddress);
        $this->assertSame($shippingAddress, $request->shippingAddress);
        $this->assertSame($metadata, $request->metadata);
        $this->assertSame('idem_abc123', $request->idempotencyKey);
    }

    #[Test]
    public function it_creates_capture_request_via_factory(): void
    {
        $amount = Money::of(5000, 'USD');
        $metadata = ['source' => 'api'];

        $request = AuthorizeRequest::forCapture(
            amount: $amount,
            paymentMethodToken: 'tok_visa',
            customerId: 'cus_abc',
            description: 'Test capture',
            metadata: $metadata,
        );

        $this->assertSame($amount, $request->amount);
        $this->assertSame('tok_visa', $request->paymentMethodToken);
        $this->assertSame('cus_abc', $request->customerId);
        $this->assertSame(AuthorizationType::AUTH_CAPTURE, $request->authorizationType);
        $this->assertSame('Test capture', $request->description);
        $this->assertTrue($request->capture);
        $this->assertSame($metadata, $request->metadata);
    }

    #[Test]
    public function it_creates_preauth_request_via_factory(): void
    {
        $amount = Money::of(7500, 'GBP');
        $metadata = ['channel' => 'mobile'];

        $request = AuthorizeRequest::forPreAuth(
            amount: $amount,
            paymentMethodToken: 'tok_amex',
            customerId: 'cus_xyz',
            description: 'Test preauth',
            metadata: $metadata,
        );

        $this->assertSame($amount, $request->amount);
        $this->assertSame('tok_amex', $request->paymentMethodToken);
        $this->assertSame('cus_xyz', $request->customerId);
        $this->assertSame(AuthorizationType::PREAUTH, $request->authorizationType);
        $this->assertSame('Test preauth', $request->description);
        $this->assertFalse($request->capture);
        $this->assertSame($metadata, $request->metadata);
    }

    #[Test]
    public function it_detects_auto_capture_when_capture_flag_is_true(): void
    {
        $request = new AuthorizeRequest(
            amount: Money::of(1000, 'USD'),
            paymentMethodToken: 'tok_test',
            capture: true,
        );

        $this->assertTrue($request->isAutoCapture());
    }

    #[Test]
    public function it_detects_auto_capture_when_auth_type_is_auth_capture(): void
    {
        $request = new AuthorizeRequest(
            amount: Money::of(1000, 'USD'),
            paymentMethodToken: 'tok_test',
            authorizationType: AuthorizationType::AUTH_CAPTURE,
        );

        $this->assertTrue($request->isAutoCapture());
    }

    #[Test]
    public function it_detects_non_auto_capture(): void
    {
        $request = new AuthorizeRequest(
            amount: Money::of(1000, 'USD'),
            paymentMethodToken: 'tok_test',
            authorizationType: AuthorizationType::PREAUTH,
            capture: false,
        );

        $this->assertFalse($request->isAutoCapture());
    }

    #[Test]
    public function it_returns_amount_in_minor_units(): void
    {
        $request = new AuthorizeRequest(
            amount: new Money(15050, 'USD'),
            paymentMethodToken: 'tok_test',
        );

        $this->assertSame(15050, $request->getAmountInMinorUnits());
    }

    #[Test]
    public function it_returns_currency_code(): void
    {
        $request = new AuthorizeRequest(
            amount: Money::of(1000, 'EUR'),
            paymentMethodToken: 'tok_test',
        );

        $this->assertSame('EUR', $request->getCurrency());
    }
}
