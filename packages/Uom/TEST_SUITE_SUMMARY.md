# Nexus UoM Package - Test Suite Summary

## Overview

This document outlines the comprehensive test strategy for the Nexus UoM package, covering unit tests, integration tests, performance tests, and edge case scenarios.

**Total Planned Tests:** 60+ tests across 12 test files  
**Coverage Target:** 95%+ code coverage  
**Test Framework:** PHPUnit 10.x  
**PHP Version:** 8.3+

---

## Test File Organization

```
tests/
├── Unit/
│   ├── ValueObjects/
│   │   ├── QuantityTest.php              (15 tests) ⭐
│   │   ├── UnitTest.php                  (5 tests)
│   │   ├── DimensionTest.php             (4 tests)
│   │   ├── ConversionRuleTest.php        (6 tests)
│   │   └── UnitSystemTest.php            (3 tests)
│   │
│   ├── Services/
│   │   ├── UomConversionEngineTest.php   (20 tests)
│   │   ├── UomValidationServiceTest.php  (12 tests)
│   │   └── UomManagerTest.php            (8 tests)
│   │
│   └── Exceptions/
│       └── ExceptionsTest.php            (10 tests)
│
└── Integration/
    ├── MultiHopConversionTest.php        (6 tests)
    ├── PerformanceBenchmarkTest.php      (4 tests)
    └── RepositoryContractTest.php        (8 tests)
```

---

## Unit Tests

### 1. QuantityTest.php (15 tests)

**Purpose:** Test the main API entry point - the Quantity value object

#### Test Cases

```php
<?php

namespace Nexus\Uom\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Uom\ValueObjects\Quantity;
use Nexus\Uom\Services\UomConversionEngine;

class QuantityTest extends TestCase
{
    /**
     * Test basic quantity construction
     */
    public function test_constructs_quantity_with_value_and_unit(): void
    {
        $qty = new Quantity(100.5, 'kg');
        
        $this->assertEquals(100.5, $qty->getValue());
        $this->assertEquals('kg', $qty->getUnitCode());
    }

    /**
     * Test multiplication by scalar
     */
    public function test_multiplies_quantity_by_scalar(): void
    {
        $qty = new Quantity(10, 'kg');
        $doubled = $qty->multiply(2);
        
        $this->assertEquals(20, $doubled->getValue());
        $this->assertEquals('kg', $doubled->getUnitCode());
        
        // Original unchanged (immutable)
        $this->assertEquals(10, $qty->getValue());
    }

    /**
     * Test division by scalar
     */
    public function test_divides_quantity_by_scalar(): void
    {
        $qty = new Quantity(100, 'kg');
        $half = $qty->divide(2);
        
        $this->assertEquals(50, $half->getValue());
        $this->assertEquals('kg', $half->getUnitCode());
    }

    /**
     * Test division by zero throws exception
     */
    public function test_division_by_zero_throws_exception(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        
        $qty = new Quantity(100, 'kg');
        $qty->divide(0);
    }

    /**
     * Test addition with automatic conversion
     */
    public function test_adds_quantities_with_conversion(): void
    {
        $engine = $this->createConversionEngine();
        
        $qty1 = new Quantity(100, 'kg');
        $qty2 = new Quantity(50, 'lb');
        
        $sum = $qty1->add($qty2, $engine);
        
        // 50 lb ≈ 22.68 kg, so 100 + 22.68 ≈ 122.68
        $this->assertEquals(122.68, $sum->getValue(), delta: 0.01);
        $this->assertEquals('kg', $sum->getUnitCode());
    }

    /**
     * Test subtraction with automatic conversion
     */
    public function test_subtracts_quantities_with_conversion(): void
    {
        $engine = $this->createConversionEngine();
        
        $qty1 = new Quantity(100, 'kg');
        $qty2 = new Quantity(50, 'lb');
        
        $diff = $qty1->subtract($qty2, $engine);
        
        $this->assertEquals(77.32, $diff->getValue(), delta: 0.01);
        $this->assertEquals('kg', $diff->getUnitCode());
    }

    /**
     * Test conversion to different unit
     */
    public function test_converts_to_different_unit(): void
    {
        $engine = $this->createConversionEngine();
        
        $kg = new Quantity(100, 'kg');
        $lb = $kg->convertTo('lb', $engine);
        
        $this->assertEquals(220.462, $lb->getValue(), delta: 0.001);
        $this->assertEquals('lb', $lb->getUnitCode());
    }

    /**
     * Test conversion to same unit returns self
     */
    public function test_conversion_to_same_unit_returns_self(): void
    {
        $engine = $this->createConversionEngine();
        
        $qty = new Quantity(100, 'kg');
        $same = $qty->convertTo('kg', $engine);
        
        $this->assertSame($qty, $same);
    }

    /**
     * Test greater than comparison
     */
    public function test_compares_greater_than(): void
    {
        $engine = $this->createConversionEngine();
        
        $qty1 = new Quantity(100, 'kg');
        $qty2 = new Quantity(50, 'lb');
        
        $this->assertTrue($qty1->greaterThan($qty2, $engine));
        $this->assertFalse($qty2->greaterThan($qty1, $engine));
    }

    /**
     * Test less than comparison
     */
    public function test_compares_less_than(): void
    {
        $engine = $this->createConversionEngine();
        
        $qty1 = new Quantity(50, 'lb');
        $qty2 = new Quantity(100, 'kg');
        
        $this->assertTrue($qty1->lessThan($qty2, $engine));
        $this->assertFalse($qty2->lessThan($qty1, $engine));
    }

    /**
     * Test equality comparison
     */
    public function test_compares_equality(): void
    {
        $engine = $this->createConversionEngine();
        
        $qty1 = new Quantity(100, 'kg');
        $qty2 = new Quantity(100, 'kg');
        
        $this->assertTrue($qty1->equals($qty2, $engine));
    }

    /**
     * Test formatting with locale
     */
    public function test_formats_with_locale(): void
    {
        $qty = new Quantity(1234.56, 'kg');
        
        $this->assertEquals('1,234.56 kg', $qty->format('en_US', 2));
        $this->assertEquals('1.234,56 kg', $qty->format('de_DE', 2));
    }

    /**
     * Test absolute value
     */
    public function test_absolute_value(): void
    {
        $negative = new Quantity(-50, 'kg');
        $positive = $negative->abs();
        
        $this->assertEquals(50, $positive->getValue());
        $this->assertEquals('kg', $positive->getUnitCode());
    }

    /**
     * Test negation
     */
    public function test_negation(): void
    {
        $positive = new Quantity(50, 'kg');
        $negative = $positive->negate();
        
        $this->assertEquals(-50, $negative->getValue());
        $this->assertEquals('kg', $negative->getUnitCode());
    }

    /**
     * Test serialization to array
     */
    public function test_serializes_to_array(): void
    {
        $qty = new Quantity(100.5, 'kg');
        $array = $qty->toArray();
        
        $this->assertEquals([
            'value' => 100.5,
            'unit_code' => 'kg',
        ], $array);
    }

    /**
     * Test deserialization from array
     */
    public function test_deserializes_from_array(): void
    {
        $array = ['value' => 100.5, 'unit_code' => 'kg'];
        $qty = Quantity::fromArray($array);
        
        $this->assertEquals(100.5, $qty->getValue());
        $this->assertEquals('kg', $qty->getUnitCode());
    }

    /**
     * Helper to create conversion engine with test data
     */
    private function createConversionEngine(): UomConversionEngine
    {
        // Setup in-memory repository with test data
        // ... (implementation details)
    }
}
```

**Expected Assertions:** 35+ assertions

---

### 2. UomConversionEngineTest.php (20 tests)

**Purpose:** Test core conversion logic including graph pathfinding

#### Test Cases

```php
<?php

namespace Nexus\Uom\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Uom\Exceptions\{
    UnitNotFoundException,
    IncompatibleUnitException,
    ConversionPathNotFoundException
};

class UomConversionEngineTest extends TestCase
{
    /**
     * Test direct conversion (single lookup)
     */
    public function test_performs_direct_conversion(): void
    {
        $engine = $this->createEngine();
        
        $result = $engine->convert(100, 'kg', 'lb');
        
        $this->assertEquals(220.462, $result, delta: 0.001);
    }

    /**
     * Test multi-hop conversion (graph pathfinding)
     */
    public function test_performs_multi_hop_conversion(): void
    {
        $engine = $this->createEngine();
        
        // oz → lb → kg → g (3 hops)
        $result = $engine->convert(16, 'oz', 'g');
        
        // 16 oz = 1 lb = 0.453592 kg = 453.592 g
        $this->assertEquals(453.592, $result, delta: 0.001);
    }

    /**
     * Test conversion with offset (temperature)
     */
    public function test_converts_with_offset(): void
    {
        $engine = $this->createEngine();
        
        // 25°C → °F: (25 × 1.8) + 32 = 77°F
        $result = $engine->convert(25, 'celsius', 'fahrenheit');
        
        $this->assertEquals(77.0, $result, delta: 0.1);
    }

    /**
     * Test conversion caching
     */
    public function test_caches_conversion_paths(): void
    {
        $engine = $this->createEngine();
        
        // First conversion (cache miss)
        $start = microtime(true);
        $engine->convert(100, 'kg', 'lb');
        $firstTime = microtime(true) - $start;
        
        // Second conversion (cache hit)
        $start = microtime(true);
        $engine->convert(200, 'kg', 'lb');
        $cachedTime = microtime(true) - $start;
        
        // Cached should be significantly faster
        $this->assertLessThan($firstTime, $cachedTime);
    }

    /**
     * Test same unit returns value unchanged
     */
    public function test_same_unit_returns_unchanged(): void
    {
        $engine = $this->createEngine();
        
        $result = $engine->convert(100, 'kg', 'kg');
        
        $this->assertEquals(100, $result);
    }

    /**
     * Test throws exception for missing unit
     */
    public function test_throws_exception_for_missing_unit(): void
    {
        $this->expectException(UnitNotFoundException::class);
        
        $engine = $this->createEngine();
        $engine->convert(100, 'invalid_unit', 'kg');
    }

    /**
     * Test throws exception for incompatible units
     */
    public function test_throws_exception_for_incompatible_units(): void
    {
        $this->expectException(IncompatibleUnitException::class);
        
        $engine = $this->createEngine();
        $engine->convert(100, 'kg', 'm');  // mass → length
    }

    /**
     * Test throws exception when no conversion path exists
     */
    public function test_throws_exception_when_no_path_exists(): void
    {
        $this->expectException(ConversionPathNotFoundException::class);
        
        $engine = $this->createEngineWithDisconnectedUnits();
        $engine->convert(100, 'unit_a', 'unit_b');
    }

    /**
     * Test cache can be cleared
     */
    public function test_clears_cache(): void
    {
        $engine = $this->createEngine();
        
        // Populate cache
        $engine->convert(100, 'kg', 'lb');
        
        // Clear cache
        $engine->clearCache();
        
        // Conversion still works (recomputes path)
        $result = $engine->convert(100, 'kg', 'lb');
        $this->assertEquals(220.462, $result, delta: 0.001);
    }

    /**
     * Test bidirectional conversions
     */
    public function test_bidirectional_conversions(): void
    {
        $engine = $this->createEngine();
        
        $forward = $engine->convert(100, 'kg', 'lb');
        $backward = $engine->convert($forward, 'lb', 'kg');
        
        $this->assertEquals(100, $backward, delta: 0.001);
    }

    // ... 10 more tests covering edge cases
}
```

**Expected Assertions:** 40+ assertions

---

### 3. UomValidationServiceTest.php (12 tests)

**Purpose:** Test all validation rules and edge cases

#### Key Tests

- Dimension compatibility validation
- Conversion ratio validation (positive, non-zero)
- Circular reference detection
- Unique code enforcement
- System unit protection
- Offset validation for dimension

**Expected Assertions:** 25+ assertions

---

### 4. ConversionRuleTest.php (6 tests)

**Purpose:** Test conversion rule value object and inverse calculation

#### Key Tests

```php
public function test_creates_inverse_conversion_rule(): void
{
    $rule = new ConversionRule('kg', 'lb', 2.20462);
    $inverse = $rule->inverse();
    
    $this->assertEquals('lb', $inverse->getFromUnit());
    $this->assertEquals('kg', $inverse->getToUnit());
    $this->assertEquals(0.453592, $inverse->getRatio(), delta: 0.000001);
}

public function test_inverse_with_offset(): void
{
    // °C → °F: (x × 1.8) + 32
    $rule = new ConversionRule('celsius', 'fahrenheit', 1.8, 32.0);
    $inverse = $rule->inverse();
    
    // °F → °C: (x - 32) / 1.8 = (x / 1.8) - 17.778
    $this->assertEquals(0.5556, $inverse->getRatio(), delta: 0.0001);
    $this->assertEquals(-17.778, $inverse->getOffset(), delta: 0.001);
}
```

**Expected Assertions:** 15+ assertions

---

## Integration Tests

### 5. MultiHopConversionTest.php (6 tests)

**Purpose:** Test complex conversion scenarios across multiple hops

#### Test Cases

```php
/**
 * Test 5-hop conversion path
 */
public function test_deep_conversion_path(): void
{
    // Create graph: A → B → C → D → E
    $engine = $this->createComplexGraph();
    
    $result = $engine->convert(100, 'unit_a', 'unit_e');
    
    // Verify correct result through all hops
    $expected = 100 * 2.0 * 3.0 * 4.0 * 5.0; // 1200
    $this->assertEquals($expected, $result);
}

/**
 * Test hub-and-spoke pattern
 */
public function test_hub_and_spoke_conversions(): void
{
    // Hub: kg
    // Spokes: g, mg, lb, oz, ton
    
    $engine = $this->createHubSpokeGraph();
    
    // All conversions should go through kg
    $result = $engine->convert(1000, 'mg', 'oz');
    
    // mg → kg → oz
    // 1000 mg = 0.001 kg = 0.03527 oz
    $this->assertEquals(0.03527, $result, delta: 0.00001);
}
```

**Expected Assertions:** 20+ assertions

---

### 6. PerformanceBenchmarkTest.php (4 tests)

**Purpose:** Ensure performance requirements are met

#### Test Cases

```php
/**
 * Test direct conversion performance
 */
public function test_direct_conversion_under_1ms(): void
{
    $engine = $this->createEngine();
    $iterations = 100;
    
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $engine->convert(100, 'kg', 'lb');
    }
    $elapsed = (microtime(true) - $start) * 1000;
    
    $avgTime = $elapsed / $iterations;
    $this->assertLessThan(1.0, $avgTime, "Direct conversion exceeded 1ms");
}

/**
 * Test multi-hop conversion with cache under 2ms
 */
public function test_cached_multi_hop_under_2ms(): void
{
    $engine = $this->createEngine();
    
    // Prime cache
    $engine->convert(1, 'oz', 'g');
    
    $iterations = 100;
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $engine->convert(100, 'oz', 'g');
    }
    $elapsed = (microtime(true) - $start) * 1000;
    
    $avgTime = $elapsed / $iterations;
    $this->assertLessThan(2.0, $avgTime, "Cached conversion exceeded 2ms");
}

/**
 * Test memory usage for 1000 quantities
 */
public function test_memory_usage_within_limits(): void
{
    $start = memory_get_usage();
    
    $quantities = [];
    for ($i = 0; $i < 1000; $i++) {
        $quantities[] = new Quantity($i, 'kg');
    }
    
    $used = memory_get_usage() - $start;
    $perInstance = $used / 1000;
    
    // Should use less than 2KB per instance
    $this->assertLessThan(2048, $perInstance);
}
```

**Expected Assertions:** 10+ assertions

---

## Test Data Fixtures

### Standard Test Units

```php
class UomTestFixtures
{
    public static function createStandardUnits(): array
    {
        return [
            // Mass
            'kg' => ['dimension' => 'mass', 'base' => true],
            'g' => ['dimension' => 'mass', 'base' => false],
            'lb' => ['dimension' => 'mass', 'base' => false],
            'oz' => ['dimension' => 'mass', 'base' => false],
            
            // Length
            'm' => ['dimension' => 'length', 'base' => true],
            'cm' => ['dimension' => 'length', 'base' => false],
            'ft' => ['dimension' => 'length', 'base' => false],
            'in' => ['dimension' => 'length', 'base' => false],
            
            // Temperature
            'celsius' => ['dimension' => 'temperature', 'base' => false],
            'fahrenheit' => ['dimension' => 'temperature', 'base' => false],
            'kelvin' => ['dimension' => 'temperature', 'base' => true],
        ];
    }
    
    public static function createStandardConversions(): array
    {
        return [
            ['kg', 'g', 1000.0, 0.0],
            ['kg', 'lb', 2.20462, 0.0],
            ['lb', 'oz', 16.0, 0.0],
            ['m', 'cm', 100.0, 0.0],
            ['m', 'ft', 3.28084, 0.0],
            ['ft', 'in', 12.0, 0.0],
            ['celsius', 'fahrenheit', 1.8, 32.0],
            ['celsius', 'kelvin', 1.0, 273.15],
        ];
    }
}
```

---

## Coverage Goals

### Code Coverage Targets

| Component | Target Coverage | Critical Paths |
|-----------|----------------|----------------|
| Value Objects | 100% | All public methods |
| Services | 95%+ | Core conversion logic |
| Exceptions | 90%+ | Factory methods |
| Validation | 100% | All validation rules |
| **Overall** | **95%+** | - |

### Critical Paths (Must be 100% covered)

1. ✅ Quantity arithmetic operations
2. ✅ Direct conversion lookups
3. ✅ Multi-hop pathfinding algorithm
4. ✅ Circular reference detection
5. ✅ Dimension compatibility checks
6. ✅ Offset conversion calculations

---

## Running Tests

### Run All Tests

```bash
cd packages/Uom
vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Unit tests only
vendor/bin/phpunit tests/Unit

# Integration tests only
vendor/bin/phpunit tests/Integration

# Specific test file
vendor/bin/phpunit tests/Unit/ValueObjects/QuantityTest.php
```

### Run with Coverage Report

```bash
vendor/bin/phpunit --coverage-html coverage
```

### Run Performance Tests

```bash
vendor/bin/phpunit tests/Integration/PerformanceBenchmarkTest.php
```

---

## Continuous Integration

### GitHub Actions Workflow

```yaml
name: UoM Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.3', '8.4']
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: bcmath, mbstring
      
      - name: Install dependencies
        run: composer install
      
      - name: Run tests
        run: vendor/bin/phpunit --coverage-clover coverage.xml
      
      - name: Upload coverage
        uses: codecov/codecov-action@v2
```

---

## Summary

The Nexus UoM package test suite provides comprehensive coverage of all functionality with:

- **60+ tests** across unit and integration categories
- **95%+ code coverage** target
- **Performance benchmarks** ensuring < 2ms avg conversion time
- **Edge case testing** for all exception scenarios
- **CI/CD integration** for automated testing

All tests follow PHPUnit best practices with clear assertions, descriptive test names, and proper fixture management.

---

**Last Updated:** November 28, 2024  
**Test Framework:** PHPUnit 10.x  
**PHP Version:** 8.3+
