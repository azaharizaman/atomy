<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Strategies\Payment;

use Nexus\ProcurementOperations\DTOs\PaymentRequest;
use Nexus\ProcurementOperations\Enums\PaymentMethod;
use Nexus\ProcurementOperations\Strategies\Payment\WirePaymentStrategy;
use PHPUnit\Framework\TestCase;

final class WirePaymentStrategyTest extends TestCase
{
    private WirePaymentStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new WirePaymentStrategy();
    }

    public function test_get_method_returns_wire(): void
    {
        $this->assertSame(PaymentMethod::WIRE, $this->strategy->getMethod());
    }

    public function test_is_available_with_bank_details(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::WIRE,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $this->assertTrue($this->strategy->isAvailable($request));
    }

    public function test_is_available_with_swift_for_international(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'EUR',
            preferredMethod: PaymentMethod::WIRE,
            bankAccountNumber: 'DE89370400440532013000',
            swiftCode: 'COBADEFFXXX',
            isInternational: true,
        );

        $this->assertTrue($this->strategy->isAvailable($request));
    }

    public function test_is_not_available_without_bank_details(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::WIRE,
        );

        $this->assertFalse($this->strategy->isAvailable($request));
    }

    public function test_is_available_for_same_day(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::WIRE,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
            requiresSameDay: true,
        );

        $this->assertTrue($this->strategy->isAvailable($request));
    }

    public function test_execute_returns_successful_result(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::WIRE,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $result = $this->strategy->execute($request);

        $this->assertTrue($result->success);
        $this->assertNotEmpty($result->paymentId);
        $this->assertStringStartsWith('WIRE-', $result->transactionReference);
        $this->assertSame(PaymentMethod::WIRE, $result->methodUsed);
    }

    public function test_calculate_fee_domestic(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::WIRE,
            isInternational: false,
        );

        $this->assertSame(2500, $this->strategy->calculateFee($request)); // $25
    }

    public function test_calculate_fee_international(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'EUR',
            preferredMethod: PaymentMethod::WIRE,
            isInternational: true,
        );

        $this->assertSame(5000, $this->strategy->calculateFee($request)); // $50
    }

    public function test_execute_same_day_clearing(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::WIRE,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
            requiresSameDay: true,
        );

        $result = $this->strategy->execute($request);

        // Wire transfers clear same day
        $this->assertSame(
            (new \DateTimeImmutable())->format('Y-m-d'),
            $result->estimatedClearingDate->format('Y-m-d')
        );
    }
}
