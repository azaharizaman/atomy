<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Sales\Contracts\CreditLimitCheckerInterface;
use Nexus\Sales\Contracts\InvoiceManagerInterface;
use Nexus\Sales\Contracts\StockReservationInterface;
use Nexus\Laravel\Sales\Adapters\ReceivableCreditLimitCheckerAdapter;
use Nexus\Laravel\Sales\Adapters\InventoryStockReservationAdapter;
use Nexus\Laravel\Sales\Adapters\ReceivableInvoiceManagerAdapter;

/**
 * Laravel Service Provider for Sales package adapters.
 *
 * This provider binds the Sales package interfaces to their concrete
 * adapter implementations that integrate with other Nexus packages.
 *
 * Only register these bindings when the required packages are available.
 */
class SalesAdapterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register credit limit checker adapter if Receivable package is available
        $this->app->bind(CreditLimitCheckerInterface::class, function ($app) {
            return new ReceivableCreditLimitCheckerAdapter(
                receivableCreditChecker: $app->make(\Nexus\Receivable\Contracts\CreditLimitCheckerInterface::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class)
            );
        });

        // Register stock reservation adapter if Inventory package is available
        $this->app->bind(StockReservationInterface::class, function ($app) {
            return new InventoryStockReservationAdapter(
                salesOrderRepository: $app->make(\Nexus\Sales\Contracts\SalesOrderRepositoryInterface::class),
                reservationManager: $app->make(\Nexus\Inventory\Contracts\ReservationManagerInterface::class),
                stockLevelRepository: $app->make(\Nexus\Inventory\Contracts\StockLevelRepositoryInterface::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class)
            );
        });

        // Register invoice manager adapter if Receivable package is available
        $this->app->bind(InvoiceManagerInterface::class, function ($app) {
            return new ReceivableInvoiceManagerAdapter(
                salesOrderRepository: $app->make(\Nexus\Sales\Contracts\SalesOrderRepositoryInterface::class),
                receivableManager: $app->make(\Nexus\Receivable\Contracts\ReceivableManagerInterface::class),
                logger: $app->make(\Psr\Log\LoggerInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration if needed
        $this->publishes([
            __DIR__ . '/../../config/sales-adapter.php' => config_path('sales-adapter.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            CreditLimitCheckerInterface::class,
            StockReservationInterface::class,
            InvoiceManagerInterface::class,
        ];
    }
}