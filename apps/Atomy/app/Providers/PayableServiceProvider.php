<?php

declare(strict_types=1);

namespace Atomy\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Payable\Contracts\PayableManagerInterface;
use Nexus\Payable\Contracts\VendorRepositoryInterface;
use Nexus\Payable\Contracts\VendorBillRepositoryInterface;
use Nexus\Payable\Contracts\PaymentRepositoryInterface;
use Nexus\Payable\Contracts\PaymentScheduleRepositoryInterface;
use Nexus\Payable\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\Payable\Contracts\GoodsReceivedRepositoryInterface;
use Nexus\Payable\Contracts\ThreeWayMatcherInterface;
use Nexus\Payable\Contracts\PaymentSchedulerInterface;
use Nexus\Payable\Contracts\PaymentAllocationInterface;
use Nexus\Payable\Services\PayableManager;
use Nexus\Payable\Services\VendorManager;
use Nexus\Payable\Services\BillProcessor;
use Nexus\Payable\Services\MatchingEngine;
use Nexus\Payable\Services\PaymentScheduler;
use Nexus\Payable\Services\PaymentProcessor;
use Atomy\Repositories\EloquentVendorRepository;
use Atomy\Repositories\EloquentVendorBillRepository;
use Atomy\Repositories\EloquentPaymentRepository;
use Atomy\Repositories\EloquentPaymentScheduleRepository;
use Atomy\Repositories\StubPurchaseOrderRepository;
use Atomy\Repositories\StubGoodsReceivedRepository;

/**
 * Payable service provider.
 */
class PayableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(VendorRepositoryInterface::class, EloquentVendorRepository::class);
        $this->app->singleton(VendorBillRepositoryInterface::class, EloquentVendorBillRepository::class);
        $this->app->singleton(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
        $this->app->singleton(PaymentScheduleRepositoryInterface::class, EloquentPaymentScheduleRepository::class);
        
        // Bind stub repositories (temporary until Procurement and Inventory packages exist)
        $this->app->singleton(PurchaseOrderRepositoryInterface::class, StubPurchaseOrderRepository::class);
        $this->app->singleton(GoodsReceivedRepositoryInterface::class, StubGoodsReceivedRepository::class);

        // Bind services
        $this->app->singleton(ThreeWayMatcherInterface::class, MatchingEngine::class);
        $this->app->singleton(PaymentSchedulerInterface::class, PaymentScheduler::class);
        $this->app->singleton(PaymentAllocationInterface::class, PaymentProcessor::class);

        // Bind main orchestrator
        $this->app->singleton(PayableManagerInterface::class, PayableManager::class);
    }

    public function boot(): void
    {
        // Publish migrations if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'payable-migrations');
        }
    }
}
