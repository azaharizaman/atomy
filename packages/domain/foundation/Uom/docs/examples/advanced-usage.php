<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples for Nexus UoM Package
 *
 * This file demonstrates complex UoM scenarios including:
 * - Complex multi-hop conversions
 * - Packaging hierarchy (pallet → case → each)
 * - Temperature conversions with offset
 * - Circular reference detection
 * - Custom dimension creation
 * - Performance benchmarking
 * - Integration with business logic
 *
 * Run this file directly: php advanced-usage.php
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
    CircularConversionException,
    InvalidConversionRatioException,
    IncompatibleUnitException
};

// Include the in-memory repository from basic-usage.php
require_once __DIR__ . '/basic-usage.php';

echo "\n\n";
echo "=================================================================\n";
echo "Nexus UoM Package - Advanced Usage Examples\n";
echo "=================================================================\n\n";

// ============================================================================
// ADVANCED EXAMPLE 1: Complex Multi-Hop Conversion Graph
// ============================================================================

echo "ADVANCED EXAMPLE 1: Complex Multi-Hop Conversion Graph\n";
echo "-----------------------------------------------------------------\n";

// Setup complex conversion graph
$repository = new InMemoryUomRepository();
$validator = new UomValidationService($repository);
$engine = new UomConversionEngine($repository, $validator);
$manager = new UomManager($repository, $engine, $validator);

// Create Mass dimension with many units
$manager->createDimension('mass', 'Mass', 'kg', false);

$manager->createUnit('kg', 'Kilogram', 'kg', 'mass', 'metric', true, true);
$manager->createUnit('g', 'Gram', 'g', 'mass', 'metric', false, true);
$manager->createUnit('mg', 'Milligram', 'mg', 'mass', 'metric', false, true);
$manager->createUnit('ton', 'Metric Ton', 't', 'mass', 'metric', false, true);

$manager->createUnit('lb', 'Pound', 'lb', 'mass', 'imperial', false, true);
$manager->createUnit('oz', 'Ounce', 'oz', 'mass', 'imperial', false, true);
$manager->createUnit('stone', 'Stone', 'st', 'mass', 'imperial', false, true);

// Create conversions (hub-and-spoke pattern with kg as hub)
$manager->createConversion('kg', 'g', 1000.0);
$manager->createConversion('kg', 'mg', 1000000.0);
$manager->createConversion('kg', 'ton', 0.001);

$manager->createConversion('kg', 'lb', 2.20462);
$manager->createConversion('lb', 'oz', 16.0);
$manager->createConversion('stone', 'lb', 14.0);

echo "Created complex conversion graph with 7 units\n";
echo "Hub: kg (base unit)\n\n";

// Test deep path: mg → kg → lb → oz
$milligrams = new Quantity(1000000, 'mg');
$ounces = $milligrams->convertTo('oz', $engine);

echo "Deep path conversion: 1,000,000 mg → oz\n";
echo "  Path: mg → kg → lb → oz\n";
echo "  Steps:\n";
echo "    1. 1,000,000 mg ÷ 1,000,000 = 1 kg\n";
echo "    2. 1 kg × 2.20462 = 2.20462 lb\n";
echo "    3. 2.20462 lb × 16 = 35.27 oz\n";
echo "  Result: {$ounces->format('en_US', 2)}\n\n";

// Test another deep path: stone → lb → kg → ton
$stones = new Quantity(10, 'stone');
$tons = $stones->convertTo('ton', $engine);

echo "Another deep path: 10 stone → ton\n";
echo "  Path: stone → lb → kg → ton\n";
echo "  Result: {$tons->format('en_US', 4)}\n\n";

// ============================================================================
// ADVANCED EXAMPLE 2: Packaging Hierarchy for E-Commerce
// ============================================================================

echo "ADVANCED EXAMPLE 2: E-Commerce Packaging Hierarchy\n";
echo "-----------------------------------------------------------------\n";

// Setup packaging dimension
$manager->createDimension('packaging', 'Packaging', 'each', false);

$manager->createUnit('each', 'Each', 'ea', 'packaging', null, true, true);
$manager->createUnit('inner_pack', 'Inner Pack', 'ip', 'packaging', null, false, false);
$manager->createUnit('case', 'Case', 'cs', 'packaging', null, false, false);
$manager->createUnit('layer', 'Pallet Layer', 'lyr', 'packaging', null, false, false);
$manager->createUnit('pallet', 'Pallet', 'plt', 'packaging', null, false, false);

// Real-world packaging structure for a product
// 1 Inner Pack = 6 Each
// 1 Case = 4 Inner Packs = 24 Each
// 1 Layer = 5 Cases = 120 Each
// 1 Pallet = 6 Layers = 720 Each

$manager->createConversion('inner_pack', 'each', 6.0);
$manager->createConversion('case', 'inner_pack', 4.0);
$manager->createConversion('layer', 'case', 5.0);
$manager->createConversion('pallet', 'layer', 6.0);

echo "E-Commerce packaging structure:\n";
echo "  • 1 Pallet = 6 Layers = 30 Cases = 120 Inner Packs = 720 Each\n\n";

// Order scenarios
$customerOrder = new Quantity(3, 'pallet');
$warehouseView = $customerOrder->convertTo('case', $engine);
$pickingView = $customerOrder->convertTo('each', $engine);

echo "Order: {$customerOrder->format('en_US', 0)}\n";
echo "  → Warehouse view: {$warehouseView->format('en_US', 0)}\n";
echo "  → Picking view: {$pickingView->format('en_US', 0)}\n\n";

// Partial order calculation
$orderedEach = new Quantity(500, 'each');
$fullPallets = new Quantity(floor($orderedEach->getValue() / 720), 'pallet');
$remainingEach = new Quantity($orderedEach->getValue() - ($fullPallets->getValue() * 720), 'each');
$remainingCases = new Quantity(floor($remainingEach->getValue() / 24), 'case');
$remainingFinal = new Quantity($remainingEach->getValue() - ($remainingCases->getValue() * 24), 'each');

echo "Order breakdown for 500 each:\n";
echo "  • Full pallets: {$fullPallets->format('en_US', 0)}\n";
echo "  • Additional cases: {$remainingCases->format('en_US', 0)}\n";
echo "  • Loose eaches: {$remainingFinal->format('en_US', 0)}\n";
echo "  • Total: 0 pallets + 20 cases + 20 eaches = 500 eaches\n\n";

// ============================================================================
// ADVANCED EXAMPLE 3: Temperature Scales with Complex Conversions
// ============================================================================

echo "ADVANCED EXAMPLE 3: Temperature Scales with Complex Conversions\n";
echo "-----------------------------------------------------------------\n";

$manager->createDimension('temperature', 'Temperature', 'kelvin', true);

$manager->createUnit('kelvin', 'Kelvin', 'K', 'temperature', 'metric', true, true);
$manager->createUnit('celsius', 'Celsius', '°C', 'temperature', 'metric', false, true);
$manager->createUnit('fahrenheit', 'Fahrenheit', '°F', 'temperature', 'imperial', false, true);
$manager->createUnit('rankine', 'Rankine', '°R', 'temperature', 'imperial', false, true);

// Conversion formulas:
// Celsius to Fahrenheit: °F = (°C × 1.8) + 32
// Celsius to Kelvin: K = °C + 273.15
// Fahrenheit to Rankine: °R = °F + 459.67

$manager->createConversion('celsius', 'fahrenheit', 1.8, 32.0);
$manager->createConversion('celsius', 'kelvin', 1.0, 273.15);
$manager->createConversion('fahrenheit', 'rankine', 1.0, 459.67);

echo "Temperature scale conversions:\n\n";

// Freezing point of water
$freezingC = new Quantity(0, 'celsius');
$freezingF = $freezingC->convertTo('fahrenheit', $engine);
$freezingK = $freezingC->convertTo('kelvin', $engine);

echo "Freezing point of water:\n";
echo "  • {$freezingC->format('en_US', 1)}\n";
echo "  • {$freezingF->format('en_US', 1)}\n";
echo "  • {$freezingK->format('en_US', 2)}\n\n";

// Boiling point of water
$boilingC = new Quantity(100, 'celsius');
$boilingF = $boilingC->convertTo('fahrenheit', $engine);
$boilingK = $boilingC->convertTo('kelvin', $engine);

echo "Boiling point of water:\n";
echo "  • {$boilingC->format('en_US', 1)}\n";
echo "  • {$boilingF->format('en_US', 1)}\n";
echo "  • {$boilingK->format('en_US', 2)}\n\n";

// Multi-hop: Fahrenheit → Celsius → Kelvin
$roomTempF = new Quantity(72, 'fahrenheit');
$roomTempK = $roomTempF->convertTo('kelvin', $engine);

echo "Room temperature conversion:\n";
echo "  • {$roomTempF->format('en_US', 1)} → {$roomTempK->format('en_US', 2)}\n";
echo "  • Path: °F → °C → K\n\n";

// ============================================================================
// ADVANCED EXAMPLE 4: Circular Reference Detection
// ============================================================================

echo "ADVANCED EXAMPLE 4: Circular Reference Detection\n";
echo "-----------------------------------------------------------------\n";

// Create test dimension for circular detection
$manager->createDimension('circular_test', 'Circular Test', 'unit_a', false);
$manager->createUnit('unit_a', 'Unit A', 'A', 'circular_test', null, true, false);
$manager->createUnit('unit_b', 'Unit B', 'B', 'circular_test', null, false, false);
$manager->createUnit('unit_c', 'Unit C', 'C', 'circular_test', null, false, false);

// Create chain: A → B → C
$manager->createConversion('unit_a', 'unit_b', 2.0);
$manager->createConversion('unit_b', 'unit_c', 3.0);

echo "Created conversion chain: A → B → C\n";

// Attempt to create circular reference: C → A
try {
    $validator->validateNoCircularConversion('unit_c', 'unit_a');
    $manager->createConversion('unit_c', 'unit_a', 0.5);
    echo "⚠ WARNING: Circular reference should have been detected!\n";
} catch (CircularConversionException $e) {
    echo "✓ Circular reference detected and prevented!\n";
    echo "  Attempted: C → A (would create A → B → C → A)\n";
}

echo "\n";

// ============================================================================
// ADVANCED EXAMPLE 5: Custom Dimension for Industry-Specific Units
// ============================================================================

echo "ADVANCED EXAMPLE 5: Industry-Specific Custom Dimensions\n";
echo "-----------------------------------------------------------------\n";

// Example: Textile industry - yarn weight
$manager->createDimension('yarn_weight', 'Yarn Weight', 'tex', false, 'Textile yarn weight units');

$manager->createUnit('tex', 'Tex', 'tex', 'yarn_weight', null, true, false);
$manager->createUnit('denier', 'Denier', 'den', 'yarn_weight', null, false, false);
$manager->createUnit('nm', 'Metric Count', 'Nm', 'yarn_weight', null, false, false);

// Conversions for textile industry
// 1 denier = 0.111 tex
// 1 Nm = 1000/tex (inverse relationship)
$manager->createConversion('denier', 'tex', 0.111);

echo "Textile industry custom dimension:\n";
echo "  • Dimension: Yarn Weight\n";
echo "  • Units: tex (base), denier, Nm\n\n";

$yarnDenier = new Quantity(150, 'denier');
$yarnTex = $yarnDenier->convertTo('tex', $engine);

echo "Yarn weight conversion:\n";
echo "  • {$yarnDenier->format('en_US', 0)} = {$yarnTex->format('en_US', 2)}\n\n";

// Example: Pharmaceutical - concentration
$manager->createDimension('concentration', 'Concentration', 'mg_ml', false);

$manager->createUnit('mg_ml', 'Milligrams per Milliliter', 'mg/mL', 'concentration', null, true, false);
$manager->createUnit('percent_w_v', 'Percent Weight/Volume', '% w/v', 'concentration', null, false, false);
$manager->createUnit('ppm', 'Parts Per Million', 'ppm', 'concentration', null, false, false);

// 1% w/v = 10 mg/mL
// 1 mg/mL = 1000 ppm (for aqueous solutions)
$manager->createConversion('percent_w_v', 'mg_ml', 10.0);
$manager->createConversion('mg_ml', 'ppm', 1000.0);

echo "Pharmaceutical concentration:\n";

$concentration = new Quantity(5, 'percent_w_v');
$concentrationMgMl = $concentration->convertTo('mg_ml', $engine);
$concentrationPpm = $concentration->convertTo('ppm', $engine);

echo "  • {$concentration->format('en_US', 1)}\n";
echo "  • {$concentrationMgMl->format('en_US', 1)}\n";
echo "  • {$concentrationPpm->format('en_US', 0)}\n\n";

// ============================================================================
// ADVANCED EXAMPLE 6: Recipe Scaling with Mixed Units
// ============================================================================

echo "ADVANCED EXAMPLE 6: Recipe Scaling with Mixed Units\n";
echo "-----------------------------------------------------------------\n";

// Setup volume dimension
$manager->createDimension('volume', 'Volume', 'liter', false);

$manager->createUnit('liter', 'Liter', 'L', 'volume', 'metric', true, true);
$manager->createUnit('milliliter', 'Milliliter', 'mL', 'volume', 'metric', false, true);
$manager->createUnit('cup', 'Cup', 'cup', 'volume', 'imperial', false, true);
$manager->createUnit('tablespoon', 'Tablespoon', 'tbsp', 'volume', 'imperial', false, true);
$manager->createUnit('teaspoon', 'Teaspoon', 'tsp', 'volume', 'imperial', false, true);

$manager->createConversion('liter', 'milliliter', 1000.0);
$manager->createConversion('liter', 'cup', 4.22675);
$manager->createConversion('cup', 'tablespoon', 16.0);
$manager->createConversion('tablespoon', 'teaspoon', 3.0);

// Recipe for 4 servings
$recipe = [
    'flour' => new Quantity(500, 'g'),
    'sugar' => new Quantity(200, 'g'),
    'milk' => new Quantity(250, 'milliliter'),
    'vanilla' => new Quantity(2, 'teaspoon'),
];

echo "Original recipe (4 servings):\n";
foreach ($recipe as $ingredient => $qty) {
    echo "  • " . ucfirst($ingredient) . ": {$qty->format('en_US', 0)}\n";
}
echo "\n";

// Scale to 12 servings (3x)
$scaleFactor = 12 / 4;
$scaledRecipe = [];

foreach ($recipe as $ingredient => $qty) {
    $scaledRecipe[$ingredient] = $qty->multiply($scaleFactor);
}

echo "Scaled recipe (12 servings, 3x):\n";
foreach ($scaledRecipe as $ingredient => $qty) {
    echo "  • " . ucfirst($ingredient) . ": {$qty->format('en_US', 0)}\n";
}
echo "\n";

// Convert to US customary units
echo "US customary units:\n";
$milkCups = $scaledRecipe['milk']->convertTo('cup', $engine);
$vanillaTbsp = $scaledRecipe['vanilla']->convertTo('tablespoon', $engine);
echo "  • Milk: {$milkCups->format('en_US', 2)}\n";
echo "  • Vanilla: {$vanillaTbsp->format('en_US', 2)}\n\n";

// ============================================================================
// ADVANCED EXAMPLE 7: Performance Benchmarking
// ============================================================================

echo "ADVANCED EXAMPLE 7: Performance Benchmarking\n";
echo "-----------------------------------------------------------------\n";

// Direct conversion benchmark
$iterations = 1000;
$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    $qty = new Quantity(100, 'kg');
    $converted = $qty->convertTo('lb', $engine);
}

$directTime = (microtime(true) - $start) * 1000;
$avgDirect = $directTime / $iterations;

echo "Direct conversion (kg → lb):\n";
echo "  • Iterations: {$iterations}\n";
echo "  • Total time: " . number_format($directTime, 2) . " ms\n";
echo "  • Average: " . number_format($avgDirect, 4) . " ms/conversion\n\n";

// Multi-hop conversion benchmark
$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    $qty = new Quantity(16, 'oz');
    $converted = $qty->convertTo('g', $engine);
}

$multiHopTime = (microtime(true) - $start) * 1000;
$avgMultiHop = $multiHopTime / $iterations;

echo "Multi-hop conversion (oz → lb → kg → g):\n";
echo "  • Iterations: {$iterations}\n";
echo "  • Total time: " . number_format($multiHopTime, 2) . " ms\n";
echo "  • Average: " . number_format($avgMultiHop, 4) . " ms/conversion\n";
echo "  • Note: First conversion slower (path finding), subsequent cached\n\n";

// Arithmetic operations benchmark
$start = microtime(true);

$qty1 = new Quantity(100, 'kg');
$qty2 = new Quantity(50, 'lb');

for ($i = 0; $i < $iterations; $i++) {
    $sum = $qty1->add($qty2, $engine);
}

$arithmeticTime = (microtime(true) - $start) * 1000;
$avgArithmetic = $arithmeticTime / $iterations;

echo "Arithmetic with conversion (100 kg + 50 lb):\n";
echo "  • Iterations: {$iterations}\n";
echo "  • Total time: " . number_format($arithmeticTime, 2) . " ms\n";
echo "  • Average: " . number_format($avgArithmetic, 4) . " ms/operation\n\n";

// ============================================================================
// ADVANCED EXAMPLE 8: Business Logic Integration
// ============================================================================

echo "ADVANCED EXAMPLE 8: Business Logic Integration\n";
echo "-----------------------------------------------------------------\n";

/**
 * Example: Product pricing with quantity breaks
 */
class ProductPricing
{
    private array $priceTiers = [];

    public function __construct(
        private string $sku,
        private Quantity $baseQuantity,
        private float $basePrice,
        private UomConversionEngine $engine
    ) {}

    public function addTier(Quantity $minQty, float $discount): void
    {
        $this->priceTiers[] = [
            'min_qty' => $minQty,
            'discount' => $discount,
        ];

        // Sort by quantity descending
        usort($this->priceTiers, function ($a, $b) {
            $aInBase = $a['min_qty']->convertTo($this->baseQuantity->getUnitCode(), $this->engine);
            $bInBase = $b['min_qty']->convertTo($this->baseQuantity->getUnitCode(), $this->engine);
            return $bInBase->getValue() <=> $aInBase->getValue();
        });
    }

    public function calculatePrice(Quantity $orderQty): float
    {
        // Convert order to base unit
        $orderInBase = $orderQty->convertTo($this->baseQuantity->getUnitCode(), $this->engine);
        $units = $orderInBase->getValue();

        // Find applicable tier
        $discount = 0.0;
        foreach ($this->priceTiers as $tier) {
            $tierMin = $tier['min_qty']->convertTo($this->baseQuantity->getUnitCode(), $this->engine);
            if ($units >= $tierMin->getValue()) {
                $discount = $tier['discount'];
                break;
            }
        }

        // Calculate price
        $subtotal = $units * $this->basePrice;
        $total = $subtotal * (1 - $discount);

        return $total;
    }
}

$pricing = new ProductPricing(
    sku: 'WIDGET-001',
    baseQuantity: new Quantity(1, 'each'),
    basePrice: 10.00,
    engine: $engine
);

// Add quantity breaks
$pricing->addTier(new Quantity(10, 'each'), 0.05);   // 5% off for 10+
$pricing->addTier(new Quantity(50, 'each'), 0.10);   // 10% off for 50+
$pricing->addTier(new Quantity(100, 'each'), 0.15);  // 15% off for 100+

echo "Product pricing with quantity breaks:\n";
echo "  • Base price: $10.00 per each\n\n";

$scenarios = [
    new Quantity(5, 'each'),
    new Quantity(25, 'each'),
    new Quantity(75, 'each'),
    new Quantity(150, 'each'),
];

foreach ($scenarios as $orderQty) {
    $price = $pricing->calculatePrice($orderQty);
    $unitPrice = $price / $orderQty->getValue();
    echo "  • Order {$orderQty->format('en_US', 0)}: \$" . number_format($price, 2);
    echo " (\$" . number_format($unitPrice, 2) . " per unit)\n";
}

echo "\n";

// ============================================================================
// ADVANCED EXAMPLE 9: Inventory Reconciliation with Mixed Units
// ============================================================================

echo "ADVANCED EXAMPLE 9: Inventory Reconciliation\n";
echo "-----------------------------------------------------------------\n";

class InventoryLedger
{
    private array $transactions = [];

    public function __construct(
        private string $productId,
        private string $baseUnit,
        private UomConversionEngine $engine
    ) {}

    public function addTransaction(string $type, Quantity $qty): void
    {
        $this->transactions[] = [
            'type' => $type,
            'quantity' => $qty,
            'timestamp' => time(),
        ];
    }

    public function getBalance(): Quantity
    {
        $balanceValue = 0.0;

        foreach ($this->transactions as $txn) {
            $qtyInBase = $txn['quantity']->convertTo($this->baseUnit, $this->engine);
            
            if ($txn['type'] === 'receipt' || $txn['type'] === 'production') {
                $balanceValue += $qtyInBase->getValue();
            } elseif ($txn['type'] === 'shipment' || $txn['type'] === 'consumption') {
                $balanceValue -= $qtyInBase->getValue();
            }
        }

        return new Quantity($balanceValue, $this->baseUnit);
    }
}

$ledger = new InventoryLedger('WIDGET-001', 'each', $engine);

echo "Inventory transactions:\n";

$ledger->addTransaction('receipt', new Quantity(5, 'pallet'));
echo "  • Receipt: 5 pallets\n";

$ledger->addTransaction('shipment', new Quantity(50, 'case'));
echo "  • Shipment: 50 cases\n";

$ledger->addTransaction('receipt', new Quantity(1000, 'each'));
echo "  • Receipt: 1000 eaches\n";

$ledger->addTransaction('shipment', new Quantity(500, 'each'));
echo "  • Shipment: 500 eaches\n";

$balance = $ledger->getBalance();
echo "\nCurrent balance: {$balance->format('en_US', 0)}\n";

// Calculation breakdown
echo "\nBreakdown:\n";
echo "  • 5 pallets = 3,600 each\n";
echo "  • -50 cases = -1,200 each\n";
echo "  • +1,000 each = +1,000 each\n";
echo "  • -500 each = -500 each\n";
echo "  • Total: 2,900 each\n\n";

// ============================================================================
// Summary
// ============================================================================

echo "=================================================================\n";
echo "Summary: Advanced Usage Examples Completed\n";
echo "=================================================================\n";
echo "\n";
echo "You've mastered:\n";
echo "  ✓ Complex multi-hop conversion graphs\n";
echo "  ✓ E-commerce packaging hierarchies (5 levels)\n";
echo "  ✓ Temperature conversions with multiple scales\n";
echo "  ✓ Circular reference detection and prevention\n";
echo "  ✓ Industry-specific custom dimensions (textile, pharma)\n";
echo "  ✓ Recipe scaling with mixed units\n";
echo "  ✓ Performance benchmarking (< 1ms avg per conversion)\n";
echo "  ✓ Business logic integration (pricing, inventory)\n";
echo "\n";
echo "Performance Summary:\n";
echo "  • Direct conversions: ~" . number_format($avgDirect, 4) . " ms\n";
echo "  • Multi-hop conversions: ~" . number_format($avgMultiHop, 4) . " ms\n";
echo "  • Arithmetic operations: ~" . number_format($avgArithmetic, 4) . " ms\n";
echo "\n";
echo "Next steps:\n";
echo "  • Implement in your production application\n";
echo "  • Add domain-specific dimensions for your industry\n";
echo "  • Set up database persistence (see integration-guide.md)\n";
echo "  • Monitor conversion cache hit rates\n";
echo "\n";
