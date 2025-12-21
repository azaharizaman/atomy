<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\ValueObjects;

use Nexus\PaymentGateway\ValueObjects\GatewayError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GatewayError::class)]
final class GatewayErrorTest extends TestCase
{
    #[Test]
    public function it_creates_error_with_all_parameters(): void
    {
        $error = new GatewayError(
            code: 'test_error',
            message: 'Test error message',
            declineCode: 'card_declined',
            retryable: true,
            type: 'card_error',
            param: 'card_number',
            details: ['additional' => 'info'],
        );

        $this->assertSame('test_error', $error->code);
        $this->assertSame('Test error message', $error->message);
        $this->assertSame('card_declined', $error->declineCode);
        $this->assertTrue($error->retryable);
        $this->assertSame('card_error', $error->type);
        $this->assertSame('card_number', $error->param);
        $this->assertSame(['additional' => 'info'], $error->details);
    }

    #[Test]
    public function it_creates_error_from_array(): void
    {
        $response = [
            'code' => 'api_error',
            'message' => 'API error occurred',
            'decline_code' => 'insufficient_funds',
            'retryable' => true,
            'type' => 'card_error',
            'param' => 'amount',
        ];

        $error = GatewayError::fromArray($response);

        $this->assertSame('api_error', $error->code);
        $this->assertSame('API error occurred', $error->message);
        $this->assertSame('insufficient_funds', $error->declineCode);
        $this->assertTrue($error->retryable);
        $this->assertSame('card_error', $error->type);
        $this->assertSame('amount', $error->param);
    }

    #[Test]
    public function it_handles_alternative_response_keys(): void
    {
        $response = [
            'error_code' => 'alt_error',
            'error_message' => 'Alternative error message',
            'declineCode' => 'expired_card',
            'error_type' => 'validation_error',
        ];

        $error = GatewayError::fromArray($response);

        $this->assertSame('alt_error', $error->code);
        $this->assertSame('Alternative error message', $error->message);
        $this->assertSame('expired_card', $error->declineCode);
        $this->assertSame('validation_error', $error->type);
    }

    #[Test]
    public function it_uses_defaults_for_missing_keys(): void
    {
        $error = GatewayError::fromArray([]);

        $this->assertSame('unknown_error', $error->code);
        $this->assertSame('An unknown error occurred', $error->message);
        $this->assertNull($error->declineCode);
        $this->assertFalse($error->retryable);
    }

    #[Test]
    public function it_creates_card_declined_error(): void
    {
        $error = GatewayError::cardDeclined('insufficient_funds', 'Not enough balance');

        $this->assertSame('card_declined', $error->code);
        $this->assertSame('Not enough balance', $error->message);
        $this->assertSame('insufficient_funds', $error->declineCode);
        $this->assertFalse($error->retryable);
        $this->assertSame('card_error', $error->type);
    }

    #[Test]
    public function it_creates_card_declined_with_defaults(): void
    {
        $error = GatewayError::cardDeclined();

        $this->assertSame('Card was declined', $error->message);
        $this->assertNull($error->declineCode);
    }

    #[Test]
    public function it_creates_expired_card_error(): void
    {
        $error = GatewayError::expiredCard();

        $this->assertSame('expired_card', $error->code);
        $this->assertSame('Card has expired', $error->message);
        $this->assertSame('expired_card', $error->declineCode);
        $this->assertFalse($error->retryable);
    }

    #[Test]
    public function it_creates_insufficient_funds_error(): void
    {
        $error = GatewayError::insufficientFunds();

        $this->assertSame('insufficient_funds', $error->code);
        $this->assertSame('Insufficient funds available', $error->message);
        $this->assertSame('insufficient_funds', $error->declineCode);
    }

    #[Test]
    public function it_creates_retryable_network_error(): void
    {
        $error = GatewayError::networkError('Connection timeout');

        $this->assertSame('network_error', $error->code);
        $this->assertSame('Connection timeout', $error->message);
        $this->assertTrue($error->retryable);
        $this->assertSame('api_error', $error->type);
    }

    #[Test]
    public function it_creates_authentication_error(): void
    {
        $error = GatewayError::authenticationError('Invalid key');

        $this->assertSame('authentication_error', $error->code);
        $this->assertSame('Invalid key', $error->message);
        $this->assertFalse($error->retryable);
        $this->assertSame('authentication_error', $error->type);
    }

    #[Test]
    public function it_identifies_card_errors(): void
    {
        $cardError = new GatewayError(
            code: 'card_declined',
            message: 'Card declined',
            type: 'card_error',
        );
        $this->assertTrue($cardError->isCardError());

        $errorWithDeclineCode = new GatewayError(
            code: 'some_error',
            message: 'Some error',
            declineCode: 'insufficient_funds',
        );
        $this->assertTrue($errorWithDeclineCode->isCardError());

        $nonCardError = new GatewayError(
            code: 'api_error',
            message: 'API error',
            type: 'api_error',
        );
        $this->assertFalse($nonCardError->isCardError());
    }

    #[Test]
    public function it_identifies_validation_errors(): void
    {
        $validationError = new GatewayError(
            code: 'validation',
            message: 'Invalid param',
            type: 'validation_error',
        );
        $this->assertTrue($validationError->isValidationError());

        $errorWithParam = new GatewayError(
            code: 'error',
            message: 'Error with param',
            param: 'amount',
        );
        $this->assertTrue($errorWithParam->isValidationError());

        $nonValidationError = new GatewayError(
            code: 'api_error',
            message: 'API error',
            type: 'api_error',
        );
        $this->assertFalse($nonValidationError->isValidationError());
    }

    #[Test]
    public function it_returns_user_friendly_messages(): void
    {
        $this->assertSame(
            'Your card has insufficient funds.',
            GatewayError::insufficientFunds()->getUserMessage()
        );

        $this->assertSame(
            'Your card has expired.',
            GatewayError::expiredCard()->getUserMessage()
        );

        $cvcError = new GatewayError('cvc', 'CVC error', 'incorrect_cvc');
        $this->assertSame('The security code is incorrect.', $cvcError->getUserMessage());

        $genericDecline = GatewayError::cardDeclined('card_declined');
        $this->assertSame('Your card was declined.', $genericDecline->getUserMessage());

        $lostCard = new GatewayError('lost', 'Lost card', 'lost_card');
        $this->assertSame('This card cannot be used.', $lostCard->getUserMessage());

        $unknownDecline = new GatewayError('unknown', 'Unknown', 'unknown_code');
        $this->assertSame(
            'Your payment was declined. Please try a different payment method.',
            $unknownDecline->getUserMessage()
        );

        $nonCardError = GatewayError::networkError();
        $this->assertSame(
            'An error occurred processing your payment. Please try again.',
            $nonCardError->getUserMessage()
        );
    }
}
