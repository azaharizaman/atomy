<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\InventoryGLListener;
use App\Repositories\Inventory\DbCostLayerRepository;
use App\Repositories\Inventory\DbLotRepository;
use App\Repositories\Inventory\DbReservationRepository;
use App\Repositories\Inventory\DbSerialRepository;
use App\Repositories\Inventory\DbStockLevelRepository;
use App\Repositories\Inventory\DbStockMovementRepository;
use App\Repositories\Inventory\DbTransferRepository;
use App\Services\Inventory\InventoryConfigurationAdapter;
use App\Services\Inventory\LaravelEventPublisher;
use App\Services\Inventory\StandardCostAdapter;
use App\Services\Inventory\WeightedAverageAdapter;
use Illuminate\Support\ServiceProvider;
use Nexus\Inventory\Contracts\ConfigurationInterface;
use Nexus\Inventory\Contracts\CostLayerStorageInterface;
use Nexus\Inventory\Contracts\EventPublisherInterface;
use Nexus\Inventory\Contracts\LotManagerInterface;
use Nexus\Inventory\Contracts\LotRepositoryInterface;
use Nexus\Inventory\Contracts\ReservationManagerInterface;
use Nexus\Inventory\Contracts\ReservationRepositoryInterface;
use Nexus\Inventory\Contracts\SerialManagerInterface;
use Nexus\Inventory\Contracts\SerialRepositoryInterface;
use Nexus\Inventory\Contracts\StandardCostStorageInterface;
use Nexus\Inventory\Contracts\StockLevelRepositoryInterface;
use Nexus\Inventory\Contracts\StockManagerInterface;
use Nexus\Inventory\Contracts\StockMovementRepositoryInterface;
use Nexus\Inventory\Contracts\TransferManagerInterface;
use Nexus\Inventory\Contracts\TransferRepositoryInterface;
use Nexus\Inventory\Contracts\ValuationEngineInterface;
use Nexus\Inventory\Contracts\WeightedAverageStorageInterface;
use Nexus\Inventory\Core\Engine\FifoEngine;
use Nexus\Inventory\Core\Engine\StandardCostEngine;
use Nexus\Inventory\Core\Engine\WeightedAverageEngine;
use Nexus\Inventory\Services\LotManager;
use Nexus\Inventory\Services\ReservationManager;
use Nexus\Inventory\Services\SerialManager;
use Nexus\Inventory\Services\StockManager;
use Nexus\Inventory\Services\TransferManager;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Configuration
        $this->app->singleton(ConfigurationInterface::class, InventoryConfigurationAdapter::class);

        // Event Publishing
        $this->app->singleton(EventPublisherInterface::class, LaravelEventPublisher::class);

        // Repositories
        $this->app->singleton(StockLevelRepositoryInterface::class, DbStockLevelRepository::class);
        $this->app->singleton(StockMovementRepositoryInterface::class, DbStockMovementRepository::class);
        $this->app->singleton(LotRepositoryInterface::class, DbLotRepository::class);
        $this->app->singleton(SerialRepositoryInterface::class, DbSerialRepository::class);
        $this->app->singleton(ReservationRepositoryInterface::class, DbReservationRepository::class);
        $this->app->singleton(TransferRepositoryInterface::class, DbTransferRepository::class);
        $this->app->singleton(CostLayerStorageInterface::class, DbCostLayerRepository::class);

        // Valuation Engine Storage Adapters
        $this->app->singleton(WeightedAverageStorageInterface::class, WeightedAverageAdapter::class);
        $this->app->singleton(StandardCostStorageInterface::class, StandardCostAdapter::class);

        // Valuation Engine - Based on configuration
        $this->app->singleton(ValuationEngineInterface::class, function ($app) {
            $config = $app->make(ConfigurationInterface::class);
            $method = $config->getDefaultValuationMethod();

            return match ($method) {
                'fifo' => new FifoEngine(
                    $app->make(CostLayerStorageInterface::class),
                    $app->make('log')
                ),
                'weighted_average' => new WeightedAverageEngine(
                    $app->make(WeightedAverageStorageInterface::class),
                    $app->make('log')
                ),
                'standard_cost' => new StandardCostEngine(
                    $app->make(StandardCostStorageInterface::class),
                    $app->make('log')
                ),
                default => new WeightedAverageEngine(
                    $app->make(WeightedAverageStorageInterface::class),
                    $app->make('log')
                ),
            };
        });

        // Services
        $this->app->singleton(StockManagerInterface::class, StockManager::class);
        $this->app->singleton(LotManagerInterface::class, LotManager::class);
        $this->app->singleton(SerialManagerInterface::class, SerialManager::class);
        $this->app->singleton(ReservationManagerInterface::class, ReservationManager::class);
        $this->app->singleton(TransferManagerInterface::class, TransferManager::class);
    }

    public function boot(): void
    {
        // Register event listener for GL integration
        // TODO: Uncomment when Nexus\Accounting\Services\GeneralLedgerManager is implemented
        // $this->app->make('events')->subscribe(InventoryGLListener::class);
    }
}
