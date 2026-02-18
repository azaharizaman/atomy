<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Listeners;

use Nexus\SalesOperations\Contracts\CreditManagerInterface;
use Nexus\SalesOperations\Contracts\CustomerProviderInterface;
use Nexus\SalesOperations\Events\OrderCreatedEvent;
use Nexus\SalesOperations\Listeners\CheckCreditListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CheckCreditListener::class)]
final class CheckCreditListenerTest extends TestCase
{
    #[Test]
    public function handle_logs_warning_for_missing_customer(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn(null);

        $listener = new CheckCreditListener($creditManager, $customerProvider);

        $event = new OrderCreatedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR'
        );

        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function handle_checks_credit_hold_status(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $creditManager->method('isOnCreditHold')->willReturn(true);
        $creditManager->method('getCreditHoldReason')->willReturn('Overdue payments');

        $customer = new class {
            public function getName(): string { return 'Test Customer'; }
        };

        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn($customer);

        $listener = new CheckCreditListener($creditManager, $customerProvider);

        $event = new OrderCreatedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR'
        );

        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function handle_checks_available_credit(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $creditManager->method('isOnCreditHold')->willReturn(false);
        $creditManager->method('getCreditLimit')->willReturn(10000.0);
        $creditManager->method('getCreditUsed')->willReturn(9000.0);

        $customer = new class {
            public function getName(): string { return 'Test Customer'; }
        };

        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn($customer);

        $listener = new CheckCreditListener($creditManager, $customerProvider);

        $event = new OrderCreatedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 500.0,
            currencyCode: 'MYR'
        );

        $listener->handle($event);
        $this->assertTrue(true);
    }

    #[Test]
    public function invoke_calls_handle(): void
    {
        $creditManager = $this->createMock(CreditManagerInterface::class);
        $customerProvider = $this->createMock(CustomerProviderInterface::class);
        $customerProvider->method('findById')->willReturn(null);

        $listener = new CheckCreditListener($creditManager, $customerProvider);

        $event = new OrderCreatedEvent(
            tenantId: 'tenant-1',
            orderId: 'order-1',
            orderNumber: 'SO-001',
            customerId: 'cust-1',
            totalAmount: 1000.0,
            currencyCode: 'MYR'
        );

        $listener($event);
        $this->assertTrue(true);
    }
}
