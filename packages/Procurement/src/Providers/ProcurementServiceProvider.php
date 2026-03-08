<?php

declare(strict_types=1);

namespace Nexus\Procurement\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Services\ProcurementManager;
use Nexus\Procurement\Services\RequisitionManager;
use Nexus\Procurement\Services\PurchaseOrderManager;
use Nexus\Procurement\Services\GoodsReceiptManager;
use Nexus\Procurement\Services\VendorQuoteManager;
use Nexus\Procurement\Services\MatchingEngine;

final class ProcurementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RequisitionManager::class);
        $this->app->singleton(PurchaseOrderManager::class);
        $this->app->singleton(GoodsReceiptManager::class);
        $this->app->singleton(VendorQuoteManager::class);
        $this->app->singleton(MatchingEngine::class);

        $this->app->singleton(ProcurementManagerInterface::class, ProcurementManager::class);
    }
}
