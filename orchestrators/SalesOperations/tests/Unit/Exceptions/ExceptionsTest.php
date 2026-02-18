<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\Exceptions;

use Nexus\SalesOperations\Exceptions\CreditLimitExceededException;
use Nexus\SalesOperations\Exceptions\InsufficientStockException;
use Nexus\SalesOperations\Exceptions\OrderNotFoundException;
use Nexus\SalesOperations\Exceptions\QuotationNotConvertibleException;
use Nexus\SalesOperations\Exceptions\FulfillmentException;
use Nexus\SalesOperations\Exceptions\CustomerNotFoundException;
use Nexus\SalesOperations\Exceptions\PaymentException;
use Nexus\SalesOperations\Exceptions\ShipmentException;
use Nexus\SalesOperations\Exceptions\CommissionException;
use Nexus\SalesOperations\Exceptions\PricingException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreditLimitExceededException::class)]
#[CoversClass(InsufficientStockException::class)]
#[CoversClass(OrderNotFoundException::class)]
#[CoversClass(QuotationNotConvertibleException::class)]
#[CoversClass(FulfillmentException::class)]
#[CoversClass(CustomerNotFoundException::class)]
#[CoversClass(PaymentException::class)]
#[CoversClass(ShipmentException::class)]
#[CoversClass(CommissionException::class)]
#[CoversClass(PricingException::class)]
final class ExceptionsTest extends TestCase
{
    #[Test]
    public function credit_limit_exceeded_creates_message(): void
    {
        $exception = new CreditLimitExceededException(
            customerId: 'cust-1',
            creditLimit: 10000.0,
            requestedAmount: 15000.0,
            availableCredit: 5000.0
        );

        $this->assertSame('cust-1', $exception->customerId);
        $this->assertSame(10000.0, $exception->creditLimit);
        $this->assertSame(15000.0, $exception->requestedAmount);
        $this->assertStringContainsString('Credit limit exceeded', $exception->getMessage());
    }

    #[Test]
    public function insufficient_stock_creates_message(): void
    {
        $exception = new InsufficientStockException(
            productId: 'prod-1',
            requested: 100.0,
            available: 50.0
        );

        $this->assertSame('prod-1', $exception->productId);
        $this->assertSame(100.0, $exception->requested);
        $this->assertSame(50.0, $exception->available);
        $this->assertStringContainsString('Insufficient stock', $exception->getMessage());
    }

    #[Test]
    public function order_not_found_creates_message(): void
    {
        $exception = new OrderNotFoundException('order-1');

        $this->assertSame('order-1', $exception->orderId);
        $this->assertStringContainsString('Order not found', $exception->getMessage());
    }

    #[Test]
    public function quotation_not_convertible_creates_message(): void
    {
        $exception = new QuotationNotConvertibleException('quote-1', 'Already expired');

        $this->assertSame('quote-1', $exception->quotationId);
        $this->assertSame('Already expired', $exception->reason);
        $this->assertStringContainsString('cannot be converted', $exception->getMessage());
    }

    #[Test]
    public function fulfillment_exception_creates_message(): void
    {
        $exception = new FulfillmentException('order-1', ['No stock', 'Invalid warehouse']);

        $this->assertSame('order-1', $exception->orderId);
        $this->assertCount(2, $exception->issues);
        $this->assertStringContainsString('Fulfillment failed', $exception->getMessage());
    }

    #[Test]
    public function customer_not_found_creates_message(): void
    {
        $exception = new CustomerNotFoundException('cust-1');

        $this->assertSame('cust-1', $exception->customerId);
        $this->assertStringContainsString('Customer not found', $exception->getMessage());
    }

    #[Test]
    public function payment_exception_static_factories(): void
    {
        $failed = PaymentException::failed('pay-1', 'Declined');
        $this->assertStringContainsString('failed', $failed->getMessage());

        $invalid = PaymentException::invalidAmount(-100.0);
        $this->assertStringContainsString('Invalid payment amount', $invalid->getMessage());

        $processed = PaymentException::alreadyProcessed('pay-1');
        $this->assertStringContainsString('already been processed', $processed->getMessage());
    }

    #[Test]
    public function shipment_exception_static_factories(): void
    {
        $cannotShip = ShipmentException::cannotShip('order-1', 'No stock');
        $this->assertStringContainsString('Cannot ship', $cannotShip->getMessage());

        $insufficient = ShipmentException::insufficientStock('prod-1', 100.0, 50.0);
        $this->assertStringContainsString('Insufficient stock', $insufficient->getMessage());

        $invalidWarehouse = ShipmentException::invalidWarehouse('wh-1');
        $this->assertStringContainsString('Invalid warehouse', $invalidWarehouse->getMessage());
    }

    #[Test]
    public function commission_exception_static_factories(): void
    {
        $invalidRate = CommissionException::invalidRate(-5.0);
        $this->assertStringContainsString('Invalid commission rate', $invalidRate->getMessage());

        $noSalesperson = CommissionException::noSalespersonAssigned('order-1');
        $this->assertStringContainsString('No salesperson assigned', $noSalesperson->getMessage());

        $alreadyPaid = CommissionException::alreadyPaid('comm-1');
        $this->assertStringContainsString('already been paid', $alreadyPaid->getMessage());
    }

    #[Test]
    public function pricing_exception_static_factories(): void
    {
        $invalidPrice = PricingException::invalidPrice(-100.0);
        $this->assertStringContainsString('Invalid price', $invalidPrice->getMessage());

        $discountExceeded = PricingException::discountExceeded(35.0, 30.0);
        $this->assertStringContainsString('exceeds maximum', $discountExceeded->getMessage());

        $notPriced = PricingException::productNotPriced('prod-1');
        $this->assertStringContainsString('No pricing found', $notPriced->getMessage());

        $invalidList = PricingException::invalidPriceList('list-1');
        $this->assertStringContainsString('Invalid price list', $invalidList->getMessage());
    }
}
