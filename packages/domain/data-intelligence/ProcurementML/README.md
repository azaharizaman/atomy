# Nexus Procurement-ML Adapter

**Package:** `nexus/procurement-ml`

## Overview

This package provides Machine Learning (ML) feature extraction capabilities for the `nexus/procurement` package. It is designed as an optional adapter, allowing consumers to leverage ML-powered analytics for procurement processes without forcing the dependency on applications that do not require it.

This adheres to the Nexus philosophy of package atomicity and framework agnosticism.

## Features

- **Feature Extraction for Procurement Entities:** Provides `FeatureExtractorInterface` implementations for various procurement-related entities.
- **Anomaly Detection:** Extracts features for identifying anomalies in:
    - Purchase Order Quantities
    - Vendor Pricing
- **Risk Prediction:** Extracts features for predicting:
    - Requisition Approval Risks
    - Budget Overruns
    - GRN Discrepancies
- **Fraud Detection:** Extracts features for vendor fraud detection.
- **Efficiency Analysis:** Extracts features for PO conversion efficiency.

## Installation

```bash
composer require nexus/procurement-ml:"*@dev"
```

## Core Concepts

This package acts as a bridge between the `nexus/procurement` package and the `nexus/machine-learning` package. The extractors in this package are designed to be used by the services in `nexus/machine-learning`.

### Available Extractors

- `BudgetOverrunPredictionExtractor`
- `GRNDiscrepancyPredictionExtractor`
- `POConversionEfficiencyExtractor`
- `ProcurementPOQtyExtractor`
- `RequisitionApprovalRiskExtractor`
- `VendorFraudDetectionExtractor`
- `VendorPricingAnomalyExtractor`

### Available Analytics Repository Interfaces

- `ApprovalAnalyticsRepositoryInterface`
- `BudgetAnalyticsRepositoryInterface`
- `ConversionAnalyticsRepositoryInterface`
- `DeliveryAnalyticsRepositoryInterface`
- `HistoricalDataRepositoryInterface`
- `PricingAnalyticsRepositoryInterface`
- `VendorAnalyticsRepositoryInterface`

## Application Layer Integration

### Laravel Example

In your application's service provider, you would bind the analytics repository interfaces to your concrete implementations.

```php
// App\Providers\ProcurementMLServiceProvider.php

use App\Repositories\Procurement;
use Nexus\ProcurementML\Contracts;
use Illuminate\Support\ServiceProvider;

class ProcurementMLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            Contracts\ApprovalAnalyticsRepositoryInterface::class,
            Procurement\ApprovalAnalyticsRepository::class
        );
        
        $this->app->singleton(
            Contracts\BudgetAnalyticsRepositoryInterface::class,
            Procurement\BudgetAnalyticsRepository::class
        );

        // ... bind other interfaces
    }
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
