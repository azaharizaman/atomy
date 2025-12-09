<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Payment;

use Nexus\ProcurementOperations\Contracts\PaymentMethodStrategyInterface;
use Nexus\ProcurementOperations\DTOs\PaymentExecutionResult;
use Nexus\ProcurementOperations\DTOs\PaymentRequest;
use Nexus\ProcurementOperations\Enums\PaymentMethod;
use Nexus\ProcurementOperations\Services\Payment\PaymentMethodSelector;
use Nexus\ProcurementOperations\Strategies\Payment\AchPaymentStrategy;
use Nexus\ProcurementOperations\Strategies\Payment\CheckPaymentStrategy;
use Nexus\ProcurementOperations\Strategies\Payment\VirtualCardPaymentStrategy;
use Nexus\ProcurementOperations\Strategies\Payment\WirePaymentStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class PaymentMethodSelectorTest extends TestCase
{
    private PaymentMethodSelector $selector;

    protected function setUp(): void
    {
        $strategies = [
            new AchPaymentStrategy(),
            new WirePaymentStrategy(),
            new CheckPaymentStrategy(),
            new VirtualCardPaymentStrategy(),
        ];

        $this->selector = new PaymentMethodSelector($strategies, new NullLogger());
    }

    public function test_select_method_returns_preferred_when_available(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $strategy = $this->selector->selectMethod($request);

        $this->assertSame(PaymentMethod::ACH, $strategy->getMethod());
    }

    public function test_select_method_falls_back_when_preferred_unavailable(): void
    {
        // Request ACH but without bank details - should fall back
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            mailingAddress: '123 Main St, City, State 12345',
        );

        $strategy = $this->selector->selectMethod($request);

        // Should select check since mailing address is available
        $this->assertSame(PaymentMethod::CHECK, $strategy->getMethod());
    }

    public function test_select_method_returns_wire_for_international(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'EUR',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: 'DE89370400440532013000',
            swiftCode: 'COBADEFFXXX',
            isInternational: true,
        );

        $strategy = $this->selector->selectMethod($request);

        $this->assertSame(PaymentMethod::WIRE, $strategy->getMethod());
    }

    public function test_select_method_returns_wire_for_same_day(): void
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

        $strategy = $this->selector->selectMethod($request);

        // Wire supports same-day, ACH does not
        $this->assertSame(PaymentMethod::WIRE, $strategy->getMethod());
    }

    public function test_select_method_throws_when_no_strategy_available(): void
    {
        // Create selector with no strategies
        $emptySelector = new PaymentMethodSelector([], new NullLogger());

        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No payment method available');

        $emptySelector->selectMethod($request);
    }

    public function test_execute_payment_uses_selected_strategy(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $result = $this->selector->executePayment($request);

        $this->assertInstanceOf(PaymentExecutionResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame(PaymentMethod::ACH, $result->methodUsed);
    }

    public function test_get_available_methods_returns_only_available(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $available = $this->selector->getAvailableMethods($request);

        // Should have ACH and Wire available (both have bank details)
        $methods = array_map(fn($s) => $s->getMethod(), $available);
        $this->assertContains(PaymentMethod::ACH, $methods);
        $this->assertContains(PaymentMethod::WIRE, $methods);
    }

    public function test_is_method_available_returns_true_for_available(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
            bankAccountNumber: '123456789',
            bankRoutingNumber: '021000021',
        );

        $this->assertTrue($this->selector->isMethodAvailable(PaymentMethod::ACH, $request));
    }

    public function test_is_method_available_returns_false_for_unavailable(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
        );

        // ACH requires bank details
        $this->assertFalse($this->selector->isMethodAvailable(PaymentMethod::ACH, $request));
    }

    public function test_is_method_available_returns_false_for_unregistered_method(): void
    {
        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::CREDIT_CARD,
        );

        // Credit card strategy not registered in setUp
        $this->assertFalse($this->selector->isMethodAvailable(PaymentMethod::CREDIT_CARD, $request));
    }

    public function test_constructor_indexes_strategies_by_method(): void
    {
        // Mock strategy to verify indexing
        $mockStrategy = $this->createMock(PaymentMethodStrategyInterface::class);
        $mockStrategy->method('getMethod')->willReturn(PaymentMethod::ACH);
        $mockStrategy->method('isAvailable')->willReturn(true);
        $mockStrategy->method('execute')->willReturn(new PaymentExecutionResult(
            success: true,
            paymentId: 'test-123',
            transactionReference: 'ACH-TEST',
            feeCents: 50,
            methodUsed: PaymentMethod::ACH,
            estimatedClearingDate: new \DateTimeImmutable('+2 days'),
        ));

        $selector = new PaymentMethodSelector([$mockStrategy], new NullLogger());

        $request = new PaymentRequest(
            vendorId: 'vendor-1',
            amountCents: 100000,
            currency: 'USD',
            preferredMethod: PaymentMethod::ACH,
        );

        $this->assertTrue($selector->isMethodAvailable(PaymentMethod::ACH, $request));
    }
}
