<?php

declare(strict_types=1);

/**
 * Basic Usage Example: Procurement-ML
 *
 * This example demonstrates how to use a feature extractor from this package
 * in conjunction with a service from the `nexus/machine-learning` package.
 *
 * This assumes you have already set up your dependency injection container
 * to bind the repository interfaces to your concrete implementations.
 */

use Nexus\MachineLearning\Contracts\AnomalyDetectionServiceInterface;
use Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface;
use Nexus\ProcurementML\Extractors\VendorPricingAnomalyExtractor;
use Nexus\ProcurementML\Contracts\PricingAnalyticsRepositoryInterface;

// ============================================
// Step 1: Mock Application Components
// ============================================

// Mock a Purchase Order object
$purchaseOrder = new class {
    public function getVendorId(): string { return 'VENDOR-123'; }
    public function getLines(): array {
        return [
            (object)['productId' => 'PROD-A', 'unitPrice' => 110.00],
        ];
    }
};

// Mock the application's repository implementation
$pricingAnalyticsRepo = new class implements PricingAnalyticsRepositoryInterface {
    public function getHistoricalPricingForVendor(string $vendorId, string $productId): array {
        // In a real app, this would query the DB
        return [
            ['unit_price' => 100.00],
            ['unit_price' => 102.00],
            ['unit_price' => 99.00],
        ];
    }
    public function getPeerPricingForProduct(string $productId, \DateTimeImmutable $date): array {
        return []; // Not needed for this extractor
    }
};

// ============================================
// Step 2: Instantiate the Extractor
// ============================================

// The extractor takes your repository implementation as a dependency.
// In a real application, DI would handle this.
$extractor = new VendorPricingAnomalyExtractor($pricingAnalyticsRepo);

// ============================================
// Step 3: Use with a Machine Learning Service
// ============================================

// Mock the ML service that would consume the extractor
$anomalyDetector = new class($extractor) implements AnomalyDetectionServiceInterface {
    public function __construct(private readonly VendorPricingAnomalyExtractor $extractor) {}

    public function detectAnomalies(string $domain, object $entity): object {
        if ($domain !== 'procurement.vendor_pricing') {
            throw new \Exception('Invalid domain');
        }

        $features = $this->extractor->extract($entity);

        // Simplified anomaly detection logic
        $isAnomaly = $features['price_deviation_std_dev'] > 2.0;

        return (object)[
            'is_anomaly' => $isAnomaly,
            'reason' => $isAnomaly ? 'Price is more than 2 standard deviations from historical average.' : null,
            'features' => $features,
        ];
    }
};

// ============================================
// Step 4: Execute and Get Results
// ============================================

$result = $anomalyDetector->detectAnomalies('procurement.vendor_pricing', $purchaseOrder);

echo "Anomaly Detected: " . ($result->is_anomaly ? 'Yes' : 'No') . "\n";
if ($result->is_anomaly) {
    echo "Reason: " . $result->reason . "\n";
}
echo "Extracted Features:\n";
print_r($result->features);

// Expected output:
// Anomaly Detected: Yes
// Reason: Price is more than 2 standard deviations from historical average.
// Extracted Features:
// Array
// (
//     [avg_historical_price] => 100.33333333333333
//     [current_price] => 110
//     [price_deviation] => 9.666666666666671
//     [price_deviation_std_dev] => 3.7712361663282534
// )
