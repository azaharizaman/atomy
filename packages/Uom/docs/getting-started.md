# Getting Started with Nexus UoM

## Overview

Nexus UoM is a **framework-agnostic Unit of Measurement (UoM) management and conversion engine** for Nexus ERP. It provides type-safe quantity handling, automatic unit conversions, and support for complex measurement scenarios including packaging hierarchies and temperature conversions.

**Critical Principle:** All measurements in a business system should be immutable value objects with explicit units. This package provides the foundation for type-safe measurement handling across your entire application.

---

## Prerequisites

- **PHP 8.3 or higher** (readonly properties, constructor property promotion)
- **Composer** for package management
- **BCMath or GMP extension** (recommended for arbitrary precision calculations)

### Recommended
- **Nexus\Product** for product catalog integration with UoM
- **Nexus\Inventory** for inventory management with UoM tracking
- **Nexus\Storage** for persistent unit/dimension storage

---

## When to Use This Package

This package is designed for:

✅ **ERP systems** requiring unit of measurement management  
✅ **Inventory systems** tracking quantities in multiple units  
✅ **Manufacturing** with recipe/BOM conversions (kg → g, L → mL)  
✅ **International commerce** with metric/imperial conversions  
✅ **Packaging hierarchies** (pallets → cases → eaches)  
✅ **Temperature monitoring** with Celsius/Fahrenheit/Kelvin conversions  
✅ **Scientific applications** requiring precision conversions  

Do NOT use this package for:

❌ **Simple numeric calculations** - Use native PHP math  
❌ **Currency conversions** - Use `Nexus\Currency` instead  
❌ **Date/time calculations** - Use native PHP DateTimeImmutable  
❌ **Percentage calculations** - Not a measurement unit concern  

---

## Core Concepts

### Concept 1: Quantity Value Objects Are the Primary API

The `Quantity` value object is your main entry point for working with measurements:

```php
use Nexus\Uom\ValueObjects\Quantity;

// Create quantities with value + unit
$weight = new Quantity(100.5, 'kg');
$distance = new Quantity(1000, 'm');
$temperature = new Quantity(25, 'celsius');

// All instances are immutable - operations return new instances
$doubled = $weight->multiply(2);  // Returns new Quantity(201.0, 'kg')
// Original $weight is unchanged
```

**Key Points:**
- **Immutable**: Operations return new instances; originals never change
- **Type-safe**: Each quantity carries its unit, preventing errors
- **Serializable**: Can be stored in JSON, arrays, databases

### Concept 2: Dimensions Group Related Units

Dimensions organize units that measure the same physical property:

```
┌─────────────────────────────────────────────────────────┐
│                    Dimensions                            │
├──────────────┬──────────────┬──────────────┬────────────┤
│     Mass     │    Length    │  Temperature │   Volume   │
│   Base: kg   │   Base: m    │  Base: kelvin│  Base: L   │
├──────────────┼──────────────┼──────────────┼────────────┤
│  kg, g, lb   │  m, cm, in   │  °C, °F, K   │  L, mL, gal│
│  oz, ton     │  ft, mi, km  │              │  qt, pt    │
└──────────────┴──────────────┴──────────────┴────────────┘
```

- You can only convert between units in the same dimension
- Each dimension has a **base unit** used for multi-hop conversions
- Attempting to convert incompatible units throws `IncompatibleUnitException`

### Concept 3: Graph-Based Conversion Pathfinding

The conversion engine uses graph algorithms to find conversion paths:

```
Direct conversion:
  kg → lb (ratio: 2.20462)

Multi-hop conversion:
  oz → kg → g
  (oz → kg: 0.0283495) × (kg → g: 1000) = 28.3495
```

**Benefits:**
- You only define direct conversions you know
- Engine automatically finds multi-hop paths
- Caches computed paths for performance
- Detects circular references to prevent infinite loops

### Concept 4: Arithmetic with Automatic Conversion

Quantities can be added/subtracted with automatic unit conversion:

```php
$weight1 = new Quantity(100, 'kg');
$weight2 = new Quantity(50, 'lb');  // Different unit!

// Automatic conversion to first operand's unit
$total = $weight1->add($weight2, $engine);
// Result: Quantity(122.68, 'kg')  [50 lb ≈ 22.68 kg]

$difference = $weight1->subtract($weight2, $engine);
// Result: Quantity(77.32, 'kg')
```

**Rules:**
- Addition/subtraction requires compatible units (same dimension)
- Result is always in the first operand's unit
- Second operand is automatically converted
- Throws `IncompatibleUnitException` for incompatible units

### Concept 5: Framework Agnosticism

All dependencies are interfaces. Your application provides implementations:

```php
// Package defines the contract
interface UomRepositoryInterface {
    public function findUnitByCode(string $code): ?UnitInterface;
    public function findConversion(string $from, string $to): ?ConversionRuleInterface;
    // ...
}

// You provide the implementation
class LaravelUomRepository implements UomRepositoryInterface {
    // Use Eloquent, Query Builder, or any storage
}

// Bind in your container
$app->bind(UomRepositoryInterface::class, LaravelUomRepository::class);
```

This allows the package to work with **any PHP framework** or **no framework at all**.

---

## Installation

### Step 1: Install via Composer

Add the package to your project:

```bash
composer require nexus/uom:"*@dev"
```

### Step 2: Verify PHP Version

Ensure you're running PHP 8.3+:

```bash
php -v
# Should show: PHP 8.3.x or higher
```

### Step 3: Check BCMath Extension (Recommended)

For precision calculations, verify BCMath is installed:

```bash
php -m | grep bcmath
# Should output: bcmath
```

If not installed:

```bash
# Ubuntu/Debian
sudo apt-get install php8.3-bcmath

# macOS with Homebrew
brew install php@8.3
# BCMath is included by default

# Restart your web server
sudo service php8.3-fpm restart
```

---

## Quick Start: Your First Conversion

Let's create a simple conversion example in 5 steps:

### Step 1: Implement the Repository Interface

Create a simple in-memory repository:

```php
<?php

use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\Contracts\UnitInterface;
use Nexus\Uom\Contracts\DimensionInterface;
use Nexus\Uom\Contracts\ConversionRuleInterface;
use Nexus\Uom\ValueObjects\Unit;
use Nexus\Uom\ValueObjects\Dimension;
use Nexus\Uom\ValueObjects\ConversionRule;

class InMemoryUomRepository implements UomRepositoryInterface
{
    private array $units = [];
    private array $dimensions = [];
    private array $conversions = [];

    public function __construct()
    {
        // Define Mass dimension
        $this->dimensions['mass'] = new Dimension(
            code: 'mass',
            name: 'Mass',
            baseUnit: 'kg',
            allowsOffset: false
        );

        // Define units
        $this->units['kg'] = new Unit('kg', 'Kilogram', 'kg', 'mass', 'metric', true);
        $this->units['g'] = new Unit('g', 'Gram', 'g', 'mass', 'metric');
        $this->units['lb'] = new Unit('lb', 'Pound', 'lb', 'mass', 'imperial');

        // Define conversions
        $this->conversions['kg:g'] = new ConversionRule('kg', 'g', 1000.0);
        $this->conversions['kg:lb'] = new ConversionRule('kg', 'lb', 2.20462);
    }

    public function findUnitByCode(string $code): ?UnitInterface
    {
        return $this->units[$code] ?? null;
    }

    public function findDimensionByCode(string $code): ?DimensionInterface
    {
        return $this->dimensions[$code] ?? null;
    }

    public function findConversion(string $from, string $to): ?ConversionRuleInterface
    {
        return $this->conversions["{$from}:{$to}"] ?? null;
    }

    public function getConversionsFrom(string $fromUnit): array
    {
        return array_filter(
            $this->conversions,
            fn($rule) => $rule->getFromUnit() === $fromUnit
        );
    }

    // Implement other required methods with empty arrays/null for now
    public function getUnitsByDimension(string $dimensionCode): array { return []; }
    public function getUnitsBySystem(string $systemCode): array { return []; }
    public function getConversionsByDimension(string $dimensionCode): array { return []; }
    public function saveUnit(UnitInterface $unit): UnitInterface { return $unit; }
    public function saveDimension(DimensionInterface $dimension): DimensionInterface { return $dimension; }
    public function saveConversion(ConversionRuleInterface $rule): ConversionRuleInterface { return $rule; }
    public function deleteUnit(string $code): void {}
    public function deleteDimension(string $code): void {}
    public function deleteConversion(string $from, string $to): void {}
}
```

### Step 2: Create the Services

Wire up the conversion engine:

```php
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Uom\Services\UomValidationService;
use Nexus\Uom\Services\UomManager;

$repository = new InMemoryUomRepository();
$validator = new UomValidationService($repository);
$engine = new UomConversionEngine($repository, $validator);
$manager = new UomManager($repository, $engine, $validator);
```

### Step 3: Create Your First Quantity

```php
use Nexus\Uom\ValueObjects\Quantity;

$weight = new Quantity(100, 'kg');

echo $weight->format('en_US', 2);
// Output: "100.00 kg"
```

### Step 4: Perform a Conversion

```php
// Convert kilograms to pounds
$pounds = $weight->convertTo('lb', $engine);

echo $pounds->format('en_US', 2);
// Output: "220.46 lb"
```

### Step 5: Perform Arithmetic

```php
$weight1 = new Quantity(50, 'kg');
$weight2 = new Quantity(20, 'kg');

$total = $weight1->add($weight2, $engine);
echo $total->format('en_US', 2);
// Output: "70.00 kg"

$doubled = $weight1->multiply(2);
echo $doubled->format('en_US', 2);
// Output: "100.00 kg"
```

---

## Common Usage Patterns

### Pattern 1: Product Catalog Integration

Track products with multiple UoM options:

```php
class Product
{
    public function __construct(
        public string $sku,
        public string $name,
        public Quantity $baseQuantity,  // e.g., Quantity(1, 'each')
        public array $alternativeUnits = []  // ['case' => 12, 'pallet' => 1440]
    ) {}
}

// Create a product sold in multiple units
$product = new Product(
    sku: 'WIDGET-001',
    name: 'Premium Widget',
    baseQuantity: new Quantity(1, 'each'),
    alternativeUnits: [
        'case' => 12,    // 1 case = 12 eaches
        'pallet' => 1440 // 1 pallet = 1440 eaches
    ]
);

// Convert order quantity from cases to eaches
$orderQuantity = new Quantity(5, 'case');
$eaches = $orderQuantity->convertTo('each', $engine);
// Result: Quantity(60, 'each')
```

### Pattern 2: Recipe/BOM Conversions

Manufacturing recipes with unit conversions:

```php
class RecipeIngredient
{
    public function __construct(
        public string $ingredientId,
        public Quantity $quantity
    ) {}

    public function getQuantityIn(string $targetUnit, UomConversionEngine $engine): Quantity
    {
        return $this->quantity->convertTo($targetUnit, $engine);
    }
}

// Recipe in grams
$flour = new RecipeIngredient('flour', new Quantity(500, 'g'));
$sugar = new RecipeIngredient('sugar', new Quantity(200, 'g'));

// Batch production needs kilograms
$flourKg = $flour->getQuantityIn('kg', $engine);
echo $flourKg->format('en_US', 3);
// Output: "0.500 kg"
```

### Pattern 3: Inventory Tracking with Mixed Units

Handle inventory received in different units:

```php
class InventoryTransaction
{
    public function __construct(
        public Quantity $quantity,
        public string $type  // 'receipt', 'issue', 'adjustment'
    ) {}
}

// Track inventory in base unit (kg)
$currentStock = new Quantity(1000, 'kg');

// Receive shipment in pounds
$receipt = new InventoryTransaction(
    quantity: new Quantity(500, 'lb'),
    type: 'receipt'
);

// Convert and add to stock
$receiptKg = $receipt->quantity->convertTo('kg', $engine);
$newStock = $currentStock->add($receiptKg, $engine);

echo "Stock after receipt: " . $newStock->format('en_US', 2);
// Output: "Stock after receipt: 1,226.80 kg"
```

### Pattern 4: Temperature Monitoring

Converting between Celsius and Fahrenheit:

```php
// Warehouse temperature in Celsius
$warehouseTemp = new Quantity(22, 'celsius');

// Convert to Fahrenheit for US reporting
$tempF = $warehouseTemp->convertTo('fahrenheit', $engine);
echo $tempF->format('en_US', 1);
// Output: "71.6 fahrenheit"

// Check against threshold
$maxTemp = new Quantity(75, 'fahrenheit');
$maxTempC = $maxTemp->convertTo('celsius', $engine);

if ($warehouseTemp->getValue() < $maxTempC->getValue()) {
    echo "Temperature within safe limits";
}
```

### Pattern 5: Validation Before Operations

Always validate compatibility before operations:

```php
use Nexus\Uom\Exceptions\IncompatibleUnitException;

$weight = new Quantity(100, 'kg');
$distance = new Quantity(50, 'm');

try {
    // This will throw IncompatibleUnitException
    $invalid = $weight->add($distance, $engine);
} catch (IncompatibleUnitException $e) {
    echo "Cannot add mass and length: " . $e->getMessage();
}

// Check compatibility first
if ($validator->areConvertible($weight, $distance)) {
    $sum = $weight->add($distance, $engine);
} else {
    echo "Units are not compatible";
}
```

---

## Working with Dimensions and Units

### Creating Custom Dimensions

Define your own measurement dimensions:

```php
// Create Volume dimension
$volumeDimension = $manager->createDimension(
    code: 'volume',
    name: 'Volume',
    baseUnit: 'liter',
    allowsOffset: false,
    description: 'Volumetric measurements'
);

// Create units for this dimension
$liter = $manager->createUnit(
    code: 'liter',
    name: 'Liter',
    symbol: 'L',
    dimension: 'volume',
    system: 'metric',
    isBaseUnit: true
);

$milliliter = $manager->createUnit(
    code: 'milliliter',
    name: 'Milliliter',
    symbol: 'mL',
    dimension: 'volume',
    system: 'metric'
);

// Define conversion: 1 L = 1000 mL
$conversionRule = $manager->createConversion(
    fromUnit: 'liter',
    toUnit: 'milliliter',
    ratio: 1000.0
);
```

### Understanding Unit Systems

Group units by regional standards:

```php
// Metric system units
$metricUnits = $repository->getUnitsBySystem('metric');
// Returns: [kg, g, m, cm, L, mL, ...]

// Imperial system units
$imperialUnits = $repository->getUnitsBySystem('imperial');
// Returns: [lb, oz, ft, in, gal, qt, ...]

// Display preference based on user locale
function displayQuantity(Quantity $qty, string $userLocale): string
{
    $preferredSystem = ($userLocale === 'en_US') ? 'imperial' : 'metric';
    
    // Convert to user's preferred system if different
    // ... implementation depends on your system preference logic
    
    return $qty->format($userLocale);
}
```

---

## Packaging Hierarchies

### Setting Up Packaging Conversions

Define relationships between packaging levels:

```php
// Define Quantity dimension for packaging
$quantityDim = $manager->createDimension(
    code: 'quantity',
    name: 'Quantity',
    baseUnit: 'each',
    allowsOffset: false
);

// Create packaging units
$each = $manager->createUnit('each', 'Each', 'ea', 'quantity', null, true);
$case = $manager->createUnit('case', 'Case', 'cs', 'quantity');
$pallet = $manager->createUnit('pallet', 'Pallet', 'plt', 'quantity');

// Define conversions
$manager->createConversion('case', 'each', 12.0);      // 1 case = 12 eaches
$manager->createConversion('pallet', 'case', 120.0);   // 1 pallet = 120 cases
```

### Converting Between Packaging Levels

```php
// Order is 5 pallets
$orderQty = new Quantity(5, 'pallet');

// Warehouse picks in cases
$cases = $orderQty->convertTo('case', $engine);
echo $cases->format('en_US', 0);
// Output: "600 case"

// Inventory counts in eaches
$eaches = $orderQty->convertTo('each', $engine);
echo $eaches->format('en_US', 0);
// Output: "7,200 each"
```

**Multi-hop conversion happens automatically:**
```
pallet → case → each
  5 × 120 = 600 cases
  600 × 12 = 7,200 eaches
```

---

## Performance Considerations

### Caching Conversion Paths

The engine automatically caches computed conversion ratios:

```php
// First conversion computes and caches path
$pounds1 = new Quantity(100, 'kg')->convertTo('lb', $engine);
// Cache miss: Computes path kg → lb, stores ratio 2.20462

// Subsequent conversions use cache
$pounds2 = new Quantity(200, 'kg')->convertTo('lb', $engine);
// Cache hit: Uses stored ratio, no path computation
```

**Performance Metrics:**
- Direct conversions: **< 1ms** (single lookup)
- Multi-hop conversions (first time): **5-15ms** (path finding + caching)
- Multi-hop conversions (cached): **< 2ms** (cached ratio lookup)

### Batch Operations

Process multiple conversions efficiently:

```php
$weights = [
    new Quantity(100, 'kg'),
    new Quantity(200, 'kg'),
    new Quantity(300, 'kg'),
];

$converted = array_map(
    fn($qty) => $qty->convertTo('lb', $engine),
    $weights
);

// First conversion caches path, rest use cache
// Total time: ~5ms + (3 × 1ms) = ~8ms
```

### Pre-warming Cache

For high-performance scenarios, pre-warm common conversions:

```php
// During application bootstrap
$commonConversions = [
    ['kg', 'lb'], ['kg', 'g'], ['lb', 'oz'],
    ['m', 'ft'], ['m', 'cm'], ['ft', 'in'],
];

foreach ($commonConversions as [$from, $to]) {
    // This caches the conversion path
    $engine->convert(1.0, $from, $to);
}
```

---

## Error Handling

### Common Exceptions

The package throws specific exceptions for different error scenarios:

```php
use Nexus\Uom\Exceptions\UnitNotFoundException;
use Nexus\Uom\Exceptions\IncompatibleUnitException;
use Nexus\Uom\Exceptions\ConversionPathNotFoundException;
use Nexus\Uom\Exceptions\CircularConversionException;

try {
    $qty = new Quantity(100, 'invalid_unit');
    $converted = $qty->convertTo('kg', $engine);
} catch (UnitNotFoundException $e) {
    // Unit code doesn't exist in repository
    echo "Unit not found: " . $e->getMessage();
} catch (IncompatibleUnitException $e) {
    // Units are from different dimensions
    echo "Cannot convert: " . $e->getMessage();
} catch (ConversionPathNotFoundException $e) {
    // No conversion path exists between units
    echo "No conversion path: " . $e->getMessage();
} catch (CircularConversionException $e) {
    // Circular reference detected in conversion graph
    echo "Circular conversion: " . $e->getMessage();
}
```

### Validation Before Conversion

Use validation service to check before converting:

```php
$qty1 = new Quantity(100, 'kg');
$qty2 = new Quantity(50, 'm');

// Check if units can be converted
if (!$validator->areConvertible($qty1, $qty2)) {
    echo "Cannot convert between mass and length";
    return;
}

// Safe to proceed
$sum = $qty1->add($qty2, $engine);
```

### Graceful Degradation

Handle missing conversions gracefully:

```php
function safeConvert(Quantity $qty, string $targetUnit, UomConversionEngine $engine): ?Quantity
{
    try {
        return $qty->convertTo($targetUnit, $engine);
    } catch (ConversionPathNotFoundException $e) {
        // Log the error
        error_log("Cannot convert {$qty->getUnitCode()} to {$targetUnit}: " . $e->getMessage());
        
        // Return null or original quantity
        return null;
    }
}

$result = safeConvert($weight, 'lb', $engine);
if ($result !== null) {
    echo "Converted: " . $result->format('en_US', 2);
} else {
    echo "Using original: " . $weight->format('en_US', 2);
}
```

---

## Troubleshooting

### Problem: "Unit not found" error

**Symptom:**
```
UnitNotFoundException: Unit with code 'kg' not found
```

**Solution:**
Ensure your repository implementation returns the requested unit:

```php
public function findUnitByCode(string $code): ?UnitInterface
{
    // Check your data source
    $unit = $this->database->table('uom_units')
        ->where('code', $code)
        ->first();
    
    return $unit ? $this->mapToUnitInterface($unit) : null;
}
```

### Problem: "No conversion path found"

**Symptom:**
```
ConversionPathNotFoundException: No conversion path from 'oz' to 'kg'
```

**Solution:**
Verify conversion rules exist for the dimension:

```php
// Check if direct conversion exists
$rule = $repository->findConversion('oz', 'kg');
if ($rule === null) {
    echo "No direct conversion, will try multi-hop";
}

// Check if both units share the same base unit
$ozUnit = $repository->findUnitByCode('oz');
$kgUnit = $repository->findUnitByCode('kg');

if ($ozUnit->getDimension() !== $kgUnit->getDimension()) {
    echo "Units are from different dimensions!";
}
```

### Problem: Incorrect conversion results

**Symptom:**
```php
$kg = new Quantity(1, 'kg')->convertTo('lb', $engine);
// Expected: 2.20462 lb, Got: 0.45359 lb (inverse!)
```

**Solution:**
Check conversion rule direction and ratio:

```php
// Correct: kg → lb
$rule = new ConversionRule(
    fromUnit: 'kg',
    toUnit: 'lb',
    ratio: 2.20462  // 1 kg = 2.20462 lb
);

// Incorrect: ratio is inverted
$rule = new ConversionRule(
    fromUnit: 'kg',
    toUnit: 'lb',
    ratio: 0.45359  // This is lb → kg ratio!
);
```

### Problem: Circular conversion detected

**Symptom:**
```
CircularConversionException: Circular conversion path detected
```

**Solution:**
Review your conversion rules for cycles:

```php
// BAD: Creates a cycle
$conversions = [
    ['a', 'b', 2.0],
    ['b', 'c', 3.0],
    ['c', 'a', 0.5],  // Creates cycle: a → b → c → a
];

// GOOD: Use base unit as hub
$conversions = [
    ['kg', 'g', 1000.0],    // kg is base unit
    ['kg', 'lb', 2.20462],  // kg is base unit
    ['kg', 'oz', 35.274],   // kg is base unit
];
```

### Problem: Performance degradation

**Symptom:**
Conversions taking > 50ms per operation

**Solution:**
Enable conversion caching and use batch operations:

```php
// Pre-warm cache during bootstrap
$commonPairs = [['kg', 'lb'], ['m', 'ft']];
foreach ($commonPairs as [$from, $to]) {
    $engine->convert(1.0, $from, $to);  // Caches path
}

// Use batch operations instead of loops
$results = array_map(
    fn($qty) => $qty->convertTo('lb', $engine),
    $quantities  // Reuses cached path
);
```

---

## Next Steps

Now that you understand the basics:

1. **Read the [API Reference](api-reference.md)** for complete interface documentation
2. **Explore [Integration Guide](integration-guide.md)** for Laravel/Symfony setup with database persistence
3. **Review [Basic Examples](examples/basic-usage.php)** for practical code samples
4. **Study [Advanced Examples](examples/advanced-usage.php)** for complex scenarios

---

## Getting Help

- **Package Issues**: File issues at the Nexus monorepo
- **Integration Questions**: Review the Integration Guide
- **Performance Tuning**: See Performance Considerations section above
- **Custom Requirements**: Extend via interfaces and dependency injection

---

**Last Updated:** November 28, 2024  
**Package Version:** 1.0.0-dev  
**Minimum PHP:** 8.3+
