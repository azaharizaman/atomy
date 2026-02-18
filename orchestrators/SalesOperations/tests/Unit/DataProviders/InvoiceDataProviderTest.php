<?php

declare(strict_types=1);

namespace Nexus\SalesOperations\Tests\Unit\DataProviders;

use Nexus\SalesOperations\Contracts\SalesOrderProviderInterface;
use Nexus\SalesOperations\Contracts\InvoiceProviderInterface;
use Nexus\SalesOperations\DataProviders\InvoiceDataProvider;
use Nexus\SalesOperations\DataProviders\InvoiceContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceDataProvider::class)]
#[CoversClass(InvoiceContext::class)]
final class InvoiceDataProviderTest extends TestCase
{
    private InvoiceDataProvider $provider;
    private $orderProvider;
    private $invoiceProvider;

    protected function setUp(): void
    {
        $this->orderProvider = $this->createMock(SalesOrderProviderInterface::class);
        $this->invoiceProvider = $this->createMock(InvoiceProviderInterface::class);

        $this->provider = new InvoiceDataProvider(
            $this->invoiceProvider,
            $this->orderProvider
        );
    }

    #[Test]
    public function get_invoice_context_returns_null_for_missing_order(): void
    {
        $this->orderProvider->method('findById')->willReturn(null);

        $result = $this->provider->getInvoiceContext('tenant-1', 'order-1');

        $this->assertNull($result);
    }

    #[Test]
    public function calculate_order_totals_for_missing_order(): void
    {
        $this->orderProvider->method('findById')->willReturn(null);

        $result = $this->provider->calculateOrderTotals('tenant-1', 'order-1');

        $this->assertFalse($result['found']);
    }

    #[Test]
    public function get_overdue_invoices_returns_empty_for_no_orders(): void
    {
        $this->orderProvider->method('findByStatus')->willReturn([]);

        $result = $this->provider->getOverdueInvoices('tenant-1');

        $this->assertEmpty($result);
    }
}
