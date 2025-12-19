<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\PaymentMethodInterface;
use Nexus\Payment\Contracts\PaymentTransactionInterface;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\Exceptions\InsufficientFundsException;
use Nexus\Payment\Exceptions\InvalidPaymentMethodException;
use Nexus\Payment\Exceptions\InvalidPaymentStatusException;
use Nexus\Payment\Exceptions\PaymentValidationException;
use Nexus\Payment\Services\PaymentValidator;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentValidator::class)]
final class PaymentValidatorTest extends TestCase
{
    private PaymentValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PaymentValidator();
    }

    #[Test]
    public function it_validates_create_with_valid_data(): void
    {
        $data = [
            'tenant_id' => 'tenant_123',
            'direction' => PaymentDirection::INBOUND,
            'amount' => Money::of(100, 'MYR'),
            'method_type' => PaymentMethodType::BANK_ACCOUNT,
            'payer_id' => 'customer_123',
        ];

        // Should not throw
        $this->validator->validateCreate($data);

        self::assertTrue(true); // Assertion to confirm no exception
    }

    #[Test]
    public function it_throws_when_tenant_id_missing(): void
    {
        $data = [
            'direction' => PaymentDirection::INBOUND,
            'amount' => Money::of(100, 'MYR'),
            'method_type' => PaymentMethodType::BANK_ACCOUNT,
        ];

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage("Field 'tenant_id' is required");

        $this->validator->validateCreate($data);
    }

    #[Test]
    public function it_throws_when_direction_missing(): void
    {
        $data = [
            'tenant_id' => 'tenant_123',
            'amount' => Money::of(100, 'MYR'),
            'method_type' => PaymentMethodType::BANK_ACCOUNT,
        ];

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage("Field 'direction' is required");

        $this->validator->validateCreate($data);
    }

    #[Test]
    public function it_throws_when_amount_missing(): void
    {
        $data = [
            'tenant_id' => 'tenant_123',
            'direction' => PaymentDirection::INBOUND,
            'method_type' => PaymentMethodType::BANK_ACCOUNT,
        ];

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage("Field 'amount' is required");

        $this->validator->validateCreate($data);
    }

    #[Test]
    public function it_throws_when_method_type_missing(): void
    {
        $data = [
            'tenant_id' => 'tenant_123',
            'direction' => PaymentDirection::INBOUND,
            'amount' => Money::of(100, 'MYR'),
        ];

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage("Field 'method_type' is required");

        $this->validator->validateCreate($data);
    }

    #[Test]
    public function it_validates_inbound_requires_payer_id(): void
    {
        $data = [
            'tenant_id' => 'tenant_123',
            'direction' => PaymentDirection::INBOUND,
            'amount' => Money::of(100, 'MYR'),
            'method_type' => PaymentMethodType::BANK_ACCOUNT,
            // Missing payer_id
        ];

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Payer ID is required for inbound payments');

        $this->validator->validateCreate($data);
    }

    #[Test]
    public function it_validates_outbound_requires_payee_id(): void
    {
        $data = [
            'tenant_id' => 'tenant_123',
            'direction' => PaymentDirection::OUTBOUND,
            'amount' => Money::of(100, 'MYR'),
            'method_type' => PaymentMethodType::BANK_ACCOUNT,
            // Missing payee_id
        ];

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Payee ID is required for outbound payments');

        $this->validator->validateCreate($data);
    }

    #[Test]
    public function it_validates_positive_amount(): void
    {
        $amount = Money::of(100, 'MYR');

        // Should not throw
        $this->validator->validateAmount($amount);

        self::assertTrue(true);
    }

    #[Test]
    public function it_throws_for_zero_amount(): void
    {
        $amount = Money::of(0, 'MYR');

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Payment amount must be positive');

        $this->validator->validateAmount($amount);
    }

    #[Test]
    public function it_throws_for_negative_amount(): void
    {
        $amount = Money::of(-100, 'MYR');

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Payment amount must be positive');

        $this->validator->validateAmount($amount);
    }

    #[Test]
    public function it_throws_for_amount_below_minimum(): void
    {
        // Note: With Money using scale=2, amounts that would be "below minimum (0.01)"
        // but still "positive" are mathematically impossible because:
        // - Amounts < 0.005 round to 0 cents (not positive)
        // - Amounts >= 0.005 round to >= 1 cent (the minimum)
        // Therefore the "must be positive" check effectively covers the minimum check.
        // This test verifies the behavior matches expectations for fractional amounts.
        $amount = Money::of(0.001, 'MYR'); // Rounds to 0 cents

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Payment amount must be positive');

        $this->validator->validateAmount($amount);
    }

    #[Test]
    public function it_throws_for_amount_above_maximum(): void
    {
        $amount = Money::of(20000000, 'MYR'); // Above 10,000,000 maximum

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Payment amount cannot exceed');

        $this->validator->validateAmount($amount);
    }

    #[Test]
    public function it_validates_reference_with_valid_length(): void
    {
        $reference = new PaymentReference('INV-001');

        // Should not throw
        $this->validator->validateReference($reference);

        self::assertTrue(true);
    }

    #[Test]
    public function it_throws_for_short_reference(): void
    {
        // PaymentReference allows 1+ chars, but validator requires 3+
        $reference = new PaymentReference('AB'); // 2 chars - too short for validator

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Payment reference must be at least 3 characters');

        $this->validator->validateReference($reference);
    }

    #[Test]
    public function it_throws_for_long_reference(): void
    {
        // PaymentReference allows up to 100 chars, but validator limits to 50
        $reference = new PaymentReference(str_repeat('A', 51)); // 51 chars - too long for validator

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Payment reference cannot exceed 50 characters');

        $this->validator->validateReference($reference);
    }

    #[Test]
    public function it_validates_payment_method_can_be_used(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('isActive')->willReturn(true);
        $paymentMethod->method('isExpired')->willReturn(false);
        $paymentMethod->method('isVerified')->willReturn(true);
        $paymentMethod->method('getType')->willReturn(PaymentMethodType::CARD);

        $amount = Money::of(100, 'MYR');

        // Should not throw
        $this->validator->validatePaymentMethod($paymentMethod, $amount);

        self::assertTrue(true);
    }

    #[Test]
    public function it_throws_when_payment_method_cannot_be_used(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('isActive')->willReturn(false);
        $paymentMethod->method('isExpired')->willReturn(false);
        $paymentMethod->method('getType')->willReturn(PaymentMethodType::CARD);

        $amount = Money::of(100, 'MYR');

        $this->expectException(InvalidPaymentMethodException::class);
        $this->expectExceptionMessage('Payment method is not active or has expired');

        $this->validator->validatePaymentMethod($paymentMethod, $amount);
    }

    #[Test]
    public function it_throws_when_payment_method_not_verified(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('isActive')->willReturn(true);
        $paymentMethod->method('isExpired')->willReturn(false);
        $paymentMethod->method('isVerified')->willReturn(false);
        $paymentMethod->method('getType')->willReturn(PaymentMethodType::CARD);

        $amount = Money::of(100, 'MYR');

        $this->expectException(InvalidPaymentMethodException::class);
        $this->expectExceptionMessage('Payment method has not been verified');

        $this->validator->validatePaymentMethod($paymentMethod, $amount);
    }

    #[Test]
    public function it_validates_for_execution_when_pending(): void
    {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('getStatus')->willReturn(PaymentStatus::PENDING);

        // Should not throw
        $this->validator->validateForExecution($payment);

        self::assertTrue(true);
    }

    #[Test]
    public function it_throws_when_executing_non_pending_payment(): void
    {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('getStatus')->willReturn(PaymentStatus::COMPLETED);

        $this->expectException(InvalidPaymentStatusException::class);

        $this->validator->validateForExecution($payment);
    }

    #[Test]
    public function it_validates_for_cancellation_when_allowed(): void
    {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('canBeCancelled')->willReturn(true);

        // Should not throw
        $this->validator->validateForCancellation($payment);

        self::assertTrue(true);
    }

    #[Test]
    public function it_throws_when_payment_cannot_be_cancelled(): void
    {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('canBeCancelled')->willReturn(false);
        $payment->method('getStatus')->willReturn(PaymentStatus::COMPLETED);

        $this->expectException(InvalidPaymentStatusException::class);

        $this->validator->validateForCancellation($payment);
    }

    #[Test]
    public function it_validates_for_reversal_when_allowed(): void
    {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('canBeReversed')->willReturn(true);
        $payment->method('getMethodType')->willReturn(PaymentMethodType::BANK_ACCOUNT);

        // Should not throw - BANK_ACCOUNT supports refunds
        $this->validator->validateForReversal($payment);

        self::assertTrue(true);
    }

    #[Test]
    public function it_throws_when_payment_cannot_be_reversed(): void
    {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('canBeReversed')->willReturn(false);
        $payment->method('getStatus')->willReturn(PaymentStatus::PENDING);

        $this->expectException(InvalidPaymentStatusException::class);

        $this->validator->validateForReversal($payment);
    }

    #[Test]
    public function it_throws_when_payment_method_doesnt_support_refund(): void
    {
        $payment = $this->createMock(PaymentTransactionInterface::class);
        $payment->method('canBeReversed')->willReturn(true);
        $payment->method('getMethodType')->willReturn(PaymentMethodType::CASH);

        $this->expectException(InvalidPaymentMethodException::class);
        $this->expectExceptionMessage('does not support reversals');

        $this->validator->validateForReversal($payment);
    }

    #[Test]
    public function it_validates_sufficient_funds(): void
    {
        $required = Money::of(100, 'MYR');
        $available = Money::of(200, 'MYR');

        // Should not throw
        $this->validator->validateSufficientFunds($required, $available);

        self::assertTrue(true);
    }

    #[Test]
    public function it_validates_exact_funds(): void
    {
        $required = Money::of(100, 'MYR');
        $available = Money::of(100, 'MYR');

        // Should not throw
        $this->validator->validateSufficientFunds($required, $available);

        self::assertTrue(true);
    }

    #[Test]
    public function it_throws_for_insufficient_funds(): void
    {
        $required = Money::of(200, 'MYR');
        $available = Money::of(100, 'MYR');

        $this->expectException(InsufficientFundsException::class);

        $this->validator->validateSufficientFunds($required, $available);
    }

    #[Test]
    public function it_validates_non_expired_idempotency_key(): void
    {
        // Create a key with future expiry
        $key = IdempotencyKey::generate(24); // Expires in 24 hours

        // Should not throw
        $this->validator->validateIdempotencyKey($key);

        self::assertTrue(true);
    }

    #[Test]
    public function it_throws_for_expired_idempotency_key(): void
    {
        // Create an expired key by creating one with dates in the past
        $createdAt = new \DateTimeImmutable('-2 days');
        $expiresAt = new \DateTimeImmutable('-1 day');
        $key = new IdempotencyKey('test-key', $createdAt, $expiresAt);

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage('Idempotency key has expired');

        $this->validator->validateIdempotencyKey($key);
    }

    #[Test]
    #[DataProvider('validAmountProvider')]
    public function it_accepts_valid_amounts(float $amount): void
    {
        $money = Money::of($amount, 'MYR');

        // Should not throw
        $this->validator->validateAmount($money);

        self::assertTrue(true);
    }

    /**
     * @return array<string, array{float}>
     */
    public static function validAmountProvider(): array
    {
        return [
            'minimum valid' => [0.01],
            'small amount' => [1.00],
            'typical amount' => [100.00],
            'large amount' => [50000.00],
            'near maximum' => [9999999.99],
        ];
    }

    #[Test]
    #[DataProvider('invalidAmountProvider')]
    public function it_rejects_invalid_amounts(float $amount, string $expectedMessage): void
    {
        $money = Money::of($amount, 'MYR');

        $this->expectException(PaymentValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->validator->validateAmount($money);
    }

    /**
     * @return array<string, array{float, string}>
     */
    public static function invalidAmountProvider(): array
    {
        return [
            'zero' => [0.00, 'must be positive'],
            'negative' => [-50.00, 'must be positive'],
            'exceeds maximum' => [15000000.00, 'Payment amount cannot exceed'],
        ];
    }
}
