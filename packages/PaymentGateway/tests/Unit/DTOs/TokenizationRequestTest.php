<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\DTOs;

use Nexus\PaymentGateway\DTOs\TokenizationRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenizationRequest::class)]
final class TokenizationRequestTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_minimal_parameters(): void
    {
        $request = new TokenizationRequest(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $this->assertSame('4242424242424242', $request->cardNumber);
        $this->assertSame(12, $request->expiryMonth);
        $this->assertSame(2030, $request->expiryYear);
        $this->assertSame('123', $request->cvv);
        $this->assertNull($request->cardholderName);
        $this->assertNull($request->customerId);
        $this->assertSame([], $request->billingAddress);
        $this->assertSame([], $request->metadata);
    }

    #[Test]
    public function it_can_be_created_with_all_parameters(): void
    {
        $billingAddress = [
            'line1' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'postal_code' => '10001',
        ];
        $metadata = ['source' => 'checkout', 'channel' => 'web'];

        $request = new TokenizationRequest(
            cardNumber: '5555555555554444',
            expiryMonth: 6,
            expiryYear: 2028,
            cvv: '456',
            cardholderName: 'John Doe',
            customerId: 'cus_12345',
            billingAddress: $billingAddress,
            metadata: $metadata,
        );

        $this->assertSame('5555555555554444', $request->cardNumber);
        $this->assertSame(6, $request->expiryMonth);
        $this->assertSame(2028, $request->expiryYear);
        $this->assertSame('456', $request->cvv);
        $this->assertSame('John Doe', $request->cardholderName);
        $this->assertSame('cus_12345', $request->customerId);
        $this->assertSame($billingAddress, $request->billingAddress);
        $this->assertSame($metadata, $request->metadata);
    }

    #[Test]
    public function it_creates_request_via_from_card_factory(): void
    {
        $request = TokenizationRequest::fromCard(
            cardNumber: '4242424242424242',
            expiryMonth: 11,
            expiryYear: 2027,
            cvv: '789',
            cardholderName: 'Jane Smith',
            customerId: 'cus_abc',
        );

        $this->assertSame('4242424242424242', $request->cardNumber);
        $this->assertSame(11, $request->expiryMonth);
        $this->assertSame(2027, $request->expiryYear);
        $this->assertSame('789', $request->cvv);
        $this->assertSame('Jane Smith', $request->cardholderName);
        $this->assertSame('cus_abc', $request->customerId);
    }

    #[Test]
    public function it_strips_whitespace_from_card_number_via_factory(): void
    {
        $request = TokenizationRequest::fromCard(
            cardNumber: '4242 4242 4242 4242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $this->assertSame('4242424242424242', $request->cardNumber);
    }

    #[Test]
    public function it_strips_tabs_and_newlines_from_card_number(): void
    {
        $request = TokenizationRequest::fromCard(
            cardNumber: "4242\t4242\n4242\r4242",
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $this->assertSame('4242424242424242', $request->cardNumber);
    }

    #[Test]
    public function it_returns_last_four_digits(): void
    {
        $request = new TokenizationRequest(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $this->assertSame('4242', $request->getLastFour());
    }

    #[Test]
    #[DataProvider('maskedNumberProvider')]
    public function it_returns_masked_card_number(string $cardNumber, string $expected): void
    {
        $request = new TokenizationRequest(
            cardNumber: $cardNumber,
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $this->assertSame($expected, $request->getMaskedNumber());
    }

    public static function maskedNumberProvider(): array
    {
        return [
            '16 digit card' => ['4242424242424242', '4242********4242'],
            '15 digit card (amex)' => ['378282246310005', '3782*******0005'],
            '19 digit card' => ['6011111111111111111', '6011***********1111'],
            '9 digit card' => ['123456789', '1234*6789'],
            '8 digit minimum (no middle to mask)' => ['12345678', '12345678'],
            '7 digit card returns all stars' => ['1234567', '*******'],
            '4 digit card returns all stars' => ['1234', '****'],
        ];
    }

    #[Test]
    public function it_detects_expired_card_when_year_is_past(): void
    {
        $request = new TokenizationRequest(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2020,
            cvv: '123',
        );

        $this->assertTrue($request->isExpired());
    }

    #[Test]
    public function it_detects_expired_card_when_month_is_past_in_current_year(): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        // Only test if we're not in January (so we have a past month to test)
        if ($currentMonth > 1) {
            $request = new TokenizationRequest(
                cardNumber: '4242424242424242',
                expiryMonth: $currentMonth - 1,
                expiryYear: $currentYear,
                cvv: '123',
            );

            $this->assertTrue($request->isExpired());
        } else {
            // In January, use previous year
            $request = new TokenizationRequest(
                cardNumber: '4242424242424242',
                expiryMonth: 12,
                expiryYear: $currentYear - 1,
                cvv: '123',
            );

            $this->assertTrue($request->isExpired());
        }
    }

    #[Test]
    public function it_detects_non_expired_card_for_future_date(): void
    {
        $request = new TokenizationRequest(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2099,
            cvv: '123',
        );

        $this->assertFalse($request->isExpired());
    }

    #[Test]
    public function it_detects_customer_attachment_when_customer_id_is_set(): void
    {
        $request = new TokenizationRequest(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
            customerId: 'cus_12345',
        );

        $this->assertTrue($request->shouldAttachToCustomer());
    }

    #[Test]
    public function it_detects_no_customer_attachment_when_customer_id_is_null(): void
    {
        $request = new TokenizationRequest(
            cardNumber: '4242424242424242',
            expiryMonth: 12,
            expiryYear: 2030,
            cvv: '123',
        );

        $this->assertFalse($request->shouldAttachToCustomer());
    }
}
