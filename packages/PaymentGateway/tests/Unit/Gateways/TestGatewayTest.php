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
use Nexus\PaymentGateway\Enums\TransactionStatus;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\Gateways\TestGateway;
use Nexus\PaymentGateway\ValueObjects\GatewayCredentials;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestGateway::class)]
final class TestGatewayTest extends TestCase
{
    private TestGateway $gateway;

    protected function setUp(): void
    {
        $this->gateway = new TestGateway();
        $this->gateway->initialize(GatewayCredentials::forStripe('sk_test_123', 'pk_test_123'));
    }

    protected function tearDown(): void
    {
        $this->gateway->reset();
    }

    #[Test]
    public function it_returns_provider(): void
    {
        $this->assertSame(GatewayProvider::STRIPE, $this->gateway->getProvider());
    }

    #[Test]
    public function it_returns_name(): void
    {
        $this->assertSame('Test Gateway', $this->gateway->getName());
    }

    #[Test]
    public function it_reports_initialization_status(): void
    {
        $freshGateway = new TestGateway();
        $this->assertFalse($freshGateway->isInitialized());

        $freshGateway->initialize(GatewayCredentials::forStripe('sk', 'pk'));
        $this->assertTrue($freshGateway->isInitialized());
    }

    #[Test]
    public function it_supports_3ds(): void
    {
        $this->assertTrue($this->gateway->supports3ds());
    }

    #[Test]
    public function it_supports_tokenization(): void
    {
        $this->assertTrue($this->gateway->supportsTokenization());
    }

    #[Test]
    public function it_sets_and_gets_status(): void
    {
        $this->assertSame(GatewayStatus::HEALTHY, $this->gateway->getStatus());

        $this->gateway->setStatus(GatewayStatus::DEGRADED);
        $this->assertSame(GatewayStatus::DEGRADED, $this->gateway->getStatus());

        $this->gateway->setStatus(GatewayStatus::UNAVAILABLE);
        $this->assertSame(GatewayStatus::UNAVAILABLE, $this->gateway->getStatus());
    }

    // Authorization Tests

    #[Test]
    public function it_authorizes_payment_successfully(): void
    {
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, 'tok_success_visa');

        $result = $this->gateway->authorize($request);

        $this->assertTrue($result->success);
        $this->assertSame(TransactionStatus::AUTHORIZED, $result->status);
        $this->assertNotEmpty($result->authorizationId);
        $this->assertNotEmpty($result->transactionId);
        $this->assertSame($amount, $result->authorizedAmount);
    }

    #[Test]
    public function it_handles_auto_capture(): void
    {
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forCapture($amount, 'tok_success_visa');

        $result = $this->gateway->authorize($request);

        $this->assertTrue($result->success);
        $this->assertSame(TransactionStatus::CAPTURED, $result->status);
    }

    #[Test]
    public function it_throws_on_declined_card(): void
    {
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, TestGateway::TOKEN_DECLINE . '_visa');

        $this->expectException(AuthorizationFailedException::class);

        $this->gateway->authorize($request);
    }

    #[Test]
    public function it_throws_on_gateway_error_token(): void
    {
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, TestGateway::TOKEN_ERROR . '_card');

        $this->expectException(AuthorizationFailedException::class);

        $this->gateway->authorize($request);
    }

    #[Test]
    public function it_requires_3ds_for_3ds_token(): void
    {
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, TestGateway::TOKEN_3DS . '_visa');

        $result = $this->gateway->authorize($request);

        $this->assertTrue($result->requires3ds);
        $this->assertNotEmpty($result->threeDsUrl);
    }

    #[Test]
    public function it_validates_zero_amount(): void
    {
        $amount = Money::of(0, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, 'tok_success');

        $this->expectException(AuthorizationFailedException::class);

        $this->gateway->authorize($request);
    }

    #[Test]
    public function it_validates_empty_token(): void
    {
        $amount = Money::of(10000, 'USD');
        $request = AuthorizeRequest::forPreAuth($amount, '');

        $this->expectException(AuthorizationFailedException::class);

        $this->gateway->authorize($request);
    }

    // Capture Tests

    #[Test]
    public function it_captures_authorization(): void
    {
        $amount = Money::of(10000, 'USD');
        $authRequest = AuthorizeRequest::forPreAuth($amount, 'tok_success');
        $authResult = $this->gateway->authorize($authRequest);

        $captureRequest = CaptureRequest::full($authResult->authorizationId);
        $captureResult = $this->gateway->capture($captureRequest);

        $this->assertTrue($captureResult->success);
        $this->assertSame($amount, $captureResult->capturedAmount);
        $this->assertNotEmpty($captureResult->captureId);
    }

    #[Test]
    public function it_captures_partial_amount(): void
    {
        $amount = Money::of(10000, 'USD');
        $partialAmount = Money::of(5000, 'USD');
        $authRequest = AuthorizeRequest::forPreAuth($amount, 'tok_success');
        $authResult = $this->gateway->authorize($authRequest);

        $captureRequest = CaptureRequest::partial($authResult->authorizationId, $partialAmount);
        $captureResult = $this->gateway->capture($captureRequest);

        $this->assertTrue($captureResult->success);
        $this->assertSame($partialAmount, $captureResult->capturedAmount);
    }

    #[Test]
    public function it_throws_when_capturing_non_existent_authorization(): void
    {
        $captureRequest = CaptureRequest::full('auth_not_found');

        $this->expectException(CaptureFailedException::class);

        $this->gateway->capture($captureRequest);
    }

    #[Test]
    public function it_throws_when_capturing_already_captured(): void
    {
        $amount = Money::of(10000, 'USD');
        $authRequest = AuthorizeRequest::forPreAuth($amount, 'tok_success');
        $authResult = $this->gateway->authorize($authRequest);

        // First capture succeeds
        $this->gateway->capture(CaptureRequest::full($authResult->authorizationId));

        // Second capture fails
        $this->expectException(CaptureFailedException::class);
        $this->gateway->capture(CaptureRequest::full($authResult->authorizationId));
    }

    #[Test]
    public function it_throws_when_capture_amount_exceeds_authorization(): void
    {
        $amount = Money::of(10000, 'USD');
        $authRequest = AuthorizeRequest::forPreAuth($amount, 'tok_success');
        $authResult = $this->gateway->authorize($authRequest);

        $excessAmount = Money::of(20000, 'USD');
        $captureRequest = CaptureRequest::partial($authResult->authorizationId, $excessAmount);

        $this->expectException(CaptureFailedException::class);

        $this->gateway->capture($captureRequest);
    }

    #[Test]
    public function it_validates_empty_authorization_id(): void
    {
        $captureRequest = CaptureRequest::full('');

        $this->expectException(CaptureFailedException::class);

        $this->gateway->capture($captureRequest);
    }

    // Refund Tests

    #[Test]
    public function it_refunds_captured_payment(): void
    {
        // Setup: authorize and capture
        $amount = Money::of(10000, 'USD');
        $authResult = $this->gateway->authorize(AuthorizeRequest::forPreAuth($amount, 'tok_success'));
        $captureResult = $this->gateway->capture(CaptureRequest::full($authResult->authorizationId));

        // Full refund
        $refundRequest = RefundRequest::full($captureResult->captureId);
        $refundResult = $this->gateway->refund($refundRequest);

        $this->assertTrue($refundResult->success);
        $this->assertNotEmpty($refundResult->refundId);
        $this->assertEquals($amount, $refundResult->refundedAmount);
    }

    #[Test]
    public function it_refunds_partial_amount(): void
    {
        $amount = Money::of(10000, 'USD');
        $partialAmount = Money::of(3000, 'USD');

        $authResult = $this->gateway->authorize(AuthorizeRequest::forPreAuth($amount, 'tok_success'));
        $captureResult = $this->gateway->capture(CaptureRequest::full($authResult->authorizationId));

        $refundRequest = RefundRequest::partial($captureResult->captureId, $partialAmount);
        $refundResult = $this->gateway->refund($refundRequest);

        $this->assertTrue($refundResult->success);
        $this->assertEquals($partialAmount, $refundResult->refundedAmount);
    }

    #[Test]
    public function it_throws_when_refunding_non_existent_capture(): void
    {
        $refundRequest = RefundRequest::full('cap_not_found');

        $this->expectException(RefundFailedException::class);

        $this->gateway->refund($refundRequest);
    }

    #[Test]
    public function it_throws_when_refund_amount_exceeds_captured(): void
    {
        $amount = Money::of(10000, 'USD');
        $authResult = $this->gateway->authorize(AuthorizeRequest::forPreAuth($amount, 'tok_success'));
        $captureResult = $this->gateway->capture(CaptureRequest::full($authResult->authorizationId));

        $excessAmount = Money::of(20000, 'USD');
        $refundRequest = RefundRequest::partial($captureResult->captureId, $excessAmount);

        $this->expectException(RefundFailedException::class);

        $this->gateway->refund($refundRequest);
    }

    #[Test]
    public function it_validates_empty_capture_id(): void
    {
        $refundRequest = RefundRequest::full('');

        $this->expectException(RefundFailedException::class);

        $this->gateway->refund($refundRequest);
    }

    // Void Tests

    #[Test]
    public function it_voids_authorization(): void
    {
        $amount = Money::of(10000, 'USD');
        $authResult = $this->gateway->authorize(AuthorizeRequest::forPreAuth($amount, 'tok_success'));

        $voidRequest = VoidRequest::create($authResult->authorizationId);
        $voidResult = $this->gateway->void($voidRequest);

        $this->assertTrue($voidResult->success);
        $this->assertNotEmpty($voidResult->voidId);
    }

    #[Test]
    public function it_throws_when_voiding_non_existent_authorization(): void
    {
        $voidRequest = VoidRequest::create('auth_not_found');

        $this->expectException(VoidFailedException::class);

        $this->gateway->void($voidRequest);
    }

    #[Test]
    public function it_throws_when_voiding_captured_authorization(): void
    {
        $amount = Money::of(10000, 'USD');
        $authResult = $this->gateway->authorize(AuthorizeRequest::forPreAuth($amount, 'tok_success'));
        $this->gateway->capture(CaptureRequest::full($authResult->authorizationId));

        $voidRequest = VoidRequest::create($authResult->authorizationId);

        $this->expectException(VoidFailedException::class);

        $this->gateway->void($voidRequest);
    }

    #[Test]
    public function it_validates_empty_void_authorization_id(): void
    {
        $voidRequest = VoidRequest::create('');

        $this->expectException(VoidFailedException::class);

        $this->gateway->void($voidRequest);
    }

    // State Inspection Tests

    #[Test]
    public function it_stores_authorizations(): void
    {
        $amount = Money::of(10000, 'USD');
        $this->gateway->authorize(AuthorizeRequest::forPreAuth($amount, 'tok_success'));

        $authorizations = $this->gateway->getAuthorizations();

        $this->assertCount(1, $authorizations);
    }

    #[Test]
    public function it_stores_captures(): void
    {
        $amount = Money::of(10000, 'USD');
        $authResult = $this->gateway->authorize(AuthorizeRequest::forPreAuth($amount, 'tok_success'));
        $this->gateway->capture(CaptureRequest::full($authResult->authorizationId));

        $captures = $this->gateway->getCaptures();

        $this->assertCount(1, $captures);
    }

    #[Test]
    public function it_resets_stored_transactions(): void
    {
        $amount = Money::of(10000, 'USD');
        $authResult = $this->gateway->authorize(AuthorizeRequest::forPreAuth($amount, 'tok_success'));
        $this->gateway->capture(CaptureRequest::full($authResult->authorizationId));

        $this->assertNotEmpty($this->gateway->getAuthorizations());
        $this->assertNotEmpty($this->gateway->getCaptures());

        $this->gateway->reset();

        $this->assertEmpty($this->gateway->getAuthorizations());
        $this->assertEmpty($this->gateway->getCaptures());
    }
}
