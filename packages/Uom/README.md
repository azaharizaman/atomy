# Nexus UoM Package

**Framework-agnostic Unit of Measurement (UoM) management and conversion engine for the Nexus ERP system.**

## Overview

The `Nexus\Uom` package provides a pure PHP, framework-agnostic engine for managing units of measurement, performing conversions, and handling complex measurement scenarios including:

- **Immutable Quantity Value Objects** for type-safe measurement handling
- **Dimension-based unit organization** (Mass, Length, Time, Temperature, etc.)
- **Conversion graph engine** with automatic pathfinding for multi-hop conversions
- **Packaging hierarchies** (Pallets → Cases → Eaches)
- **Complex conversions** with offset support (e.g., Celsius to Fahrenheit)
- **Arithmetic operations** on quantities with automatic conversion
- **Unit systems** (Metric, Imperial) for regional consistency

## Features

### Core Capabilities

- **Framework Agnostic**: Pure PHP with no Laravel dependencies
- **Immutable Value Objects**: Thread-safe, predictable quantity handling
- **Type-Safe Operations**: Automatic validation prevents incompatible unit operations
- **Precision Math**: Uses BCMath/GMP for arbitrary precision calculations
- **Performance**: Direct conversions < 5ms, complex multi-hop < 20ms
- **Extensible**: Define custom dimensions, units, and conversion rules

### Key Components

1. **Quantity Value Object** (`ValueObjects/Quantity.php`)
   - Primary API entry point
   - Immutable value + unit pairing
   - Arithmetic operations with automatic conversion
   - Serialization support for JSON/arrays

2. **Conversion Engine** (`Services/UomConversionEngine.php`)
   - Graph-based pathfinding for multi-hop conversions
   - Direct ratio-based conversions
   - Offset conversions for temperature
   - Packaging hierarchy conversions

3. **Validation Service** (`Services/UomValidationService.php`)
   - Dimension compatibility checking
   - Ratio validation
   - Circular reference detection
   - System unit protection

4. **Dimension Value Object** (`ValueObjects/Dimension.php`)
   - Groups related units (Mass, Length, etc.)
   - Defines base unit for each dimension
   - Ensures conversion compatibility

## Installation

Add to your `composer.json`:

```bash
composer require nexus/uom:"*@dev"
```

## Basic Usage

### Creating Quantities

```php
use Nexus\Uom\ValueObjects\Quantity;

// Create a quantity with value and unit code
$weight = new Quantity(100.5, 'kg');
$distance = new Quantity(1000, 'm');
```

### Arithmetic Operations

```php
// Addition with automatic conversion
$total = $weight1->add($weight2); // Converts to same unit automatically

// Subtraction
$difference = $weight1->subtract($weight2);

// Multiplication (by scalar)
$doubled = $weight->multiply(2);

// Division (by scalar)
$half = $weight->divide(2);
```

### Unit Conversion

```php
// Convert to different unit
$pounds = $weight->convertTo('lb');

// Check if convertible
if ($validator->areConvertible($qty1, $qty2)) {
    $sum = $qty1->add($qty2);
}
```

### Formatting for Display

```php
// Format with locale-specific separators
echo $weight->format('en_US'); // "100.50 kg"
echo $weight->format('de_DE'); // "100,50 kg"
```

### Serialization

```php
// To array
$array = $quantity->toArray();
// ['value' => 100.5, 'unit' => 'kg']

// To JSON
$json = $quantity->toJson();
// {"value":100.5,"unit":"kg"}

// From array
$quantity = Quantity::fromArray(['value' => 100.5, 'unit' => 'kg']);
```

## Architecture

This package follows the **"Logic in Packages, Implementation in Applications"** principle:

- **Package Layer** (`packages/Uom/`): Framework-agnostic business logic
  - Interfaces defining all persistence needs
  - Value Objects for immutable data structures
  - Services containing conversion and validation logic
  - Domain-specific exceptions

- **Application Layer** (`apps/Atomy/`): Laravel-specific implementation
  - Eloquent models implementing package interfaces
  - Database migrations for schema
  - Repository implementations
  - Service provider for IoC bindings

## Requirements Satisfied

This package satisfies **58 total requirements**:
- 10 Architectural Requirements
- 10 Business Requirements
- 13 Functional Requirements
- 5 Performance Requirements
- 5 Security Requirements
- 5 Reliability Requirements
- 10 User Stories

See `docs/UOM_IMPLEMENTATION.md` for complete requirement mapping.

## Dependencies

**Package Dependencies:**
- PHP ^8.2
- No framework dependencies

**Atomy Implementation Dependencies:**
- Laravel 12
- Laravel Eloquent ORM

## Testing

```bash
# Package tests (unit tests, no database)
cd packages/Uom
composer test

# Atomy tests (feature tests with database)
cd apps/Atomy
php artisan test --filter=Uom
```

## Documentation

- [Complete Implementation Guide](../../docs/UOM_IMPLEMENTATION.md)
- [Architecture Documentation](../../ARCHITECTURE.md)
- [Requirements Specification](../../REQUIREMENTS.csv)

## Security

- Tenant isolation enforced at application layer
- Audit logging for custom unit creation
- Version tracking for conversion ratio changes
- System unit protection (cannot delete/modify)

## Performance

- Simple conversions: < 5ms
- Multi-hop conversions (up to 5 hops): < 20ms
- Arithmetic operations with conversion: < 10ms
- Conversion path caching for repeated operations
- Supports 1,000+ defined units without degradation

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

For issues, questions, or contributions, please refer to the main Nexus monorepo documentation.
