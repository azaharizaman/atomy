# API Reference: Procurement-ML

## Interfaces

This package provides a set of interfaces for analytics repositories and feature extractors.

### Analytics Repository Interfaces

These interfaces must be implemented by the consuming application to provide data to the feature extractors.

- **`ApprovalAnalyticsRepositoryInterface`**: Provides data for requisition approval risk analysis.
- **`BudgetAnalyticsRepositoryInterface`**: Provides data for budget overrun predictions.
- **`ConversionAnalyticsRepositoryInterface`**: Provides data for PO conversion efficiency analysis.
- **`DeliveryAnalyticsRepositoryInterface`**: Provides data for delivery date predictions.
- **`HistoricalDataRepositoryInterface`**: Provides generic historical data for various entities.
- **`PricingAnalyticsRepositoryInterface`**: Provides data for vendor pricing anomaly detection.
- **`VendorAnalyticsRepositoryInterface`**: Provides data for vendor fraud detection.

### Feature Extractor Interfaces

All extractors implement the `Nexus\MachineLearning\Contracts\FeatureExtractorInterface`.

---

## Services

This package does not contain any public services. The extractors are intended to be consumed by services in the `nexus/machine-learning` package.

---

## Value Objects

This package does not contain any public value objects.

---

## Enums

This package does not contain any public enums.

---

## Exceptions

This package does not contain any custom exceptions.
