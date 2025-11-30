# Integration Guide: Procurement-ML

This guide shows how to integrate the `nexus/procurement-ml` package into your application.

---

## Laravel Integration

### Step 1: Implement Analytics Repositories

For each interface in `Nexus\ProcurementML\Contracts`, you need to create a concrete implementation that fetches data from your application's database.

**Example: `VendorAnalyticsRepository`**

```php
<?php

namespace App\Repositories\Procurement;

use Nexus\ProcurementML\Contracts\VendorAnalyticsRepositoryInterface;

final readonly class VendorAnalyticsRepository implements VendorAnalyticsRepositoryInterface
{
    public function getVendorRfqWinLossRatio(string $vendorId): array
    {
        // Your logic to calculate the win/loss ratio for a vendor's quotes
        return [
            'wins' => 10,
            'losses' => 2,
        ];
    }

    public function getVendorPoChangeFrequency(string $vendorId): int
    {
        // Your logic to count how often POs for a vendor are changed
        return 3;
    }
}
```

### Step 2: Create a Service Provider

Create a dedicated service provider to bind all the repository interfaces.

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\ProcurementML\Contracts;
use App\Repositories\Procurement;

class ProcurementMLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Contracts\ApprovalAnalyticsRepositoryInterface::class, Procurement\ApprovalAnalyticsRepository::class);
        $this->app->singleton(Contracts\BudgetAnalyticsRepositoryInterface::class, Procurement\BudgetAnalyticsRepository::class);
        $this->app->singleton(Contracts\ConversionAnalyticsRepositoryInterface::class, Procurement\ConversionAnalyticsRepository::class);
        $this->app->singleton(Contracts\DeliveryAnalyticsRepositoryInterface::class, Procurement\DeliveryAnalyticsRepository::class);
        $this->app->singleton(Contracts\HistoricalDataRepositoryInterface::class, Procurement\HistoricalDataRepository::class);
        $this->app->singleton(Contracts\PricingAnalyticsRepositoryInterface::class, Procurement\PricingAnalyticsRepository::class);
        $this->app->singleton(Contracts\VendorAnalyticsRepositoryInterface::class, Procurement\VendorAnalyticsRepository::class);
    }
}
```

### Step 3: Register the Service Provider

Add your new service provider to the `providers` array in `config/app.php`.

```php
'providers' => [
    // ...
    App\Providers\ProcurementMLServiceProvider::class,
],
```

### Step 4: Usage with `nexus/machine-learning`

The feature extractors from this package can now be used by the services in `nexus/machine-learning`. When you call a service like `AnomalyDetectionServiceInterface`, it will automatically resolve the correct extractor and its repository dependencies.

```php
// In a service or controller

use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;

class ValidatePurchaseOrder
{
    public function __construct(
        private readonly AnomalyDetectionServiceInterface $anomalyDetector,
        private readonly PurchaseOrderRepositoryInterface $poRepository
    ) {}

    public function __invoke(string $poId): void
    {
        $purchaseOrder = $this->poRepository->findById($poId);

        // The anomaly detector will use the VendorPricingAnomalyExtractor,
        // which in turn uses your concrete repository implementation.
        $result = $this->anomalyDetector->detectAnomalies('procurement.vendor_pricing', $purchaseOrder);

        if ($result->isAnomaly()) {
            // Handle anomaly
        }
    }
}
```

---

## Symfony Integration

### Step 1: Implement Analytics Repositories

Similar to Laravel, create concrete repository implementations for each interface.

**Example: `BudgetAnalyticsRepository`**

```php
<?php

namespace App\Repository\Procurement;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\ProcurementML\Contracts\BudgetAnalyticsRepositoryInterface;

class BudgetAnalyticsRepository extends ServiceEntityRepository implements BudgetAnalyticsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, YourEntity::class);
    }

    public function getDepartmentBudgetUtilization(string $departmentId): array
    {
        // Your Doctrine query to calculate budget utilization
        return [
            'budgeted' => 100000,
            'spent' => 75000,
        ];
    }
}
```

### Step 2: Configure Services

In your `config/services.yaml`, bind the interfaces to your concrete implementations.

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Bind Procurement-ML interfaces to your repositories
    Nexus\ProcurementML\Contracts\ApprovalAnalyticsRepositoryInterface:
        class: App\Repository\Procurement\ApprovalAnalyticsRepository

    Nexus\ProcurementML\Contracts\BudgetAnalyticsRepositoryInterface:
        class: App\Repository\Procurement\BudgetAnalyticsRepository

    # ... and so on for all other interfaces
```

### Step 3: Usage with `nexus/machine-learning`

The usage pattern is the same as in Laravel. Symfony's dependency injection container will automatically inject the correct dependencies when a service from `nexus/machine-learning` is requested.
