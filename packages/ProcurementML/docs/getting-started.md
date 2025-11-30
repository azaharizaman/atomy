# Getting Started with Nexus Procurement-ML

## Prerequisites

- PHP 8.3 or higher
- Composer
- `nexus/procurement` package
- `nexus/machine-learning` package

## Installation

```bash
composer require nexus/procurement-ml:"*@dev"
```

## When to Use This Package

This package is designed for:
- ✅ Extracting ML features from `nexus/procurement` entities.
- ✅ Integrating procurement processes with `nexus/machine-learning` services.
- ✅ Building predictive models for procurement data.

Do NOT use this package for:
- ❌ General procurement business logic (use `nexus/procurement`).
- ❌ Core machine learning model management (use `nexus/machine-learning`).

## Core Concepts

This package contains two main types of components:

### Analytics Repository Interfaces
These interfaces (`src/Contracts/*AnalyticsRepositoryInterface.php`) define the methods required to fetch historical and analytical data from your application's database. You must provide concrete implementations of these interfaces in your application.

### Feature Extractors
These classes (`src/Extractors/*Extractor.php`) consume data from the analytics repositories and transform it into a format suitable for machine learning models. They are designed to be used by services within the `nexus/machine-learning` package.

## Basic Configuration

### Step 1: Implement Required Interfaces

You must provide concrete implementations for the analytics repository interfaces. Here is an example for `PricingAnalyticsRepositoryInterface`.

```php
// In your application, e.g., App\Repositories\Procurement\PricingAnalyticsRepository.php

namespace App\Repositories\Procurement;

use Nexus\ProcurementML\Contracts\PricingAnalyticsRepositoryInterface;

final readonly class PricingAnalyticsRepository implements PricingAnalyticsRepositoryInterface
{
    // Implement the interface methods to fetch data from your database
    public function getHistoricalPricingForVendor(string $vendorId, string $productId): array
    {
        // Your logic to query historical purchase order lines
        return [];
    }

    public function getPeerPricingForProduct(string $productId, \DateTimeImmutable $date): array
    {
        // Your logic to query what other vendors charged for the same product
        return [];
    }
}
```

### Step 2: Bind Interfaces in Service Provider

In your Laravel service provider, bind your concrete implementations to the package's interfaces.

```php
// App\Providers\ProcurementMLServiceProvider.php

use Illuminate\Support\ServiceProvider;
use Nexus\ProcurementML\Contracts;
use App\Repositories\Procurement;

class ProcurementMLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            Contracts\PricingAnalyticsRepositoryInterface::class,
            Procurement\PricingAnalyticsRepository::class
        );

        // ... bind other analytics repository interfaces
    }
}
```

## Next Steps

- Read the [API Reference](api-reference.md) for detailed interface documentation.
- Check the [Integration Guide](integration-guide.md) for more detailed framework-specific examples.
