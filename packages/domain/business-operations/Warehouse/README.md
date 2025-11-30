# Nexus\Warehouse

Framework-agnostic warehouse management package for Nexus ERP.

## Features

### Phase 1 (Current Release)
- **Warehouse management**: Multiple warehouses per tenant
- **Bin location tracking**: Optional GPS coordinates for navigation
- **Picking optimization**: TSP-based route optimization via `Nexus\Routing`
- **Distance reduction**: 15-30% improvement over sequential picking

### Phase 2 (Deferred 3-6 months pending Phase 1 validation)
- **Work order management**: Full WMS work order interface
- **Barcode scanning**: Real-time mobile scanning
- **WebSocket integration**: Real-time pick list updates

## Installation

```bash
composer require nexus/warehouse:"*@dev"
```

## Quick Start

```php
use Nexus\Warehouse\Contracts\PickingOptimizerInterface;

$optimizer = app(PickingOptimizerInterface::class);

// Optimize pick route
$result = $optimizer->optimizePickRoute(
    warehouseId: 'wh_001',
    pickItems: [
        ['bin_id' => 'bin_a1', 'product_id' => 'prod_1', 'quantity' => 10],
        ['bin_id' => 'bin_c5', 'product_id' => 'prod_2', 'quantity' => 5],
    ]
);

echo "Distance reduction: {$result->getDistanceImprovement()}%";
```

## 📖 Documentation

### Package Documentation
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api-reference.md)
- [Integration Guide](docs/integration-guide.md)
- [Examples](docs/examples/)

### Additional Resources
- `IMPLEMENTATION_SUMMARY.md` - Implementation progress
- `REQUIREMENTS.md` - Requirements
- `TEST_SUITE_SUMMARY.md` - Tests
- `VALUATION_MATRIX.md` - Valuation


## License

MIT License
