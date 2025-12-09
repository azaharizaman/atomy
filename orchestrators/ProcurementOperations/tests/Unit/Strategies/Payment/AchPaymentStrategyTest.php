<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Strategies\Payment;

use Nexus\ProcurementOperations\DTOs\PaymentExecutionResult;
use Nexus\ProcurementOperations\DTOs\PaymentRequest;
use Nexus\ProcurementOperations\Enums\PaymentMethod;
use Nexus\ProcurementOperations\Strategies\Payment\AchPaymentStrategy;
use PHPUnit\Framework\TestCase;

final class AchPaymentStrategyTest extends TestCase
{
    private AchPaymentStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new AchPaymentStrategy();
    }

    public function test_get_method_returns_ach(): void
    {
        $this->assertSame(PaymentMethod::ACH, $this->strategy->getMethod());
    }

    public function test_is_available_with_bank_details(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $this->assertTrue($this->strategy->isAvailable($request));
    }

    public function test_is_not_available_without_bank_details(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
        );

        $this->assertFalse($this->strategy->isAvailable($request));
    }

    public function test_is_not_available_for_international(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
            isInternational: true,
        );

        $this->assertFalse($this->strategy->isAvailable($request));
    }

    public function test_is_not_available_for_same_day(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
            requiresSameDay: true,
        );

        $this->assertFalse($this->strategy->isAvailable($request));
    }

    public function test_execute_returns_successful_result(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $result = $this->strategy->execute($request);

        $this->assertInstanceOf(PaymentExecutionResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->paymentId);
        $this->assertStringStartsWith('ACH-', $result->transactionReference);
        $this->assertSame(50, $result->feeCents);
        $this->assertSame(PaymentMethod::ACH, $result->methodUsed);
        $this->assertNull($result->errorMessage);
    }

    public function test_execute_calculates_correct_clearing_date(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $result = $this->strategy->execute($request);

        // Clearing date should be 2 business days from now
        $expectedClearingDate = PaymentMethod::ACH->calculateClearingDate(new \DateTimeImmutable());
        $this->assertSame(
            $expectedClearingDate->format('Y-m-d'),
            $result->estimatedClearingDate->format('Y-m-d')
        );
    }

    public function test_calculate_fee_returns_fixed_fee(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
        );

        $this->assertSame(50, $this->strategy->calculateFee($request));
    }

    public function test_calculate_fee_is_same_for_different_amounts(): void
    {
        $smallRequest = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 1000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
        );

        $largeRequest = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 10000000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
        );

        // ACH has a flat fee regardless of amount
        $this->assertSame(
            $this->strategy->calculateFee($smallRequest),
            $this->strategy->calculateFee($largeRequest)
        );
    }

    public function test_execute_includes_masked_account_in_metadata(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $result = $this->strategy->execute($request);

        $this->assertArrayHasKey('masked_account', $result->metadata);
        $this->assertStringContains('****', $result->metadata['masked_account']);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}
