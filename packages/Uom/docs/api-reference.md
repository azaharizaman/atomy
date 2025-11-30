# API Reference: UoM

Complete reference for all interfaces, value objects, services, and exceptions in Nexus UoM package.

---

## Core Interfaces

### UomRepositoryInterface

Primary interface for persisting and retrieving UoM data. All storage operations are abstracted behind this interface for framework agnosticism.

```php
namespace Nexus\Uom\Contracts;

interface UomRepositoryInterface
{
    /**
     * Find a unit by its unique code
     *
     * @param string $code The unit code (e.g., 'kg', 'm', 'lb')
     * @return UnitInterface|null The unit if found, null otherwise
     */
    public function findUnitByCode(string $code): ?UnitInterface;

    /**
     * Find a dimension by its unique code
     *
     * @param string $code The dimension code (e.g., 'mass', 'length')
     * @return DimensionInterface|null The dimension if found, null otherwise
     */
    public function findDimensionByCode(string $code): ?DimensionInterface;

    /**
     * Get all units belonging to a specific dimension
     *
     * @param string $dimensionCode The dimension code
     * @return UnitInterface[] Array of units in the dimension
     */
    public function getUnitsByDimension(string $dimensionCode): array;

    /**
     * Get all units belonging to a specific unit system
     *
     * @param string $systemCode The unit system code (e.g., 'metric', 'imperial')
     * @return UnitInterface[] Array of units in the system
     */
    public function getUnitsBySystem(string $systemCode): array;

    /**
     * Find a direct conversion rule between two units
     *
     * @param string $fromUnitCode Source unit code
     * @param string $toUnitCode Target unit code
     * @return ConversionRuleInterface|null The conversion rule if exists, null otherwise
     */
    public function findConversion(string $fromUnitCode, string $toUnitCode): ?ConversionRuleInterface;

    /**
     * Get all conversion rules where the given unit is the source
     *
     * @param string $fromUnitCode Source unit code
     * @return ConversionRuleInterface[] Array of conversion rules
     */
    public function getConversionsFrom(string $fromUnitCode): array;

    /**
     * Get all conversion rules for units within a dimension
     *
     * @param string $dimensionCode The dimension code
     * @return ConversionRuleInterface[] Array of conversion rules
     */
    public function getConversionsByDimension(string $dimensionCode): array;

    /**
     * Save a new unit definition
     *
     * @param UnitInterface $unit The unit to save
     * @return UnitInterface The saved unit with any generated IDs
     * @throws \Nexus\Uom\Exceptions\DuplicateUnitCodeException If code already exists
     */
    public function saveUnit(UnitInterface $unit): UnitInterface;

    /**
     * Save a new dimension definition
     *
     * @param DimensionInterface $dimension The dimension to save
     * @return DimensionInterface The saved dimension with any generated IDs
     * @throws \Nexus\Uom\Exceptions\DuplicateDimensionCodeException If code already exists
     */
    public function saveDimension(DimensionInterface $dimension): DimensionInterface;

    /**
     * Save a new conversion rule
     *
     * @param ConversionRuleInterface $rule The conversion rule to save
     * @return ConversionRuleInterface The saved conversion rule
     */
    public function saveConversion(ConversionRuleInterface $rule): ConversionRuleInterface;

    /**
     * Delete a unit by its code
     *
     * @param string $code The unit code to delete
     * @return void
     * @throws \Nexus\Uom\Exceptions\SystemUnitProtectedException If unit is system-protected
     */
    public function deleteUnit(string $code): void;

    /**
     * Delete a dimension by its code
     *
     * @param string $code The dimension code to delete
     * @return void
     */
    public function deleteDimension(string $code): void;

    /**
     * Delete a conversion rule
     *
     * @param string $fromUnitCode Source unit code
     * @param string $toUnitCode Target unit code
     * @return void
     */
    public function deleteConversion(string $fromUnitCode, string $toUnitCode): void;
}
```

**Requirements:** FR-UOM-A02, ARC-UOM-0027, FR-UOM-201, FR-UOM-203

**Implementation Example:**

```php
use Illuminate\Support\Facades\DB;
use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\ValueObjects\Unit;

class LaravelUomRepository implements UomRepositoryInterface
{
    public function findUnitByCode(string $code): ?UnitInterface
    {
        $row = DB::table('uom_units')
            ->where('code', $code)
            ->first();

        if ($row === null) {
            return null;
        }

        return new Unit(
            code: $row->code,
            name: $row->name,
            symbol: $row->symbol,
            dimension: $row->dimension_code,
            system: $row->system_code,
            isBaseUnit: (bool) $row->is_base_unit,
            isSystemUnit: (bool) $row->is_system_unit
        );
    }

    // Implement other methods...
}
```

---

### UnitInterface

Interface representing a unit of measurement.

```php
namespace Nexus\Uom\Contracts;

interface UnitInterface
{
    /**
     * Get the unique unit code
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get the human-readable name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the display symbol
     *
     * @return string
     */
    public function getSymbol(): string;

    /**
     * Get the dimension code this unit belongs to
     *
     * @return string
     */
    public function getDimension(): string;

    /**
     * Get the unit system code (metric, imperial, etc.)
     *
     * @return string|null Null if not part of a system
     */
    public function getSystem(): ?string;

    /**
     * Check if this is the base unit for its dimension
     *
     * @return bool
     */
    public function isBaseUnit(): bool;

    /**
     * Check if this is a system-defined (protected) unit
     *
     * @return bool
     */
    public function isSystemUnit(): bool;
}
```

**Requirements:** ARC-UOM-0027, BUS-UOM-105

---

### DimensionInterface

Interface representing a measurement dimension.

```php
namespace Nexus\Uom\Contracts;

interface DimensionInterface
{
    /**
     * Get the unique dimension code
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get the human-readable name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the base unit code for this dimension
     *
     * @return string
     */
    public function getBaseUnit(): string;

    /**
     * Get the optional description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Check if this dimension allows offset conversions
     *
     * Temperature dimensions typically allow offsets (e.g., Celsius to Fahrenheit)
     *
     * @return bool
     */
    public function allowsOffset(): bool;
}
```

**Requirements:** FR-UOM-201, FR-UOM-204, ARC-UOM-0027

---

### ConversionRuleInterface

Interface representing a conversion rule between two units.

```php
namespace Nexus\Uom\Contracts;

interface ConversionRuleInterface
{
    /**
     * Get the source unit code
     *
     * @return string
     */
    public function getFromUnit(): string;

    /**
     * Get the target unit code
     *
     * @return string
     */
    public function getToUnit(): string;

    /**
     * Get the multiplication ratio
     *
     * Formula: targetValue = (sourceValue * ratio) + offset
     *
     * @return float
     */
    public function getRatio(): float;

    /**
     * Get the addition offset
     *
     * Used for temperature conversions (e.g., Celsius to Fahrenheit)
     *
     * @return float
     */
    public function getOffset(): float;

    /**
     * Check if this conversion has an offset
     *
     * @return bool
     */
    public function hasOffset(): bool;

    /**
     * Check if the inverse conversion is also valid
     *
     * @return bool
     */
    public function isBidirectional(): bool;
}
```

**Requirements:** FR-UOM-102, FR-UOM-205, ARC-UOM-0027

---

### UnitSystemInterface

Interface representing a unit system (e.g., Metric, Imperial).

```php
namespace Nexus\Uom\Contracts;

interface UnitSystemInterface
{
    /**
     * Get the unique system code
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get the human-readable name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the optional description
     *
     * @return string|null
     */
    public function getDescription(): ?string;
}
```

**Requirements:** FR-UOM-203

---

## Value Objects

### Quantity

**Primary API:** Immutable value object representing a measurement with value and unit. This is the main entry point for working with measurements.

```php
namespace Nexus\Uom\ValueObjects;

final readonly class Quantity implements JsonSerializable
{
    public function __construct(
        public float $value,
        public string $unitCode
    ) {}

    /**
     * Get the numeric value
     */
    public function getValue(): float;

    /**
     * Get the unit code
     */
    public function getUnitCode(): string;

    /**
     * Convert this quantity to a different unit
     *
     * @throws \Nexus\Uom\Exceptions\IncompatibleUnitException
     * @throws \Nexus\Uom\Exceptions\ConversionPathNotFoundException
     */
    public function convertTo(string $toUnitCode, UomConversionEngine $engine): self;

    /**
     * Add another quantity to this one (with automatic conversion)
     *
     * @throws \Nexus\Uom\Exceptions\IncompatibleUnitException
     */
    public function add(self $other, UomConversionEngine $engine): self;

    /**
     * Subtract another quantity from this one (with automatic conversion)
     *
     * @throws \Nexus\Uom\Exceptions\IncompatibleUnitException
     */
    public function subtract(self $other, UomConversionEngine $engine): self;

    /**
     * Multiply this quantity by a scalar value
     */
    public function multiply(float $scalar): self;

    /**
     * Divide this quantity by a scalar value
     *
     * @throws \DivisionByZeroError If scalar is zero
     */
    public function divide(float $scalar): self;

    /**
     * Format the quantity for display with locale-specific formatting
     *
     * @param string $locale Locale code (e.g., 'en_US', 'de_DE')
     * @param int $decimals Number of decimal places (default 2)
     */
    public function format(string $locale = 'en_US', int $decimals = 2): string;

    /**
     * Compare if this quantity equals another (after conversion)
     *
     * @param float $epsilon Precision tolerance (default 0.0001)
     */
    public function equals(self $other, UomConversionEngine $engine, float $epsilon = 0.0001): bool;

    /**
     * Compare if this quantity is greater than another (after conversion)
     */
    public function greaterThan(self $other, UomConversionEngine $engine): bool;

    /**
     * Compare if this quantity is less than another (after conversion)
     */
    public function lessThan(self $other, UomConversionEngine $engine): bool;

    /**
     * Get the absolute value (positive)
     */
    public function abs(): self;

    /**
     * Negate the value (flip sign)
     */
    public function negate(): self;

    /**
     * Convert to array representation
     */
    public function toArray(): array;

    /**
     * JSON serialization
     */
    public function jsonSerialize(): array;

    /**
     * Create from array
     */
    public static function fromArray(array $data): self;
}
```

**Requirements:** FR-UOM-101, FR-UOM-104, FR-UOM-105, FR-UOM-303, BUS-UOM-101

**Complete Usage Examples:**

```php
use Nexus\Uom\ValueObjects\Quantity;
use Nexus\Uom\Services\UomConversionEngine;

// Create quantities
$weight1 = new Quantity(100.5, 'kg');
$weight2 = new Quantity(50.0, 'lb');

// Conversion
$weight2Kg = $weight2->convertTo('kg', $engine);
echo $weight2Kg->format('en_US', 2);
// Output: "22.68 kg"

// Arithmetic (automatic conversion)
$total = $weight1->add($weight2, $engine);
echo $total->format('en_US', 2);
// Output: "123.18 kg"  (100.5 + 22.68)

$difference = $weight1->subtract($weight2, $engine);
echo $difference->format('en_US', 2);
// Output: "77.82 kg"  (100.5 - 22.68)

// Scalar operations
$doubled = $weight1->multiply(2);
echo $doubled->format('en_US', 2);
// Output: "201.00 kg"

$half = $weight1->divide(2);
echo $half->format('en_US', 2);
// Output: "50.25 kg"

// Comparisons
if ($weight1->greaterThan($weight2, $engine)) {
    echo "Weight 1 is heavier";
}

if ($weight1->equals($weight2, $engine)) {
    echo "Weights are equal";
}

// Formatting (locale-aware)
echo $weight1->format('en_US', 2);    // "100.50 kg"
echo $weight1->format('de_DE', 2);    // "100,50 kg"
echo $weight1->format('en_US', 0);    // "101 kg"

// Serialization
$array = $weight1->toArray();
// ['value' => 100.5, 'unit_code' => 'kg']

$json = json_encode($weight1);
// {"value":100.5,"unit_code":"kg"}

$restored = Quantity::fromArray($array);
// Quantity(100.5, 'kg')

// Sign operations
$negative = new Quantity(-50, 'kg');
$positive = $negative->abs();
echo $positive->format('en_US', 2);
// Output: "50.00 kg"

$inverted = $weight1->negate();
echo $inverted->format('en_US', 2);
// Output: "-100.50 kg"
```

---

### Unit

Immutable value object implementing UnitInterface.

```php
namespace Nexus\Uom\ValueObjects;

final readonly class Unit implements UnitInterface
{
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
        public string $dimension,
        public ?string $system = null,
        public bool $isBaseUnit = false,
        public bool $isSystemUnit = true
    ) {}

    public function getCode(): string;
    public function getName(): string;
    public function getSymbol(): string;
    public function getDimension(): string;
    public function getSystem(): ?string;
    public function isBaseUnit(): bool;
    public function isSystemUnit(): bool;
}
```

**Example:**

```php
use Nexus\Uom\ValueObjects\Unit;

// Create a kilogram unit
$kg = new Unit(
    code: 'kg',
    name: 'Kilogram',
    symbol: 'kg',
    dimension: 'mass',
    system: 'metric',
    isBaseUnit: true,
    isSystemUnit: true
);

echo $kg->getCode();        // "kg"
echo $kg->getName();        // "Kilogram"
echo $kg->getSymbol();      // "kg"
echo $kg->getDimension();   // "mass"
echo $kg->getSystem();      // "metric"
var_dump($kg->isBaseUnit());     // bool(true)
var_dump($kg->isSystemUnit());   // bool(true)

// Create a custom unit
$customUnit = new Unit(
    code: 'barrel',
    name: 'Barrel',
    symbol: 'bbl',
    dimension: 'volume',
    system: null,              // Not part of a standard system
    isBaseUnit: false,
    isSystemUnit: false        // User-defined, can be deleted
);
```

---

### Dimension

Immutable value object implementing DimensionInterface.

```php
namespace Nexus\Uom\ValueObjects;

final readonly class Dimension implements DimensionInterface
{
    public function __construct(
        public string $code,
        public string $name,
        public string $baseUnit,
        public bool $allowsOffset = false,
        public ?string $description = null
    ) {}

    public function getCode(): string;
    public function getName(): string;
    public function getBaseUnit(): string;
    public function getDescription(): ?string;
    public function allowsOffset(): bool;
}
```

**Example:**

```php
use Nexus\Uom\ValueObjects\Dimension;

// Mass dimension (no offset conversions)
$mass = new Dimension(
    code: 'mass',
    name: 'Mass',
    baseUnit: 'kg',
    allowsOffset: false,
    description: 'Weight and mass measurements'
);

echo $mass->getCode();         // "mass"
echo $mass->getName();         // "Mass"
echo $mass->getBaseUnit();     // "kg"
var_dump($mass->allowsOffset()); // bool(false)

// Temperature dimension (allows offset conversions)
$temperature = new Dimension(
    code: 'temperature',
    name: 'Temperature',
    baseUnit: 'kelvin',
    allowsOffset: true,  // Celsius ↔ Fahrenheit needs offset
    description: 'Temperature measurements'
);

var_dump($temperature->allowsOffset()); // bool(true)
```

---

### ConversionRule

Immutable value object implementing ConversionRuleInterface.

```php
namespace Nexus\Uom\ValueObjects;

final readonly class ConversionRule implements ConversionRuleInterface
{
    public function __construct(
        public string $fromUnit,
        public string $toUnit,
        public float $ratio,
        public float $offset = 0.0,
        public bool $isBidirectional = true
    ) {}

    public function getFromUnit(): string;
    public function getToUnit(): string;
    public function getRatio(): float;
    public function getOffset(): float;
    public function hasOffset(): bool;
    public function isBidirectional(): bool;

    /**
     * Get the inverse conversion rule
     *
     * @throws \LogicException If conversion is not bidirectional
     */
    public function inverse(): self;
}
```

**Example:**

```php
use Nexus\Uom\ValueObjects\ConversionRule;

// Simple ratio conversion (kg to lb)
$kgToLb = new ConversionRule(
    fromUnit: 'kg',
    toUnit: 'lb',
    ratio: 2.20462,
    offset: 0.0,
    isBidirectional: true
);

// Usage: targetValue = (sourceValue * ratio) + offset
$pounds = (100 * $kgToLb->getRatio()) + $kgToLb->getOffset();
// Result: 220.462 lb

// Get inverse conversion (lb to kg)
$lbToKg = $kgToLb->inverse();
echo $lbToKg->getRatio();  // 0.453592 (1 / 2.20462)

// Temperature conversion with offset (Celsius to Fahrenheit)
$celsiusToFahrenheit = new ConversionRule(
    fromUnit: 'celsius',
    toUnit: 'fahrenheit',
    ratio: 1.8,      // 9/5
    offset: 32.0,    // Add 32 after multiplication
    isBidirectional: true
);

// Usage: °F = (°C × 1.8) + 32
$fahrenheit = (25 * $celsiusToFahrenheit->getRatio()) + $celsiusToFahrenheit->getOffset();
// Result: 77°F  (25 × 1.8 + 32)

// Inverse: °C = (°F - 32) / 1.8
$fahrenheitToCelsius = $celsiusToFahrenheit->inverse();
echo $fahrenheitToCelsius->getRatio();   // 0.5556 (1 / 1.8)
echo $fahrenheitToCelsius->getOffset();  // -17.778 (-32 / 1.8)
```

---

### UnitSystem

Immutable value object implementing UnitSystemInterface.

```php
namespace Nexus\Uom\ValueObjects;

final readonly class UnitSystem implements UnitSystemInterface
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null
    ) {}

    public function getCode(): string;
    public function getName(): string;
    public function getDescription(): ?string;
}
```

**Example:**

```php
use Nexus\Uom\ValueObjects\UnitSystem;

$metric = new UnitSystem(
    code: 'metric',
    name: 'Metric System',
    description: 'International System of Units (SI)'
);

$imperial = new UnitSystem(
    code: 'imperial',
    name: 'Imperial System',
    description: 'British Imperial units'
);

echo $metric->getCode();  // "metric"
echo $metric->getName();  // "Metric System"
```

---

## Services

### UomConversionEngine

Core conversion engine for unit of measurement conversions. Implements graph-based pathfinding for multi-hop conversions.

```php
namespace Nexus\Uom\Services;

class UomConversionEngine
{
    public function __construct(
        private readonly UomRepositoryInterface $repository,
        private readonly UomValidationService $validator
    ) {}

    /**
     * Convert a value from one unit to another
     *
     * This is the main conversion method that handles:
     * - Direct conversions (single lookup)
     * - Multi-hop conversions via base unit (graph pathfinding)
     * - Conversion caching for performance
     *
     * @param float $value The value to convert
     * @param string $fromUnitCode Source unit code
     * @param string $toUnitCode Target unit code
     * @return float The converted value
     * @throws UnitNotFoundException If either unit doesn't exist
     * @throws IncompatibleUnitException If units are from different dimensions
     * @throws ConversionPathNotFoundException If no conversion path exists
     * @throws InvalidConversionRatioException If conversion ratio is invalid
     */
    public function convert(float $value, string $fromUnitCode, string $toUnitCode): float;

    /**
     * Clear the conversion cache
     *
     * Useful when conversion rules are modified at runtime
     */
    public function clearCache(): void;
}
```

**Requirements:** FR-UOM-102, FR-UOM-202, FR-UOM-205, FR-UOM-301, FR-UOM-302, BUS-UOM-201, BUS-UOM-202, PER-UOM-101, PER-UOM-102, REL-UOM-101

**Usage Examples:**

```php
use Nexus\Uom\Services\UomConversionEngine;

$engine = new UomConversionEngine($repository, $validator);

// Direct conversion (single lookup)
$pounds = $engine->convert(100, 'kg', 'lb');
// Result: 220.462 (looks up kg→lb conversion rule)

// Multi-hop conversion (graph pathfinding)
$grams = $engine->convert(1, 'lb', 'g');
// Paths: lb → kg → g
// Result: 453.592 (0.453592 × 1000)

// Same unit (no conversion)
$same = $engine->convert(100, 'kg', 'kg');
// Result: 100 (no lookup, immediate return)

// Temperature conversion with offset
$fahrenheit = $engine->convert(25, 'celsius', 'fahrenheit');
// Formula: (25 × 1.8) + 32 = 77°F
// Result: 77

// Clear cache after adding new conversion rules
$repository->saveConversion(new ConversionRule('kg', 'ton', 0.001));
$engine->clearCache();  // Force recomputation of paths
```

---

### UomManager

High-level manager service providing convenient methods for creating and managing UoM entities.

```php
namespace Nexus\Uom\Services;

class UomManager
{
    public function __construct(
        private readonly UomRepositoryInterface $repository,
        private readonly UomConversionEngine $conversionEngine,
        private readonly UomValidationService $validationService
    ) {}

    /**
     * Get the conversion engine
     */
    public function getConversionEngine(): UomConversionEngine;

    /**
     * Get the validation service
     */
    public function getValidationService(): UomValidationService;

    /**
     * Create and save a new dimension
     *
     * @throws \Nexus\Uom\Exceptions\DuplicateDimensionCodeException
     */
    public function createDimension(
        string $code,
        string $name,
        string $baseUnit,
        bool $allowsOffset = false,
        ?string $description = null
    ): Dimension;

    /**
     * Create and save a new unit
     *
     * @throws \Nexus\Uom\Exceptions\DuplicateUnitCodeException
     */
    public function createUnit(
        string $code,
        string $name,
        string $symbol,
        string $dimension,
        ?string $system = null,
        bool $isBaseUnit = false,
        bool $isSystemUnit = false
    ): Unit;

    /**
     * Create and save a new conversion rule
     *
     * @throws \Nexus\Uom\Exceptions\InvalidConversionRatioException
     * @throws \Nexus\Uom\Exceptions\IncompatibleUnitException
     * @throws \Nexus\Uom\Exceptions\CircularConversionException
     */
    public function createConversion(
        string $fromUnit,
        string $toUnit,
        float $ratio,
        float $offset = 0.0,
        bool $isBidirectional = true
    ): ConversionRule;

    /**
     * Delete a unit
     *
     * @throws \Nexus\Uom\Exceptions\SystemUnitProtectedException
     */
    public function deleteUnit(string $code): void;

    /**
     * Delete a dimension
     */
    public function deleteDimension(string $code): void;

    /**
     * Delete a conversion rule
     */
    public function deleteConversion(string $fromUnit, string $toUnit): void;
}
```

**Requirements:** ARC-UOM-0028, FR-UOM-A02

**Usage Examples:**

```php
use Nexus\Uom\Services\UomManager;

$manager = new UomManager($repository, $engine, $validator);

// Create a dimension
$volumeDim = $manager->createDimension(
    code: 'volume',
    name: 'Volume',
    baseUnit: 'liter',
    allowsOffset: false,
    description: 'Volumetric measurements'
);

// Create units
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

// Create conversion
$conversion = $manager->createConversion(
    fromUnit: 'liter',
    toUnit: 'milliliter',
    ratio: 1000.0
);

// Use the engine directly
$engine = $manager->getConversionEngine();
$ml = $engine->convert(2.5, 'liter', 'milliliter');
// Result: 2500

// Delete custom units (system units are protected)
try {
    $manager->deleteUnit('kg');  // System unit
} catch (SystemUnitProtectedException $e) {
    echo "Cannot delete system unit";
}

$manager->deleteUnit('barrel');  // Custom unit, OK
```

---

### UomValidationService

Validation service for UoM operations.

```php
namespace Nexus\Uom\Services;

class UomValidationService
{
    public function __construct(
        private readonly UomRepositoryInterface $repository
    ) {}

    /**
     * Validate that two units are from the same dimension
     *
     * @throws IncompatibleUnitException If units are from different dimensions
     */
    public function validateSameDimension(UnitInterface $unit1, UnitInterface $unit2): void;

    /**
     * Validate that a conversion ratio is positive and non-zero
     *
     * @throws InvalidConversionRatioException If ratio is invalid
     */
    public function validateRatio(float $ratio): void;

    /**
     * Validate that a unit code is unique
     *
     * @throws DuplicateUnitCodeException If code already exists
     */
    public function validateUniqueUnitCode(string $code): void;

    /**
     * Validate that a dimension code is unique
     *
     * @throws DuplicateDimensionCodeException If code already exists
     */
    public function validateUniqueDimensionCode(string $code): void;

    /**
     * Check if two quantities can be converted to each other
     */
    public function areConvertible(Quantity $qty1, Quantity $qty2): bool;

    /**
     * Validate that creating this conversion won't create a circular reference
     *
     * @throws CircularConversionException If circular reference detected
     */
    public function validateNoCircularConversion(string $fromUnit, string $toUnit): void;

    /**
     * Validate that a unit is not system-protected before deletion
     *
     * @throws SystemUnitProtectedException If unit is protected
     */
    public function validateNotSystemUnit(UnitInterface $unit): void;
}
```

**Usage Examples:**

```php
use Nexus\Uom\Services\UomValidationService;
use Nexus\Uom\ValueObjects\Quantity;

$validator = new UomValidationService($repository);

// Check if quantities can be converted
$weight = new Quantity(100, 'kg');
$distance = new Quantity(50, 'm');

if ($validator->areConvertible($weight, $distance)) {
    $sum = $weight->add($distance, $engine);
} else {
    echo "Cannot convert between mass and length";
}

// Validate ratio
try {
    $validator->validateRatio(0.0);  // Invalid!
} catch (InvalidConversionRatioException $e) {
    echo "Ratio must be positive and non-zero";
}

// Validate no circular conversions
try {
    $validator->validateNoCircularConversion('a', 'b');
    // If path a → ... → b → ... → a exists, throws exception
} catch (CircularConversionException $e) {
    echo "This would create a circular conversion";
}

// Validate unique code
try {
    $validator->validateUniqueUnitCode('kg');  // Already exists!
} catch (DuplicateUnitCodeException $e) {
    echo "Unit code 'kg' already exists";
}
```

---

## Exceptions

All exceptions extend base `UomException` for easy catching.

### UnitNotFoundException

Thrown when a requested unit code doesn't exist in the repository.

```php
namespace Nexus\Uom\Exceptions;

class UnitNotFoundException extends UomException
{
    public static function forCode(string $code): self
    {
        return new self("Unit with code '{$code}' not found");
    }
}
```

**Example:**

```php
use Nexus\Uom\Exceptions\UnitNotFoundException;

try {
    $unit = $repository->findUnitByCode('invalid_code');
    if ($unit === null) {
        throw UnitNotFoundException::forCode('invalid_code');
    }
} catch (UnitNotFoundException $e) {
    echo $e->getMessage();
    // Output: "Unit with code 'invalid_code' not found"
}
```

---

### DimensionNotFoundException

Thrown when a requested dimension code doesn't exist.

```php
namespace Nexus\Uom\Exceptions;

class DimensionNotFoundException extends UomException
{
    public static function forCode(string $code): self
    {
        return new self("Dimension with code '{$code}' not found");
    }
}
```

---

### IncompatibleUnitException

Thrown when attempting operations on units from different dimensions.

```php
namespace Nexus\Uom\Exceptions;

class IncompatibleUnitException extends UomException
{
    public static function forUnits(string $unit1, string $unit2): self
    {
        return new self("Units '{$unit1}' and '{$unit2}' are not compatible for conversion");
    }
}
```

**Example:**

```php
use Nexus\Uom\Exceptions\IncompatibleUnitException;

try {
    $weight = new Quantity(100, 'kg');
    $distance = new Quantity(50, 'm');
    
    $invalid = $weight->add($distance, $engine);
} catch (IncompatibleUnitException $e) {
    echo $e->getMessage();
    // Output: "Units 'kg' and 'm' are not compatible for conversion"
}
```

---

### ConversionPathNotFoundException

Thrown when no conversion path exists between two units.

```php
namespace Nexus\Uom\Exceptions;

class ConversionPathNotFoundException extends UomException
{
    public static function forUnits(string $fromUnit, string $toUnit): self
    {
        return new self("No conversion path found from '{$fromUnit}' to '{$toUnit}'");
    }
}
```

**Example:**

```php
// If you have kg and lb defined, but no conversion rule between them
try {
    $pounds = $engine->convert(100, 'kg', 'lb');
} catch (ConversionPathNotFoundException $e) {
    echo $e->getMessage();
    // Output: "No conversion path found from 'kg' to 'lb'"
    
    // Solution: Add conversion rule
    $manager->createConversion('kg', 'lb', 2.20462);
}
```

---

### CircularConversionException

Thrown when a circular conversion path is detected.

```php
namespace Nexus\Uom\Exceptions;

class CircularConversionException extends UomException
{
    public static function detected(string $path): self
    {
        return new self("Circular conversion path detected: {$path}");
    }
}
```

**Example:**

```php
// Creating conversions: a → b → c → a (circular!)
try {
    $manager->createConversion('a', 'b', 2.0);
    $manager->createConversion('b', 'c', 3.0);
    $manager->createConversion('c', 'a', 0.5);  // Creates circle!
} catch (CircularConversionException $e) {
    echo $e->getMessage();
    // Output: "Circular conversion path detected: a → b → c → a"
}
```

---

### InvalidConversionRatioException

Thrown when a conversion ratio is invalid (zero or negative).

```php
namespace Nexus\Uom\Exceptions;

class InvalidConversionRatioException extends UomException
{
    public static function forRatio(float $ratio): self
    {
        return new self("Invalid conversion ratio: {$ratio}. Ratio must be positive and non-zero.");
    }
}
```

**Example:**

```php
try {
    $manager->createConversion('kg', 'lb', 0.0);  // Invalid!
} catch (InvalidConversionRatioException $e) {
    echo $e->getMessage();
    // Output: "Invalid conversion ratio: 0. Ratio must be positive and non-zero."
}
```

---

### InvalidOffsetConversionException

Thrown when an offset conversion is attempted on a dimension that doesn't allow offsets.

```php
namespace Nexus\Uom\Exceptions;

class InvalidOffsetConversionException extends UomException
{
    public static function forDimension(string $dimension): self
    {
        return new self("Dimension '{$dimension}' does not allow offset conversions");
    }
}
```

**Example:**

```php
// Mass dimension doesn't allow offsets
try {
    $manager->createConversion(
        fromUnit: 'kg',
        toUnit: 'lb',
        ratio: 2.20462,
        offset: 10.0  // Invalid for mass!
    );
} catch (InvalidOffsetConversionException $e) {
    echo $e->getMessage();
    // Output: "Dimension 'mass' does not allow offset conversions"
}
```

---

### DuplicateUnitCodeException

Thrown when attempting to create a unit with a code that already exists.

```php
namespace Nexus\Uom\Exceptions;

class DuplicateUnitCodeException extends UomException
{
    public static function forCode(string $code): self
    {
        return new self("Unit with code '{$code}' already exists");
    }
}
```

---

### DuplicateDimensionCodeException

Thrown when attempting to create a dimension with a code that already exists.

```php
namespace Nexus\Uom\Exceptions;

class DuplicateDimensionCodeException extends UomException
{
    public static function forCode(string $code): self
    {
        return new self("Dimension with code '{$code}' already exists");
    }
}
```

---

### SystemUnitProtectedException

Thrown when attempting to delete a system-defined (protected) unit.

```php
namespace Nexus\Uom\Exceptions;

class SystemUnitProtectedException extends UomException
{
    public static function forCode(string $code): self
    {
        return new self("Cannot delete system unit '{$code}'. System units are protected.");
    }
}
```

**Example:**

```php
try {
    $manager->deleteUnit('kg');  // kg is a system unit
} catch (SystemUnitProtectedException $e) {
    echo $e->getMessage();
    // Output: "Cannot delete system unit 'kg'. System units are protected."
}

// Custom units can be deleted
$manager->deleteUnit('barrel');  // OK if not system unit
```

---

## Complete Workflow Example

Putting it all together:

```php
use Nexus\Uom\Services\{UomManager, UomConversionEngine, UomValidationService};
use Nexus\Uom\ValueObjects\Quantity;

// 1. Setup
$repository = new LaravelUomRepository();
$validator = new UomValidationService($repository);
$engine = new UomConversionEngine($repository, $validator);
$manager = new UomManager($repository, $engine, $validator);

// 2. Create dimension and units
$manager->createDimension('mass', 'Mass', 'kg');
$manager->createUnit('kg', 'Kilogram', 'kg', 'mass', 'metric', true);
$manager->createUnit('g', 'Gram', 'g', 'mass', 'metric');
$manager->createUnit('lb', 'Pound', 'lb', 'mass', 'imperial');

// 3. Create conversions
$manager->createConversion('kg', 'g', 1000.0);
$manager->createConversion('kg', 'lb', 2.20462);

// 4. Work with quantities
$weight1 = new Quantity(100, 'kg');
$weight2 = new Quantity(50, 'lb');

// 5. Convert
$weight2Kg = $weight2->convertTo('kg', $engine);
echo "50 lb = " . $weight2Kg->format('en_US', 2) . "\n";
// Output: "50 lb = 22.68 kg"

// 6. Arithmetic
$total = $weight1->add($weight2, $engine);
echo "Total: " . $total->format('en_US', 2) . "\n";
// Output: "Total: 122.68 kg"

// 7. Validate before operations
if ($validator->areConvertible($weight1, $weight2)) {
    echo "Units are compatible\n";
}

// 8. Handle errors gracefully
try {
    $invalid = $weight1->convertTo('meter', $engine);
} catch (\Nexus\Uom\Exceptions\UomException $e) {
    echo "Conversion error: " . $e->getMessage() . "\n";
}
```

---

## Performance Characteristics

- **Direct conversions:** < 1ms (single database/cache lookup)
- **Multi-hop conversions (first time):** 5-15ms (graph pathfinding + caching)
- **Multi-hop conversions (cached):** < 2ms (cached ratio lookup)
- **Arithmetic operations:** < 1ms (in-memory calculation)
- **Memory usage:** ~1KB per Quantity instance

---

## Best Practices

1. **Always inject interfaces, never concrete classes**
2. **Use Quantity VO as primary API** - Don't work with raw floats and unit codes separately
3. **Validate compatibility before operations** - Use `areConvertible()` to check
4. **Pre-warm cache for common conversions** - During app bootstrap
5. **Use batch operations** - Array operations are more efficient than loops
6. **Handle exceptions gracefully** - Wrap conversions in try-catch for user-facing operations

---

**Last Updated:** November 28, 2024  
**Package Version:** 1.0.0-dev  
**Minimum PHP:** 8.3+
