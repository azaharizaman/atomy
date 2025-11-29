<?php

declare(strict_types=1);

/**
 * Basic Usage Examples for Nexus UoM Package
 *
 * This file demonstrates fundamental UoM operations including:
 * - Creating quantities
 * - Unit conversions
 * - Arithmetic operations
 * - Dimension setup
 * - Basic conversion rules
 * - Exception handling
 *
 * Run this file directly: php basic-usage.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Nexus\Uom\Contracts\{
    UomRepositoryInterface,
    UnitInterface,
    DimensionInterface,
    ConversionRuleInterface
};
use Nexus\Uom\ValueObjects\{Quantity, Unit, Dimension, ConversionRule};
use Nexus\Uom\Services\{UomConversionEngine, UomValidationService, UomManager};
use Nexus\Uom\Exceptions\{
    UnitNotFoundException,
    IncompatibleUnitException,
    ConversionPathNotFoundException,
    DuplicateUnitCodeException
};

// ============================================================================
// SETUP: In-Memory Repository for Examples
// ============================================================================

class InMemoryUomRepository implements UomRepositoryInterface
{
    private array $units = [];
    private array $dimensions = [];
    private array $conversions = [];

    public function findUnitByCode(string $code): ?UnitInterface
    {
        return $this->units[$code] ?? null;
    }

    public function findDimensionByCode(string $code): ?DimensionInterface
    {
        return $this->dimensions[$code] ?? null;
    }

    public function getUnitsByDimension(string $dimensionCode): array
    {
        return array_filter(
            $this->units,
            fn($unit) => $unit->getDimension() === $dimensionCode
        );
    }

    public function getUnitsBySystem(string $systemCode): array
    {
        return array_filter(
            $this->units,
            fn($unit) => $unit->getSystem() === $systemCode
        );
    }

    public function findConversion(string $fromUnitCode, string $toUnitCode): ?ConversionRuleInterface
    {
        return $this->conversions["{$fromUnitCode}:{$toUnitCode}"] ?? null;
    }

    public function getConversionsFrom(string $fromUnitCode): array
    {
        return array_filter(
            $this->conversions,
            fn($rule) => $rule->getFromUnit() === $fromUnitCode
        );
    }

    public function getConversionsByDimension(string $dimensionCode): array
    {
        $result = [];
        foreach ($this->conversions as $rule) {
            $fromUnit = $this->findUnitByCode($rule->getFromUnit());
            if ($fromUnit && $fromUnit->getDimension() === $dimensionCode) {
                $result[] = $rule;
            }
        }
        return $result;
    }

    public function saveUnit(UnitInterface $unit): UnitInterface
    {
        if (isset($this->units[$unit->getCode()])) {
            throw DuplicateUnitCodeException::forCode($unit->getCode());
        }
        $this->units[$unit->getCode()] = $unit;
        return $unit;
    }

    public function saveDimension(DimensionInterface $dimension): DimensionInterface
    {
        $this->dimensions[$dimension->getCode()] = $dimension;
        return $dimension;
    }

    public function saveConversion(ConversionRuleInterface $rule): ConversionRuleInterface
    {
        $this->conversions["{$rule->getFromUnit()}:{$rule->getToUnit()}"] = $rule;
        return $rule;
    }

    public function deleteUnit(string $code): void
    {
        unset($this->units[$code]);
    }

    public function deleteDimension(string $code): void
    {
        unset($this->dimensions[$code]);
    }

    public function deleteConversion(string $fromUnitCode, string $toUnitCode): void
    {
        unset($this->conversions["{$fromUnitCode}:{$toUnitCode}"]);
    }
}

// ============================================================================
// Initialize Services
// ============================================================================

$repository = new InMemoryUomRepository();
$validator = new UomValidationService($repository);
$engine = new UomConversionEngine($repository, $validator);
$manager = new UomManager($repository, $engine, $validator);

echo "=================================================================\n";
echo "Nexus UoM Package - Basic Usage Examples\n";
echo "=================================================================\n\n";

// ============================================================================
// EXAMPLE 1: Creating Dimensions and Units
// ============================================================================

echo "EXAMPLE 1: Creating Dimensions and Units\n";
echo "-----------------------------------------------------------------\n";

// Create Mass dimension
$massDimension = $manager->createDimension(
    code: 'mass',
    name: 'Mass',
    baseUnit: 'kg',
    allowsOffset: false,
    description: 'Weight and mass measurements'
);

echo "✓ Created dimension: {$massDimension->getName()} (base unit: {$massDimension->getBaseUnit()})\n";

// Create units in the Mass dimension
$kg = $manager->createUnit('kg', 'Kilogram', 'kg', 'mass', 'metric', true, true);
$g = $manager->createUnit('g', 'Gram', 'g', 'mass', 'metric', false, true);
$lb = $manager->createUnit('lb', 'Pound', 'lb', 'mass', 'imperial', false, true);
$oz = $manager->createUnit('oz', 'Ounce', 'oz', 'mass', 'imperial', false, true);

echo "✓ Created units: kg, g, lb, oz\n";

// Create conversion rules
$manager->createConversion('kg', 'g', 1000.0);          // 1 kg = 1000 g
$manager->createConversion('kg', 'lb', 2.20462);        // 1 kg = 2.20462 lb
$manager->createConversion('lb', 'oz', 16.0);           // 1 lb = 16 oz

echo "✓ Created conversion rules: kg↔g, kg↔lb, lb↔oz\n\n";

// ============================================================================
// EXAMPLE 2: Creating Quantities
// ============================================================================

echo "EXAMPLE 2: Creating Quantities\n";
echo "-----------------------------------------------------------------\n";

$weight1 = new Quantity(100.0, 'kg');
$weight2 = new Quantity(50.5, 'lb');
$weight3 = new Quantity(500, 'g');

echo "Created quantities:\n";
echo "  • weight1: {$weight1->format('en_US', 2)}\n";
echo "  • weight2: {$weight2->format('en_US', 2)}\n";
echo "  • weight3: {$weight3->format('en_US', 2)}\n\n";

// ============================================================================
// EXAMPLE 3: Direct Unit Conversions
// ============================================================================

echo "EXAMPLE 3: Direct Unit Conversions\n";
echo "-----------------------------------------------------------------\n";

// Convert kg to lb (direct conversion)
$weight1InLb = $weight1->convertTo('lb', $engine);
echo "100 kg → lb: {$weight1InLb->format('en_US', 2)}\n";

// Convert lb to kg (uses inverse)
$weight2InKg = $weight2->convertTo('kg', $engine);
echo "50.5 lb → kg: {$weight2InKg->format('en_US', 2)}\n";

// Convert kg to g (direct conversion)
$weight1InG = $weight1->convertTo('g', $engine);
echo "100 kg → g: {$weight1InG->format('en_US', 0)}\n";

// Convert g to kg (uses inverse)
$weight3InKg = $weight3->convertTo('kg', $engine);
echo "500 g → kg: {$weight3InKg->format('en_US', 3)}\n\n";

// ============================================================================
// EXAMPLE 4: Multi-Hop Conversions
// ============================================================================

echo "EXAMPLE 4: Multi-Hop Conversions (Graph Pathfinding)\n";
echo "-----------------------------------------------------------------\n";

// Convert oz to g (requires path: oz → lb → kg → g)
$ouncesQty = new Quantity(16, 'oz');
$ouncesInGrams = $ouncesQty->convertTo('g', $engine);

echo "Multi-hop conversion: 16 oz → g\n";
echo "  Path: oz → lb → kg → g\n";
echo "  Result: {$ouncesInGrams->format('en_US', 2)}\n";
echo "  Calculation: 16 oz ÷ 16 = 1 lb × 0.453592 = 0.453592 kg × 1000 = 453.59 g\n\n";

// ============================================================================
// EXAMPLE 5: Arithmetic Operations
// ============================================================================

echo "EXAMPLE 5: Arithmetic Operations\n";
echo "-----------------------------------------------------------------\n";

// Addition (with automatic conversion)
$sum = $weight1->add($weight2, $engine);
echo "Addition: 100 kg + 50.5 lb = {$sum->format('en_US', 2)}\n";
echo "  (50.5 lb ≈ 22.91 kg, so 100 + 22.91 = 122.91 kg)\n";

// Subtraction (with automatic conversion)
$difference = $weight1->subtract($weight2, $engine);
echo "Subtraction: 100 kg - 50.5 lb = {$difference->format('en_US', 2)}\n";

// Multiplication by scalar
$doubled = $weight1->multiply(2);
echo "Multiplication: 100 kg × 2 = {$doubled->format('en_US', 2)}\n";

// Division by scalar
$half = $weight1->divide(2);
echo "Division: 100 kg ÷ 2 = {$half->format('en_US', 2)}\n\n";

// ============================================================================
// EXAMPLE 6: Comparisons
// ============================================================================

echo "EXAMPLE 6: Quantity Comparisons\n";
echo "-----------------------------------------------------------------\n";

if ($weight1->greaterThan($weight2, $engine)) {
    echo "✓ 100 kg is greater than 50.5 lb\n";
}

if ($weight2->lessThan($weight1, $engine)) {
    echo "✓ 50.5 lb is less than 100 kg\n";
}

$equalWeight = new Quantity(100, 'kg');
if ($weight1->equals($equalWeight, $engine)) {
    echo "✓ 100 kg equals 100 kg\n";
}

// Comparison with conversion
$weight1InLb = $weight1->convertTo('lb', $engine);
if ($weight1InLb->greaterThan($weight2, $engine)) {
    echo "✓ 100 kg (220.46 lb) is greater than 50.5 lb\n";
}

echo "\n";

// ============================================================================
// EXAMPLE 7: Locale-Specific Formatting
// ============================================================================

echo "EXAMPLE 7: Locale-Specific Formatting\n";
echo "-----------------------------------------------------------------\n";

$qty = new Quantity(1234.56, 'kg');

echo "Same quantity in different locales:\n";
echo "  • en_US: {$qty->format('en_US', 2)}\n";  // 1,234.56 kg
echo "  • de_DE: {$qty->format('de_DE', 2)}\n";  // 1.234,56 kg
echo "  • en_US (0 decimals): {$qty->format('en_US', 0)}\n";  // 1,235 kg
echo "  • en_US (4 decimals): {$qty->format('en_US', 4)}\n";  // 1,234.5600 kg

echo "\n";

// ============================================================================
// EXAMPLE 8: Serialization
// ============================================================================

echo "EXAMPLE 8: Serialization and Deserialization\n";
echo "-----------------------------------------------------------------\n";

$originalQty = new Quantity(75.5, 'kg');

// Convert to array
$array = $originalQty->toArray();
echo "To array: " . json_encode($array) . "\n";

// Convert to JSON
$json = json_encode($originalQty);
echo "To JSON: {$json}\n";

// Restore from array
$restoredQty = Quantity::fromArray($array);
echo "Restored from array: {$restoredQty->format('en_US', 2)}\n\n";

// ============================================================================
// EXAMPLE 9: Temperature Conversions (with Offset)
// ============================================================================

echo "EXAMPLE 9: Temperature Conversions (with Offset)\n";
echo "-----------------------------------------------------------------\n";

// Create Temperature dimension (allows offset)
$tempDimension = $manager->createDimension(
    code: 'temperature',
    name: 'Temperature',
    baseUnit: 'kelvin',
    allowsOffset: true,  // Important for temperature!
    description: 'Temperature measurements'
);

$manager->createUnit('celsius', 'Celsius', '°C', 'temperature', 'metric', false, true);
$manager->createUnit('fahrenheit', 'Fahrenheit', '°F', 'temperature', 'imperial', false, true);

// Celsius to Fahrenheit: °F = (°C × 1.8) + 32
$manager->createConversion('celsius', 'fahrenheit', 1.8, 32.0);

$tempC = new Quantity(25, 'celsius');
$tempF = $tempC->convertTo('fahrenheit', $engine);

echo "Temperature conversion:\n";
echo "  • {$tempC->format('en_US', 1)} = {$tempF->format('en_US', 1)}\n";
echo "  • Formula: (25 × 1.8) + 32 = 77°F\n";

// Reverse conversion
$temp100F = new Quantity(100, 'fahrenheit');
$temp100C = $temp100F->convertTo('celsius', $engine);
echo "  • {$temp100F->format('en_US', 1)} = {$temp100C->format('en_US', 1)}\n\n";

// ============================================================================
// EXAMPLE 10: Exception Handling
// ============================================================================

echo "EXAMPLE 10: Exception Handling\n";
echo "-----------------------------------------------------------------\n";

// 1. Unit not found
try {
    $invalidQty = new Quantity(100, 'invalid_unit');
    $converted = $invalidQty->convertTo('kg', $engine);
} catch (UnitNotFoundException $e) {
    echo "✓ Caught UnitNotFoundException: {$e->getMessage()}\n";
}

// 2. Incompatible units
try {
    // Create Length dimension for incompatibility test
    $manager->createDimension('length', 'Length', 'm', false);
    $manager->createUnit('m', 'Meter', 'm', 'length', 'metric', true, true);
    
    $mass = new Quantity(100, 'kg');
    $length = new Quantity(50, 'm');
    
    $invalid = $mass->add($length, $engine);
} catch (IncompatibleUnitException $e) {
    echo "✓ Caught IncompatibleUnitException: Cannot add mass and length\n";
}

// 3. Division by zero
try {
    $qty = new Quantity(100, 'kg');
    $invalid = $qty->divide(0);
} catch (\DivisionByZeroError $e) {
    echo "✓ Caught DivisionByZeroError: {$e->getMessage()}\n";
}

// 4. Duplicate unit code
try {
    $manager->createUnit('kg', 'Duplicate Kilogram', 'kg', 'mass', 'metric');
} catch (DuplicateUnitCodeException $e) {
    echo "✓ Caught DuplicateUnitCodeException: Unit 'kg' already exists\n";
}

echo "\n";

// ============================================================================
// EXAMPLE 11: Packaging Hierarchy
// ============================================================================

echo "EXAMPLE 11: Packaging Hierarchy\n";
echo "-----------------------------------------------------------------\n";

// Create Quantity dimension
$manager->createDimension('quantity', 'Quantity', 'each', false, 'Discrete quantities');

$manager->createUnit('each', 'Each', 'ea', 'quantity', null, true, true);
$manager->createUnit('dozen', 'Dozen', 'doz', 'quantity', null, false, true);
$manager->createUnit('case', 'Case', 'cs', 'quantity', null, false, false);
$manager->createUnit('pallet', 'Pallet', 'plt', 'quantity', null, false, false);

// Define packaging conversions
$manager->createConversion('dozen', 'each', 12.0);      // 1 dozen = 12 each
$manager->createConversion('case', 'each', 24.0);       // 1 case = 24 each
$manager->createConversion('pallet', 'case', 60.0);     // 1 pallet = 60 cases

// Convert pallet to eaches
$palletQty = new Quantity(2, 'pallet');
$casesQty = $palletQty->convertTo('case', $engine);
$eachesQty = $palletQty->convertTo('each', $engine);

echo "Packaging hierarchy conversions:\n";
echo "  • {$palletQty->format('en_US', 0)} = {$casesQty->format('en_US', 0)}\n";
echo "  • {$palletQty->format('en_US', 0)} = {$eachesQty->format('en_US', 0)}\n";
echo "  • Path: pallet → case → each\n";
echo "  • Calculation: 2 pallets × 60 = 120 cases × 24 = 2,880 each\n\n";

// ============================================================================
// EXAMPLE 12: Validation Before Operations
// ============================================================================

echo "EXAMPLE 12: Validation Before Operations\n";
echo "-----------------------------------------------------------------\n";

$massQty = new Quantity(100, 'kg');
$lengthQty = new Quantity(50, 'm');

// Check if convertible
if ($validator->areConvertible($massQty, $lengthQty)) {
    echo "Units are convertible\n";
} else {
    echo "✓ Units are NOT convertible (different dimensions)\n";
}

// Validate same dimension
$kg1 = new Quantity(100, 'kg');
$lb1 = new Quantity(50, 'lb');

if ($validator->areConvertible($kg1, $lb1)) {
    echo "✓ kg and lb are convertible (same dimension: mass)\n";
}

echo "\n";

// ============================================================================
// EXAMPLE 13: Sign Operations
// ============================================================================

echo "EXAMPLE 13: Sign Operations\n";
echo "-----------------------------------------------------------------\n";

$negative = new Quantity(-50.5, 'kg');
$positive = $negative->abs();
$inverted = $positive->negate();

echo "Sign operations:\n";
echo "  • Original: {$negative->format('en_US', 2)}\n";
echo "  • Absolute: {$positive->format('en_US', 2)}\n";
echo "  • Negated: {$inverted->format('en_US', 2)}\n\n";

// ============================================================================
// Summary
// ============================================================================

echo "=================================================================\n";
echo "Summary: Basic Usage Examples Completed\n";
echo "=================================================================\n";
echo "\n";
echo "You've learned:\n";
echo "  ✓ Creating dimensions and units\n";
echo "  ✓ Creating quantities\n";
echo "  ✓ Direct and multi-hop conversions\n";
echo "  ✓ Arithmetic operations (add, subtract, multiply, divide)\n";
echo "  ✓ Comparisons (greater than, less than, equals)\n";
echo "  ✓ Locale-specific formatting\n";
echo "  ✓ Serialization and deserialization\n";
echo "  ✓ Temperature conversions with offset\n";
echo "  ✓ Exception handling\n";
echo "  ✓ Packaging hierarchies\n";
echo "  ✓ Validation before operations\n";
echo "  ✓ Sign operations (abs, negate)\n";
echo "\n";
echo "Next steps:\n";
echo "  • Review advanced-usage.php for complex scenarios\n";
echo "  • Read integration-guide.md for Laravel/Symfony setup\n";
echo "  • Explore api-reference.md for complete API documentation\n";
echo "\n";
