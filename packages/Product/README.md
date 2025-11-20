# Nexus\Product

**Framework-agnostic product catalog management package for the Nexus ERP monorepo.**

## Overview

`Nexus\Product` provides a comprehensive master data management system for product catalogs, supporting both simple standalone products and complex configurable products with template-variant architecture. It integrates seamlessly with `Nexus\Uom` for dimensional data, `Nexus\Sequencing` for SKU generation, and serves as the foundation for downstream domains like Procurement, Sales, and Inventory.

## Core Philosophy

This package defines **WHAT** a product is (master data), not **WHERE** it's stored or **HOW MUCH** stock exists. Transactional data like inventory levels, pricing, and availability belong in domain-specific packages (`Nexus\Inventory`, `Nexus\Sales`).

## Features

### Template-Variant Architecture
- **Product Templates**: Conceptual products with shared attributes (e.g., "T-Shirt Model X")
- **Product Variants**: Transactable SKUs with unique identifiers (e.g., "T-Shirt Model X, Red, Size M")
- **Standalone Products**: Simple products that don't require template structure

### Master Data Management
- **Hierarchical Categories**: Unlimited nesting with adjacency list pattern
- **Attribute Sets**: Configurable attributes (Color, Size, Material) with value management
- **SKU Generation**: Integration with `Nexus\Sequencing` for unique identifier assignment
- **Barcode Handling**: Multi-format support (EAN-13, UPC-A, CODE-128, QR) with validation

### Physical Dimensions
- **Unit-Aware Measurements**: Integration with `Nexus\Uom\Quantity` for weight, volume, dimensions
- **Dimension Sets**: Complete physical specifications (length, width, height, weight)

### Product Classification
- **Product Types**: STORABLE (inventory-tracked), CONSUMABLE (buy/use), SERVICE (intangible)
- **Tracking Methods**: NONE, LOT_NUMBER (batch tracking), SERIAL_NUMBER (unique instances)

### Enterprise Features
- **Variant Explosion Prevention**: Configurable limits on variant combinations
- **Duplicate Detection**: SKU and barcode uniqueness enforcement
- **Category Circular Reference Protection**: Validation for organizational hierarchies
- **Multi-Tenant Support**: Tenant scoping for SaaS deployments

## Installation

This package is part of the Nexus monorepo. Install it in your Laravel application:

```bash
composer require nexus/product:"*@dev"
```

## Architecture

### Package Structure

```
packages/Product/
├── composer.json
├── LICENSE
├── README.md
└── src/
    ├── Contracts/              # All interface definitions
    │   ├── CategoryInterface.php
    │   ├── CategoryRepositoryInterface.php
    │   ├── ProductTemplateInterface.php
    │   ├── ProductTemplateRepositoryInterface.php
    │   ├── ProductVariantInterface.php
    │   ├── ProductVariantRepositoryInterface.php
    │   ├── AttributeSetInterface.php
    │   └── AttributeRepositoryInterface.php
    ├── Enums/                  # PHP 8.3 native enums
    │   ├── ProductType.php
    │   ├── TrackingMethod.php
    │   └── BarcodeFormat.php
    ├── ValueObjects/           # Immutable value objects
    │   ├── Sku.php
    │   ├── Barcode.php
    │   └── DimensionSet.php
    ├── Services/               # Business logic
    │   ├── ProductManager.php
    │   ├── VariantGenerator.php
    │   ├── SkuGenerator.php
    │   └── BarcodeService.php
    └── Exceptions/             # Domain-specific exceptions
        ├── ProductException.php
        ├── ProductNotFoundException.php
        ├── DuplicateSkuException.php
        ├── VariantExplosionException.php
        ├── InvalidBarcodeException.php
        └── CircularCategoryReferenceException.php
```

### Framework Agnostic Design

This package contains **ZERO** Laravel-specific code. All dependencies are injected via constructor:

- ✅ Pure PHP 8.3 with strict types
- ✅ PSR-3 `LoggerInterface` for logging
- ✅ Constructor property promotion with `readonly`
- ✅ Native enums with `match` expressions
- ❌ NO Laravel Facades (`Log::`, `Cache::`, `DB::`)
- ❌ NO global helpers (`now()`, `config()`, `app()`)
- ❌ NO Eloquent models or migrations

## Usage Examples

### Creating a Simple Product (Standalone Variant)

```php
use Nexus\Product\Services\ProductManager;
use Nexus\Product\Enums\ProductType;
use Nexus\Product\Enums\TrackingMethod;
use Nexus\Product\ValueObjects\Sku;
use Nexus\Uom\ValueObjects\Quantity;

$productManager->createStandaloneVariant(
    tenantId: 'tenant-123',
    code: 'WIDGET-001',
    name: 'Premium Widget',
    type: ProductType::STORABLE,
    trackingMethod: TrackingMethod::SERIAL_NUMBER,
    weight: new Quantity(2.5, 'kg'),
    categoryCode: 'HARDWARE'
);
```

### Creating a Configurable Product (Template + Variants)

```php
// 1. Create template
$template = $productManager->createTemplate(
    tenantId: 'tenant-123',
    code: 'TSHIRT-X',
    name: 'T-Shirt Model X',
    description: 'Premium cotton t-shirt',
    categoryCode: 'APPAREL'
);

// 2. Define attributes
$colorAttribute = $attributeRepository->findByCode('COLOR');
$sizeAttribute = $attributeRepository->findByCode('SIZE');

// 3. Generate variants
$variants = $variantGenerator->generateVariants(
    templateId: $template->getId(),
    attributes: [
        'COLOR' => ['Red', 'Blue', 'Green'],
        'SIZE' => ['S', 'M', 'L', 'XL']
    ]
);
// Creates 12 variants (3 colors × 4 sizes)
```

### Working with Barcodes

```php
use Nexus\Product\ValueObjects\Barcode;
use Nexus\Product\Enums\BarcodeFormat;

// EAN-13 validation
$barcode = new Barcode('5901234123457', BarcodeFormat::EAN13);

// Barcode service
$barcodeService->validate($barcode); // true if valid checksum
$barcodeService->lookupVariant($barcode); // Find product by barcode
```

### Preventing Variant Explosion

```php
// Configuration via Nexus\Setting
$settings->setInt('product.max_variants_per_template', 1000);

// This will throw VariantExplosionException if > 1000 combinations
$variantGenerator->generateVariants($templateId, [
    'COLOR' => [...], // 10 values
    'SIZE' => [...],  // 10 values
    'STYLE' => [...], // 10 values
    'FABRIC' => [...] // 10 values = 10,000 variants!
]);
```

## Integration Points

### Nexus\Uom (Unit of Measure)

All physical dimensions use `Nexus\Uom\Quantity`:

```php
use Nexus\Product\ValueObjects\DimensionSet;
use Nexus\Uom\ValueObjects\Quantity;

$dimensions = new DimensionSet(
    weight: new Quantity(5.5, 'kg'),
    length: new Quantity(30, 'cm'),
    width: new Quantity(20, 'cm'),
    height: new Quantity(10, 'cm'),
    volume: new Quantity(6, 'L')
);
```

### Nexus\Sequencing (SKU Generation)

SKU generation integrates with the sequencing engine:

```php
use Nexus\Product\Services\SkuGenerator;
use Nexus\Sequencing\Contracts\SequenceGeneratorInterface;

$skuGenerator = new SkuGenerator($sequenceGenerator);
$sku = $skuGenerator->generateSku('tenant-123', 'PRODUCT');
// Result: "PRD-2024-00001"
```

### Nexus\Finance (Default GL Accounts)

Products can reference default GL account codes (resolved at application layer):

```php
interface ProductVariantInterface {
    public function getDefaultRevenueAccountCode(): ?string;
    public function getDefaultCostAccountCode(): ?string;
    public function getDefaultInventoryAccountCode(): ?string;
}
```

### Nexus\Procurement (Purchase Orders)

Update `PurchaseOrderLineInterface` to reference products:

```php
interface PurchaseOrderLineInterface {
    public function getProductVariantId(): ?string;
    public function getItemDescription(): string; // Fallback for legacy
}
```

## Value Objects

### Sku

Immutable, validated SKU identifier:

```php
use Nexus\Product\ValueObjects\Sku;

$sku = new Sku('PRD-2024-00001');
$sku->getValue(); // "PRD-2024-00001"
$sku->toArray(); // ['value' => 'PRD-2024-00001']
```

### Barcode

Format-aware barcode with validation:

```php
use Nexus\Product\ValueObjects\Barcode;
use Nexus\Product\Enums\BarcodeFormat;

$barcode = new Barcode('5901234123457', BarcodeFormat::EAN13);
$barcode->getValue(); // "5901234123457"
$barcode->getFormat(); // BarcodeFormat::EAN13
```

### DimensionSet

Complete physical specifications:

```php
use Nexus\Product\ValueObjects\DimensionSet;
use Nexus\Uom\ValueObjects\Quantity;

$dimensions = new DimensionSet(
    weight: new Quantity(2.5, 'kg'),
    length: new Quantity(30, 'cm'),
    width: new Quantity(20, 'cm'),
    height: new Quantity(15, 'cm')
);

$dimensions->toArray();
// [
//     'weight' => ['value' => 2.5, 'unit' => 'kg'],
//     'length' => ['value' => 30, 'unit' => 'cm'],
//     ...
// ]
```

## Enums

### ProductType

```php
enum ProductType: string {
    case STORABLE = 'storable';     // Physical goods with inventory tracking
    case CONSUMABLE = 'consumable'; // Items consumed without stock tracking
    case SERVICE = 'service';       // Intangible services
}
```

### TrackingMethod

```php
enum TrackingMethod: string {
    case NONE = 'none';                   // No tracking
    case LOT_NUMBER = 'lot_number';       // Batch/lot tracking
    case SERIAL_NUMBER = 'serial_number'; // Unique instance tracking
}
```

### BarcodeFormat

```php
enum BarcodeFormat: string {
    case EAN13 = 'ean13';       // European Article Number (13 digits)
    case UPCA = 'upca';         // Universal Product Code (12 digits)
    case CODE128 = 'code128';   // High-density alphanumeric
    case QR = 'qr';             // QR Code (2D)
    case CUSTOM = 'custom';     // Custom format
}
```

## Exception Hierarchy

```
Exception
└── ProductException
    ├── ProductNotFoundException
    ├── ProductTemplateNotFoundException
    ├── CategoryNotFoundException
    ├── DuplicateSkuException
    ├── DuplicateBarcodeException
    ├── VariantExplosionException
    ├── InvalidBarcodeException
    ├── CircularCategoryReferenceException
    └── InvalidProductDataException
```

## Configuration

Product-related settings (managed via `Nexus\Setting`):

| Setting Key | Default | Description |
|-------------|---------|-------------|
| `product.max_variants_per_template` | 1000 | Maximum variants allowed per template |
| `product.default_category` | `GENERAL` | Default category for uncategorized products |
| `product.require_barcode` | `false` | Whether barcodes are mandatory |
| `product.auto_generate_sku` | `true` | Auto-generate SKUs via sequencing |

## Contributing

This package follows the Nexus monorepo architectural principles:

1. **Framework Agnostic**: Zero Laravel dependencies
2. **Contract-Driven**: All external needs defined via interfaces
3. **Immutable Value Objects**: All VOs use `readonly` modifier
4. **Modern PHP**: Constructor promotion, native enums, `match` expressions
5. **Dependency Injection**: All dependencies via constructor

## License

MIT License - see LICENSE file for details.

## Related Packages

- `Nexus\Uom` - Unit of measure management
- `Nexus\Sequencing` - Auto-numbering and sequence generation
- `Nexus\Procurement` - Purchase order management
- `Nexus\Inventory` - Stock and warehouse management
- `Nexus\Finance` - Financial accounting
- `Nexus\Setting` - Configuration management
- `Nexus\Tenant` - Multi-tenancy support
