<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Exceptions;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Exceptions\AuthorizationFailedException;
use Nexus\PaymentGateway\Exceptions\CaptureFailedException;
use Nexus\PaymentGateway\Exceptions\CredentialsNotFoundException;
use Nexus\PaymentGateway\Exceptions\GatewayException;
use Nexus\PaymentGateway\Exceptions\GatewayNotFoundException;
use Nexus\PaymentGateway\Exceptions\InvalidCredentialsException;
use Nexus\PaymentGateway\Exceptions\RefundFailedException;
use Nexus\PaymentGateway\Exceptions\TokenizationFailedException;
use Nexus\PaymentGateway\Exceptions\TokenNotFoundException;
use Nexus\PaymentGateway\Exceptions\VoidFailedException;
use Nexus\PaymentGateway\Exceptions\WebhookParsingException;
use Nexus\PaymentGateway\Exceptions\WebhookProcessingException;
use Nexus\PaymentGateway\Exceptions\WebhookVerificationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GatewayException::class)]
#[CoversClass(AuthorizationFailedException::class)]
#[CoversClass(CaptureFailedException::class)]
#[CoversClass(CredentialsNotFoundException::class)]
#[CoversClass(GatewayNotFoundException::class)]
#[CoversClass(InvalidCredentialsException::class)]
#[CoversClass(RefundFailedException::class)]
#[CoversClass(TokenizationFailedException::class)]
#[CoversClass(TokenNotFoundException::class)]
#[CoversClass(VoidFailedException::class)]
#[CoversClass(WebhookParsingException::class)]
#[CoversClass(WebhookProcessingException::class)]
#[CoversClass(WebhookVerificationFailedException::class)]
final class ExceptionsTest extends TestCase
{
    // GatewayException Tests

    #[Test]
    public function gateway_exception_stores_properties(): void
    {
        $exception = new GatewayException(
            message: 'Gateway error',
            gatewayErrorCode: 'err_123',
            gatewayMessage: 'Something went wrong'
        );

        $this->assertSame('Gateway error', $exception->getMessage());
        $this->assertSame('err_123', $exception->gatewayErrorCode);
        $this->assertSame('Something went wrong', $exception->gatewayMessage);
    }

    #[Test]
    public function gateway_exception_creates_from_gateway_response(): void
    {
        $exception = GatewayException::fromGatewayResponse(
            message: 'Test error',
            errorCode: 'test_error',
            gatewayMessage: 'Test message'
        );

        $this->assertSame('Test error', $exception->getMessage());
        $this->assertSame('test_error', $exception->gatewayErrorCode);
        $this->assertSame('Test message', $exception->gatewayMessage);
    }

    #[Test]
    public function gateway_exception_preserves_previous_exception(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new GatewayException('Main error', previous: $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    // AuthorizationFailedException Tests

    #[Test]
    public function authorization_failed_creates_card_declined(): void
    {
        $amount = Money::of(10000, 'USD');
        $exception = AuthorizationFailedException::cardDeclined($amount, 'do_not_honor', 'Card was declined');

        $this->assertSame('Card was declined', $exception->getMessage());
        $this->assertSame($amount, $exception->attemptedAmount);
        $this->assertSame('do_not_honor', $exception->gatewayErrorCode);
        $this->assertNotNull($exception->error);
    }

    #[Test]
    public function authorization_failed_creates_insufficient_funds(): void
    {
        $amount = Money::of(10000, 'USD');
        $exception = AuthorizationFailedException::insufficientFunds($amount);

        $this->assertStringContainsString('Insufficient', $exception->getMessage());
        $this->assertSame('insufficient_funds', $exception->gatewayErrorCode);
    }

    #[Test]
    public function authorization_failed_creates_expired_card(): void
    {
        $amount = Money::of(10000, 'USD');
        $exception = AuthorizationFailedException::expiredCard($amount);

        $this->assertStringContainsString('expired', $exception->getMessage());
        $this->assertSame('expired_card', $exception->gatewayErrorCode);
    }

    #[Test]
    public function authorization_failed_creates_from_gateway_response(): void
    {
        $exception = AuthorizationFailedException::fromGatewayResponse(
            message: 'Auth failed',
            errorCode: 'auth_error',
            gatewayMessage: 'Gateway message'
        );

        $this->assertSame('Auth failed', $exception->getMessage());
        $this->assertSame('auth_error', $exception->gatewayErrorCode);
    }

    // CaptureFailedException Tests

    #[Test]
    public function capture_failed_creates_authorization_expired(): void
    {
        $exception = CaptureFailedException::authorizationExpired('auth_123');

        $this->assertStringContainsString('expired', $exception->getMessage());
        $this->assertSame('auth_123', $exception->authorizationId);
        $this->assertSame('authorization_expired', $exception->gatewayErrorCode);
    }

    #[Test]
    public function capture_failed_creates_already_captured(): void
    {
        $exception = CaptureFailedException::alreadyCaptured('auth_456');

        $this->assertStringContainsString('already', $exception->getMessage());
        $this->assertSame('auth_456', $exception->authorizationId);
        $this->assertSame('already_captured', $exception->gatewayErrorCode);
    }

    #[Test]
    public function capture_failed_creates_amount_exceeds_authorization(): void
    {
        $attempted = Money::of(20000, 'USD');
        $authorized = Money::of(10000, 'USD');
        $exception = CaptureFailedException::amountExceedsAuthorization('auth_789', $attempted, $authorized);

        $this->assertStringContainsString('exceeds', $exception->getMessage());
        $this->assertSame($attempted, $exception->attemptedAmount);
        $this->assertSame('amount_exceeds_authorization', $exception->gatewayErrorCode);
    }

    #[Test]
    public function capture_failed_creates_from_gateway_response(): void
    {
        $exception = CaptureFailedException::fromGatewayResponse(
            errorCode: 'cap_error',
            gatewayMessage: 'Capture failed message',
            authorizationId: 'auth_abc'
        );

        $this->assertSame('Capture failed message', $exception->getMessage());
        $this->assertSame('cap_error', $exception->gatewayErrorCode);
    }

    // RefundFailedException Tests

    #[Test]
    public function refund_failed_creates_already_refunded(): void
    {
        $exception = RefundFailedException::alreadyRefunded('txn_123');

        $this->assertStringContainsString('refunded', $exception->getMessage());
        $this->assertSame('txn_123', $exception->transactionId);
        $this->assertSame('already_refunded', $exception->gatewayErrorCode);
    }

    #[Test]
    public function refund_failed_creates_amount_exceeds_capture(): void
    {
        $attempted = Money::of(15000, 'USD');
        $captured = Money::of(10000, 'USD');
        $exception = RefundFailedException::amountExceedsCapture('txn_456', $attempted, $captured);

        $this->assertStringContainsString('exceeds', $exception->getMessage());
        $this->assertSame($attempted, $exception->attemptedAmount);
        $this->assertSame('amount_exceeds_capture', $exception->gatewayErrorCode);
    }

    #[Test]
    public function refund_failed_creates_refund_window_expired(): void
    {
        $exception = RefundFailedException::refundWindowExpired('txn_789');

        $this->assertStringContainsString('expired', $exception->getMessage());
        $this->assertSame('txn_789', $exception->transactionId);
        $this->assertSame('refund_window_expired', $exception->gatewayErrorCode);
    }

    #[Test]
    public function refund_failed_creates_from_gateway_response(): void
    {
        $exception = RefundFailedException::fromGatewayResponse(
            errorCode: 'ref_error',
            gatewayMessage: 'Refund failed message',
            transactionId: 'txn_abc'
        );

        $this->assertSame('Refund failed message', $exception->getMessage());
        $this->assertSame('ref_error', $exception->gatewayErrorCode);
    }

    // VoidFailedException Tests

    #[Test]
    public function void_failed_creates_already_captured(): void
    {
        $exception = VoidFailedException::alreadyCaptured('auth_123');

        $this->assertStringContainsString('captured', $exception->getMessage());
        $this->assertSame('auth_123', $exception->authorizationId);
        $this->assertSame('already_captured', $exception->gatewayErrorCode);
    }

    #[Test]
    public function void_failed_creates_already_voided(): void
    {
        $exception = VoidFailedException::alreadyVoided('auth_456');

        $this->assertStringContainsString('voided', $exception->getMessage());
        $this->assertSame('auth_456', $exception->authorizationId);
        $this->assertSame('already_voided', $exception->gatewayErrorCode);
    }

    #[Test]
    public function void_failed_creates_void_not_supported(): void
    {
        $exception = VoidFailedException::voidNotSupported('auth_789');

        $this->assertStringContainsString('not supported', $exception->getMessage());
        $this->assertSame('auth_789', $exception->authorizationId);
        $this->assertSame('void_not_supported', $exception->gatewayErrorCode);
    }

    #[Test]
    public function void_failed_creates_from_gateway_response(): void
    {
        $exception = VoidFailedException::fromGatewayResponse(
            errorCode: 'void_error',
            gatewayMessage: 'Void failed message',
            authorizationId: 'auth_abc'
        );

        $this->assertSame('Void failed message', $exception->getMessage());
        $this->assertSame('void_error', $exception->gatewayErrorCode);
    }

    // TokenNotFoundException Tests

    #[Test]
    public function token_not_found_stores_token_id(): void
    {
        $exception = new TokenNotFoundException('tok_xyz');

        $this->assertSame('tok_xyz', $exception->tokenId);
        $this->assertStringContainsString('tok_xyz', $exception->getMessage());
        $this->assertSame('token_not_found', $exception->gatewayErrorCode);
    }

    // TokenizationFailedException Tests

    #[Test]
    public function tokenization_failed_creates_invalid_card_data(): void
    {
        $exception = TokenizationFailedException::invalidCardData('CVV missing');

        $this->assertStringContainsString('Invalid card', $exception->getMessage());
        $this->assertSame('CVV missing', $exception->reason);
        $this->assertSame('invalid_card_data', $exception->gatewayErrorCode);
    }

    #[Test]
    public function tokenization_failed_creates_card_not_supported(): void
    {
        $exception = TokenizationFailedException::cardNotSupported('Discover');

        $this->assertStringContainsString('Discover', $exception->getMessage());
        $this->assertSame('unsupported_card_type', $exception->reason);
        $this->assertSame('card_not_supported', $exception->gatewayErrorCode);
    }

    #[Test]
    public function tokenization_failed_creates_service_unavailable(): void
    {
        $exception = TokenizationFailedException::serviceUnavailable();

        $this->assertStringContainsString('unavailable', $exception->getMessage());
        $this->assertSame('service_unavailable', $exception->reason);
        $this->assertSame('service_unavailable', $exception->gatewayErrorCode);
    }

    // GatewayNotFoundException Tests

    #[Test]
    public function gateway_not_found_stores_provider(): void
    {
        $exception = new GatewayNotFoundException(GatewayProvider::STRIPE);

        $this->assertSame(GatewayProvider::STRIPE, $exception->provider);
        $this->assertStringContainsString('stripe', $exception->getMessage());
        $this->assertSame('gateway_not_found', $exception->gatewayErrorCode);
    }

    // CredentialsNotFoundException Tests

    #[Test]
    public function credentials_not_found_stores_tenant_and_provider(): void
    {
        $exception = new CredentialsNotFoundException('tenant_123', GatewayProvider::PAYPAL);

        $this->assertSame('tenant_123', $exception->tenantId);
        $this->assertSame(GatewayProvider::PAYPAL, $exception->provider);
        $this->assertStringContainsString('tenant_123', $exception->getMessage());
        $this->assertStringContainsString('paypal', $exception->getMessage());
        $this->assertSame('credentials_not_found', $exception->gatewayErrorCode);
    }

    // InvalidCredentialsException Tests

    #[Test]
    public function invalid_credentials_uses_default_message(): void
    {
        $exception = new InvalidCredentialsException();

        $this->assertStringContainsString('Invalid', $exception->getMessage());
        $this->assertSame('invalid_credentials', $exception->gatewayErrorCode);
    }

    #[Test]
    public function invalid_credentials_uses_custom_message(): void
    {
        $exception = new InvalidCredentialsException('API key expired');

        $this->assertSame('API key expired', $exception->getMessage());
    }

    // WebhookParsingException Tests

    #[Test]
    public function webhook_parsing_uses_default_message(): void
    {
        $exception = new WebhookParsingException();

        $this->assertStringContainsString('parse', $exception->getMessage());
        $this->assertSame('webhook_parse_error', $exception->gatewayErrorCode);
    }

    #[Test]
    public function webhook_parsing_creates_invalid_json(): void
    {
        $exception = WebhookParsingException::invalidJson('Unexpected token');

        $this->assertStringContainsString('Invalid JSON', $exception->getMessage());
        $this->assertStringContainsString('Unexpected token', $exception->getMessage());
    }

    #[Test]
    public function webhook_parsing_creates_missing_field(): void
    {
        $exception = WebhookParsingException::missingField('event_type');

        $this->assertStringContainsString('Missing', $exception->getMessage());
        $this->assertStringContainsString('event_type', $exception->getMessage());
    }

    // WebhookProcessingException Tests

    #[Test]
    public function webhook_processing_uses_default_message(): void
    {
        $exception = new WebhookProcessingException();

        $this->assertStringContainsString('process', $exception->getMessage());
        $this->assertSame('webhook_processing_failed', $exception->gatewayErrorCode);
    }

    #[Test]
    public function webhook_processing_creates_unsupported_event(): void
    {
        $exception = WebhookProcessingException::unsupportedEvent('customer.deleted');

        $this->assertStringContainsString('Unsupported', $exception->getMessage());
        $this->assertStringContainsString('customer.deleted', $exception->getMessage());
    }

    #[Test]
    public function webhook_processing_creates_duplicate_event(): void
    {
        $exception = WebhookProcessingException::duplicateEvent('evt_123');

        $this->assertStringContainsString('already processed', $exception->getMessage());
        $this->assertStringContainsString('evt_123', $exception->getMessage());
    }

    // WebhookVerificationFailedException Tests

    #[Test]
    public function webhook_verification_creates_invalid_signature(): void
    {
        $exception = WebhookVerificationFailedException::invalidSignature(GatewayProvider::STRIPE);

        $this->assertSame(GatewayProvider::STRIPE, $exception->provider);
        $this->assertSame('invalid_signature', $exception->reason);
        $this->assertStringContainsString('signature', $exception->getMessage());
    }

    #[Test]
    public function webhook_verification_creates_expired_timestamp(): void
    {
        $exception = WebhookVerificationFailedException::expiredTimestamp(GatewayProvider::PAYPAL);

        $this->assertSame(GatewayProvider::PAYPAL, $exception->provider);
        $this->assertSame('expired_timestamp', $exception->reason);
        $this->assertStringContainsString('expired', $exception->getMessage());
    }

    #[Test]
    public function webhook_verification_creates_missing_signature(): void
    {
        $exception = WebhookVerificationFailedException::missingSignature(GatewayProvider::SQUARE);

        $this->assertSame(GatewayProvider::SQUARE, $exception->provider);
        $this->assertSame('missing_signature', $exception->reason);
        $this->assertStringContainsString('missing', $exception->getMessage());
    }
}
