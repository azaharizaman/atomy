# Integration Guide: UoM

Complete integration examples for Laravel and Symfony frameworks with database persistence, controller examples, and common patterns.

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Database Schema](#database-schema)
4. [Common Patterns](#common-patterns)
5. [Performance Optimization](#performance-optimization)
6. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/uom:"*@dev"
```

### Step 2: Create Database Migrations

#### Dimensions Table

```php
<?php
// database/migrations/2025_11_28_000001_create_uom_dimensions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_dimensions', function (Blueprint $table) {
            $table->string('code', 50)->primary();
            $table->string('name');
            $table->string('base_unit', 50);
            $table->boolean('allows_offset')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('base_unit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_dimensions');
    }
};
```

#### Units Table

```php
<?php
// database/migrations/2025_11_28_000002_create_uom_units_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_units', function (Blueprint $table) {
            $table->string('code', 50)->primary();
            $table->string('name');
            $table->string('symbol', 20);
            $table->string('dimension_code', 50);
            $table->string('system_code', 50)->nullable();
            $table->boolean('is_base_unit')->default(false)->index();
            $table->boolean('is_system_unit')->default(true);
            $table->timestamps();
            
            $table->foreign('dimension_code')
                ->references('code')
                ->on('uom_dimensions')
                ->onDelete('cascade');
            
            $table->index('dimension_code');
            $table->index('system_code');
            $table->index(['dimension_code', 'is_base_unit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_units');
    }
};
```

#### Conversion Rules Table

```php
<?php
// database/migrations/2025_11_28_000003_create_uom_conversions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('from_unit', 50);
            $table->string('to_unit', 50);
            $table->decimal('ratio', 20, 10);
            $table->decimal('offset', 20, 10)->default(0);
            $table->boolean('is_bidirectional')->default(true);
            $table->timestamps();
            
            $table->foreign('from_unit')
                ->references('code')
                ->on('uom_units')
                ->onDelete('cascade');
            
            $table->foreign('to_unit')
                ->references('code')
                ->on('uom_units')
                ->onDelete('cascade');
            
            $table->unique(['from_unit', 'to_unit']);
            $table->index('from_unit');
            $table->index('to_unit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
```

### Step 3: Create Eloquent Models

#### Eloquent Models (Optional - if using ORM)

```php
<?php
// app/Models/UomDimension.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UomDimension extends Model
{
    protected $table = 'uom_dimensions';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'base_unit',
        'allows_offset',
        'description',
    ];

    protected $casts = [
        'allows_offset' => 'boolean',
    ];

    public function units()
    {
        return $this->hasMany(UomUnit::class, 'dimension_code', 'code');
    }
}
```

```php
<?php
// app/Models/UomUnit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UomUnit extends Model
{
    protected $table = 'uom_units';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'dimension_code',
        'system_code',
        'is_base_unit',
        'is_system_unit',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'is_system_unit' => 'boolean',
    ];

    public function dimension()
    {
        return $this->belongsTo(UomDimension::class, 'dimension_code', 'code');
    }

    public function conversionsFrom()
    {
        return $this->hasMany(UomConversion::class, 'from_unit', 'code');
    }

    public function conversionsTo()
    {
        return $this->hasMany(UomConversion::class, 'to_unit', 'code');
    }
}
```

```php
<?php
// app/Models/UomConversion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UomConversion extends Model
{
    protected $table = 'uom_conversions';

    protected $fillable = [
        'from_unit',
        'to_unit',
        'ratio',
        'offset',
        'is_bidirectional',
    ];

    protected $casts = [
        'ratio' => 'decimal:10',
        'offset' => 'decimal:10',
        'is_bidirectional' => 'boolean',
    ];

    public function fromUnit()
    {
        return $this->belongsTo(UomUnit::class, 'from_unit', 'code');
    }

    public function toUnit()
    {
        return $this->belongsTo(UomUnit::class, 'to_unit', 'code');
    }
}
```

### Step 4: Create Repository Implementation

```php
<?php
// app/Repositories/LaravelUomRepository.php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Nexus\Uom\Contracts\{
    UomRepositoryInterface,
    UnitInterface,
    DimensionInterface,
    ConversionRuleInterface
};
use Nexus\Uom\ValueObjects\{Unit, Dimension, ConversionRule};
use Nexus\Uom\Exceptions\{
    DuplicateUnitCodeException,
    DuplicateDimensionCodeException,
    SystemUnitProtectedException
};

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

    public function findDimensionByCode(string $code): ?DimensionInterface
    {
        $row = DB::table('uom_dimensions')
            ->where('code', $code)
            ->first();

        if ($row === null) {
            return null;
        }

        return new Dimension(
            code: $row->code,
            name: $row->name,
            baseUnit: $row->base_unit,
            allowsOffset: (bool) $row->allows_offset,
            description: $row->description
        );
    }

    public function getUnitsByDimension(string $dimensionCode): array
    {
        $rows = DB::table('uom_units')
            ->where('dimension_code', $dimensionCode)
            ->get();

        return $rows->map(fn($row) => new Unit(
            code: $row->code,
            name: $row->name,
            symbol: $row->symbol,
            dimension: $row->dimension_code,
            system: $row->system_code,
            isBaseUnit: (bool) $row->is_base_unit,
            isSystemUnit: (bool) $row->is_system_unit
        ))->toArray();
    }

    public function getUnitsBySystem(string $systemCode): array
    {
        $rows = DB::table('uom_units')
            ->where('system_code', $systemCode)
            ->get();

        return $rows->map(fn($row) => new Unit(
            code: $row->code,
            name: $row->name,
            symbol: $row->symbol,
            dimension: $row->dimension_code,
            system: $row->system_code,
            isBaseUnit: (bool) $row->is_base_unit,
            isSystemUnit: (bool) $row->is_system_unit
        ))->toArray();
    }

    public function findConversion(string $fromUnitCode, string $toUnitCode): ?ConversionRuleInterface
    {
        $row = DB::table('uom_conversions')
            ->where('from_unit', $fromUnitCode)
            ->where('to_unit', $toUnitCode)
            ->first();

        if ($row === null) {
            return null;
        }

        return new ConversionRule(
            fromUnit: $row->from_unit,
            toUnit: $row->to_unit,
            ratio: (float) $row->ratio,
            offset: (float) $row->offset,
            isBidirectional: (bool) $row->is_bidirectional
        );
    }

    public function getConversionsFrom(string $fromUnitCode): array
    {
        $rows = DB::table('uom_conversions')
            ->where('from_unit', $fromUnitCode)
            ->get();

        return $rows->map(fn($row) => new ConversionRule(
            fromUnit: $row->from_unit,
            toUnit: $row->to_unit,
            ratio: (float) $row->ratio,
            offset: (float) $row->offset,
            isBidirectional: (bool) $row->is_bidirectional
        ))->toArray();
    }

    public function getConversionsByDimension(string $dimensionCode): array
    {
        $rows = DB::table('uom_conversions as c')
            ->join('uom_units as u', 'c.from_unit', '=', 'u.code')
            ->where('u.dimension_code', $dimensionCode)
            ->select('c.*')
            ->get();

        return $rows->map(fn($row) => new ConversionRule(
            fromUnit: $row->from_unit,
            toUnit: $row->to_unit,
            ratio: (float) $row->ratio,
            offset: (float) $row->offset,
            isBidirectional: (bool) $row->is_bidirectional
        ))->toArray();
    }

    public function saveUnit(UnitInterface $unit): UnitInterface
    {
        $exists = DB::table('uom_units')->where('code', $unit->getCode())->exists();
        
        if ($exists) {
            throw DuplicateUnitCodeException::forCode($unit->getCode());
        }

        DB::table('uom_units')->insert([
            'code' => $unit->getCode(),
            'name' => $unit->getName(),
            'symbol' => $unit->getSymbol(),
            'dimension_code' => $unit->getDimension(),
            'system_code' => $unit->getSystem(),
            'is_base_unit' => $unit->isBaseUnit(),
            'is_system_unit' => $unit->isSystemUnit(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $unit;
    }

    public function saveDimension(DimensionInterface $dimension): DimensionInterface
    {
        $exists = DB::table('uom_dimensions')->where('code', $dimension->getCode())->exists();
        
        if ($exists) {
            throw DuplicateDimensionCodeException::forCode($dimension->getCode());
        }

        DB::table('uom_dimensions')->insert([
            'code' => $dimension->getCode(),
            'name' => $dimension->getName(),
            'base_unit' => $dimension->getBaseUnit(),
            'allows_offset' => $dimension->allowsOffset(),
            'description' => $dimension->getDescription(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $dimension;
    }

    public function saveConversion(ConversionRuleInterface $rule): ConversionRuleInterface
    {
        DB::table('uom_conversions')->insert([
            'from_unit' => $rule->getFromUnit(),
            'to_unit' => $rule->getToUnit(),
            'ratio' => $rule->getRatio(),
            'offset' => $rule->getOffset(),
            'is_bidirectional' => $rule->isBidirectional(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $rule;
    }

    public function deleteUnit(string $code): void
    {
        $unit = $this->findUnitByCode($code);
        
        if ($unit && $unit->isSystemUnit()) {
            throw SystemUnitProtectedException::forCode($code);
        }

        DB::table('uom_units')->where('code', $code)->delete();
    }

    public function deleteDimension(string $code): void
    {
        DB::table('uom_dimensions')->where('code', $code)->delete();
    }

    public function deleteConversion(string $fromUnitCode, string $toUnitCode): void
    {
        DB::table('uom_conversions')
            ->where('from_unit', $fromUnitCode)
            ->where('to_unit', $toUnitCode)
            ->delete();
    }
}
```

### Step 5: Register Service Provider

```php
<?php
// app/Providers/UomServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Uom\Contracts\UomRepositoryInterface;
use Nexus\Uom\Services\{UomConversionEngine, UomValidationService, UomManager};
use App\Repositories\LaravelUomRepository;

class UomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository
        $this->app->singleton(UomRepositoryInterface::class, LaravelUomRepository::class);

        // Bind validation service
        $this->app->singleton(UomValidationService::class, function ($app) {
            return new UomValidationService(
                $app->make(UomRepositoryInterface::class)
            );
        });

        // Bind conversion engine
        $this->app->singleton(UomConversionEngine::class, function ($app) {
            return new UomConversionEngine(
                $app->make(UomRepositoryInterface::class),
                $app->make(UomValidationService::class)
            );
        });

        // Bind UoM manager
        $this->app->singleton(UomManager::class, function ($app) {
            return new UomManager(
                $app->make(UomRepositoryInterface::class),
                $app->make(UomConversionEngine::class),
                $app->make(UomValidationService::class)
            );
        });
    }

    public function boot(): void
    {
        // Optionally seed default units
        $this->seedDefaultUnits();
    }

    private function seedDefaultUnits(): void
    {
        if ($this->app->environment('local', 'testing')) {
            // Seed basic units during development
            // Production should use migrations/seeders
        }
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\UomServiceProvider::class,
],
```

### Step 6: Create Database Seeder

```php
<?php
// database/seeders/UomSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Nexus\Uom\Services\UomManager;

class UomSeeder extends Seeder
{
    public function run(UomManager $manager): void
    {
        // Mass dimension
        $manager->createDimension('mass', 'Mass', 'kg', false, 'Weight and mass measurements');
        $manager->createUnit('kg', 'Kilogram', 'kg', 'mass', 'metric', true, true);
        $manager->createUnit('g', 'Gram', 'g', 'mass', 'metric', false, true);
        $manager->createUnit('lb', 'Pound', 'lb', 'mass', 'imperial', false, true);
        $manager->createUnit('oz', 'Ounce', 'oz', 'mass', 'imperial', false, true);
        $manager->createConversion('kg', 'g', 1000.0);
        $manager->createConversion('kg', 'lb', 2.20462);
        $manager->createConversion('lb', 'oz', 16.0);

        // Length dimension
        $manager->createDimension('length', 'Length', 'm', false, 'Distance and length measurements');
        $manager->createUnit('m', 'Meter', 'm', 'length', 'metric', true, true);
        $manager->createUnit('cm', 'Centimeter', 'cm', 'length', 'metric', false, true);
        $manager->createUnit('km', 'Kilometer', 'km', 'length', 'metric', false, true);
        $manager->createUnit('ft', 'Foot', 'ft', 'length', 'imperial', false, true);
        $manager->createUnit('in', 'Inch', 'in', 'length', 'imperial', false, true);
        $manager->createConversion('m', 'cm', 100.0);
        $manager->createConversion('m', 'km', 0.001);
        $manager->createConversion('m', 'ft', 3.28084);
        $manager->createConversion('ft', 'in', 12.0);

        // Temperature dimension (with offset support)
        $manager->createDimension('temperature', 'Temperature', 'kelvin', true, 'Temperature measurements');
        $manager->createUnit('kelvin', 'Kelvin', 'K', 'temperature', 'metric', true, true);
        $manager->createUnit('celsius', 'Celsius', '°C', 'temperature', 'metric', false, true);
        $manager->createUnit('fahrenheit', 'Fahrenheit', '°F', 'temperature', 'imperial', false, true);
        $manager->createConversion('celsius', 'fahrenheit', 1.8, 32.0);
        $manager->createConversion('celsius', 'kelvin', 1.0, 273.15);

        // Volume dimension
        $manager->createDimension('volume', 'Volume', 'liter', false, 'Volumetric measurements');
        $manager->createUnit('liter', 'Liter', 'L', 'volume', 'metric', true, true);
        $manager->createUnit('milliliter', 'Milliliter', 'mL', 'volume', 'metric', false, true);
        $manager->createUnit('gallon', 'Gallon', 'gal', 'volume', 'imperial', false, true);
        $manager->createConversion('liter', 'milliliter', 1000.0);
        $manager->createConversion('liter', 'gallon', 0.264172);

        // Quantity dimension (for packaging)
        $manager->createDimension('quantity', 'Quantity', 'each', false, 'Discrete quantity measurements');
        $manager->createUnit('each', 'Each', 'ea', 'quantity', null, true, true);
        $manager->createUnit('dozen', 'Dozen', 'doz', 'quantity', null, false, true);
        $manager->createUnit('case', 'Case', 'cs', 'quantity', null, false, false);
        $manager->createUnit('pallet', 'Pallet', 'plt', 'quantity', null, false, false);
        $manager->createConversion('dozen', 'each', 12.0);
        // Case and pallet conversions are product-specific, typically defined per product
    }
}
```

Run seeder:

```bash
php artisan db:seed --class=UomSeeder
```

### Step 7: Create Controllers

#### UoM Conversion Controller

```php
<?php
// app/Http/Controllers/UomConversionController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Uom\ValueObjects\Quantity;
use Nexus\Uom\Exceptions\UomException;

class UomConversionController extends Controller
{
    public function __construct(
        private readonly UomConversionEngine $engine
    ) {}

    /**
     * Convert a quantity from one unit to another
     *
     * POST /api/uom/convert
     * {
     *   "value": 100,
     *   "from_unit": "kg",
     *   "to_unit": "lb"
     * }
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|numeric',
            'from_unit' => 'required|string|max:50',
            'to_unit' => 'required|string|max:50',
        ]);

        try {
            $quantity = new Quantity(
                (float) $validated['value'],
                $validated['from_unit']
            );

            $converted = $quantity->convertTo($validated['to_unit'], $this->engine);

            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $quantity->toArray(),
                    'converted' => $converted->toArray(),
                    'formatted' => $converted->format('en_US', 2),
                ],
            ]);
        } catch (UomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Perform arithmetic on quantities
     *
     * POST /api/uom/calculate
     * {
     *   "operation": "add",
     *   "operand1": {"value": 100, "unit": "kg"},
     *   "operand2": {"value": 50, "unit": "lb"}
     * }
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'operation' => 'required|in:add,subtract,multiply,divide',
            'operand1' => 'required|array',
            'operand1.value' => 'required|numeric',
            'operand1.unit' => 'required|string|max:50',
            'operand2' => 'required',
        ]);

        try {
            $qty1 = new Quantity(
                (float) $validated['operand1']['value'],
                $validated['operand1']['unit']
            );

            $result = match ($validated['operation']) {
                'add' => $this->performAdd($qty1, $validated['operand2']),
                'subtract' => $this->performSubtract($qty1, $validated['operand2']),
                'multiply' => $this->performMultiply($qty1, $validated['operand2']),
                'divide' => $this->performDivide($qty1, $validated['operand2']),
            };

            return response()->json([
                'success' => true,
                'data' => [
                    'result' => $result->toArray(),
                    'formatted' => $result->format('en_US', 2),
                ],
            ]);
        } catch (UomException | \DivisionByZeroError $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    private function performAdd(Quantity $qty1, array $operand2): Quantity
    {
        $qty2 = new Quantity((float) $operand2['value'], $operand2['unit']);
        return $qty1->add($qty2, $this->engine);
    }

    private function performSubtract(Quantity $qty1, array $operand2): Quantity
    {
        $qty2 = new Quantity((float) $operand2['value'], $operand2['unit']);
        return $qty1->subtract($qty2, $this->engine);
    }

    private function performMultiply(Quantity $qty1, $operand2): Quantity
    {
        $scalar = is_array($operand2) ? (float) $operand2['value'] : (float) $operand2;
        return $qty1->multiply($scalar);
    }

    private function performDivide(Quantity $qty1, $operand2): Quantity
    {
        $scalar = is_array($operand2) ? (float) $operand2['value'] : (float) $operand2;
        return $qty1->divide($scalar);
    }
}
```

#### UoM Admin Controller

```php
<?php
// app/Http/Controllers/UomAdminController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Nexus\Uom\Services\UomManager;
use Nexus\Uom\Exceptions\UomException;

class UomAdminController extends Controller
{
    public function __construct(
        private readonly UomManager $manager
    ) {}

    /**
     * Create a new dimension
     */
    public function createDimension(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:uom_dimensions,code',
            'name' => 'required|string|max:255',
            'base_unit' => 'required|string|max:50',
            'allows_offset' => 'boolean',
            'description' => 'nullable|string',
        ]);

        try {
            $dimension = $this->manager->createDimension(
                code: $validated['code'],
                name: $validated['name'],
                baseUnit: $validated['base_unit'],
                allowsOffset: $validated['allows_offset'] ?? false,
                description: $validated['description'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $dimension->getCode(),
                    'name' => $dimension->getName(),
                    'base_unit' => $dimension->getBaseUnit(),
                    'allows_offset' => $dimension->allowsOffset(),
                ],
            ], 201);
        } catch (UomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a new unit
     */
    public function createUnit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:uom_units,code',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'dimension' => 'required|string|exists:uom_dimensions,code',
            'system' => 'nullable|string|max:50',
            'is_base_unit' => 'boolean',
        ]);

        try {
            $unit = $this->manager->createUnit(
                code: $validated['code'],
                name: $validated['name'],
                symbol: $validated['symbol'],
                dimension: $validated['dimension'],
                system: $validated['system'] ?? null,
                isBaseUnit: $validated['is_base_unit'] ?? false,
                isSystemUnit: false  // User-created units are not system units
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $unit->getCode(),
                    'name' => $unit->getName(),
                    'symbol' => $unit->getSymbol(),
                    'dimension' => $unit->getDimension(),
                ],
            ], 201);
        } catch (UomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a new conversion rule
     */
    public function createConversion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_unit' => 'required|string|exists:uom_units,code',
            'to_unit' => 'required|string|exists:uom_units,code',
            'ratio' => 'required|numeric|gt:0',
            'offset' => 'nullable|numeric',
            'is_bidirectional' => 'boolean',
        ]);

        try {
            $rule = $this->manager->createConversion(
                fromUnit: $validated['from_unit'],
                toUnit: $validated['to_unit'],
                ratio: (float) $validated['ratio'],
                offset: (float) ($validated['offset'] ?? 0.0),
                isBidirectional: $validated['is_bidirectional'] ?? true
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'from_unit' => $rule->getFromUnit(),
                    'to_unit' => $rule->getToUnit(),
                    'ratio' => $rule->getRatio(),
                    'offset' => $rule->getOffset(),
                ],
            ], 201);
        } catch (UomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### Step 8: Define Routes

```php
<?php
// routes/api.php

use App\Http\Controllers\{UomConversionController, UomAdminController};

Route::prefix('uom')->group(function () {
    // Public conversion endpoints
    Route::post('/convert', [UomConversionController::class, 'convert']);
    Route::post('/calculate', [UomConversionController::class, 'calculate']);

    // Admin endpoints (add auth middleware in production)
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/dimensions', [UomAdminController::class, 'createDimension']);
        Route::post('/units', [UomAdminController::class, 'createUnit']);
        Route::post('/conversions', [UomAdminController::class, 'createConversion']);
    });
});
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/uom:"*@dev"
```

### Step 2: Create Doctrine Entities

#### Dimension Entity

```php
<?php
// src/Entity/UomDimension.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ArrayCollection, Collection};

#[ORM\Entity]
#[ORM\Table(name: 'uom_dimensions')]
class UomDimension
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'string', length: 50)]
    private string $baseUnit;

    #[ORM\Column(type: 'boolean')]
    private bool $allowsOffset = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: UomUnit::class, mappedBy: 'dimension')]
    private Collection $units;

    public function __construct(
        string $code,
        string $name,
        string $baseUnit,
        bool $allowsOffset = false,
        ?string $description = null
    ) {
        $this->code = $code;
        $this->name = $name;
        $this->baseUnit = $baseUnit;
        $this->allowsOffset = $allowsOffset;
        $this->description = $description;
        $this->units = new ArrayCollection();
    }

    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getBaseUnit(): string { return $this->baseUnit; }
    public function allowsOffset(): bool { return $this->allowsOffset; }
    public function getDescription(): ?string { return $this->description; }
}
```

#### Unit Entity

```php
<?php
// src/Entity/UomUnit.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'uom_units')]
#[ORM\Index(columns: ['dimension_code'])]
#[ORM\Index(columns: ['system_code'])]
class UomUnit
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $code;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'string', length: 20)]
    private string $symbol;

    #[ORM\Column(type: 'string', length: 50)]
    private string $dimensionCode;

    #[ORM\ManyToOne(targetEntity: UomDimension::class, inversedBy: 'units')]
    #[ORM\JoinColumn(name: 'dimension_code', referencedColumnName: 'code')]
    private UomDimension $dimension;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $systemCode = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isBaseUnit = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isSystemUnit = true;

    public function __construct(
        string $code,
        string $name,
        string $symbol,
        UomDimension $dimension,
        ?string $systemCode = null,
        bool $isBaseUnit = false,
        bool $isSystemUnit = true
    ) {
        $this->code = $code;
        $this->name = $name;
        $this->symbol = $symbol;
        $this->dimension = $dimension;
        $this->dimensionCode = $dimension->getCode();
        $this->systemCode = $systemCode;
        $this->isBaseUnit = $isBaseUnit;
        $this->isSystemUnit = $isSystemUnit;
    }

    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getSymbol(): string { return $this->symbol; }
    public function getDimensionCode(): string { return $this->dimensionCode; }
    public function getSystemCode(): ?string { return $this->systemCode; }
    public function isBaseUnit(): bool { return $this->isBaseUnit; }
    public function isSystemUnit(): bool { return $this->isSystemUnit; }
}
```

### Step 3: Create Repository Implementation

```php
<?php
// src/Repository/SymfonyUomRepository.php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Nexus\Uom\Contracts\{
    UomRepositoryInterface,
    UnitInterface,
    DimensionInterface,
    ConversionRuleInterface
};
use Nexus\Uom\ValueObjects\{Unit, Dimension, ConversionRule};
use App\Entity\{UomDimension, UomUnit, UomConversion};

class SymfonyUomRepository implements UomRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    public function findUnitByCode(string $code): ?UnitInterface
    {
        $entity = $this->em->getRepository(UomUnit::class)->find($code);

        if ($entity === null) {
            return null;
        }

        return new Unit(
            code: $entity->getCode(),
            name: $entity->getName(),
            symbol: $entity->getSymbol(),
            dimension: $entity->getDimensionCode(),
            system: $entity->getSystemCode(),
            isBaseUnit: $entity->isBaseUnit(),
            isSystemUnit: $entity->isSystemUnit()
        );
    }

    public function findDimensionByCode(string $code): ?DimensionInterface
    {
        $entity = $this->em->getRepository(UomDimension::class)->find($code);

        if ($entity === null) {
            return null;
        }

        return new Dimension(
            code: $entity->getCode(),
            name: $entity->getName(),
            baseUnit: $entity->getBaseUnit(),
            allowsOffset: $entity->allowsOffset(),
            description: $entity->getDescription()
        );
    }

    // Implement other methods similarly...
}
```

### Step 4: Configure Services

```yaml
# config/services.yaml

services:
    _defaults:
        autowire: true
        autoconfigure: true

    # Repository
    Nexus\Uom\Contracts\UomRepositoryInterface:
        class: App\Repository\SymfonyUomRepository

    # Validation Service
    Nexus\Uom\Services\UomValidationService:
        arguments:
            $repository: '@Nexus\Uom\Contracts\UomRepositoryInterface'

    # Conversion Engine
    Nexus\Uom\Services\UomConversionEngine:
        arguments:
            $repository: '@Nexus\Uom\Contracts\UomRepositoryInterface'
            $validator: '@Nexus\Uom\Services\UomValidationService'

    # UoM Manager
    Nexus\Uom\Services\UomManager:
        arguments:
            $repository: '@Nexus\Uom\Contracts\UomRepositoryInterface'
            $conversionEngine: '@Nexus\Uom\Services\UomConversionEngine'
            $validationService: '@Nexus\Uom\Services\UomValidationService'
```

### Step 5: Create Controllers

```php
<?php
// src/Controller/UomConversionController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Annotation\Route;
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Uom\ValueObjects\Quantity;
use Nexus\Uom\Exceptions\UomException;

#[Route('/api/uom')]
class UomConversionController extends AbstractController
{
    public function __construct(
        private readonly UomConversionEngine $engine
    ) {}

    #[Route('/convert', methods: ['POST'])]
    public function convert(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['value'], $data['from_unit'], $data['to_unit'])) {
            return $this->json([
                'success' => false,
                'error' => 'Missing required fields',
            ], 400);
        }

        try {
            $quantity = new Quantity((float) $data['value'], $data['from_unit']);
            $converted = $quantity->convertTo($data['to_unit'], $this->engine);

            return $this->json([
                'success' => true,
                'data' => [
                    'original' => $quantity->toArray(),
                    'converted' => $converted->toArray(),
                    'formatted' => $converted->format('en_US', 2),
                ],
            ]);
        } catch (UomException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
```

---

## Database Schema

### Complete Schema Overview

```sql
-- Dimensions table
CREATE TABLE uom_dimensions (
    code VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    base_unit VARCHAR(50) NOT NULL,
    allows_offset BOOLEAN DEFAULT FALSE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_base_unit (base_unit)
);

-- Units table
CREATE TABLE uom_units (
    code VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    dimension_code VARCHAR(50) NOT NULL,
    system_code VARCHAR(50),
    is_base_unit BOOLEAN DEFAULT FALSE,
    is_system_unit BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dimension_code) REFERENCES uom_dimensions(code) ON DELETE CASCADE,
    INDEX idx_dimension (dimension_code),
    INDEX idx_system (system_code),
    INDEX idx_dimension_base (dimension_code, is_base_unit)
);

-- Conversion rules table
CREATE TABLE uom_conversions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    from_unit VARCHAR(50) NOT NULL,
    to_unit VARCHAR(50) NOT NULL,
    ratio DECIMAL(20, 10) NOT NULL,
    offset DECIMAL(20, 10) DEFAULT 0,
    is_bidirectional BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (from_unit) REFERENCES uom_units(code) ON DELETE CASCADE,
    FOREIGN KEY (to_unit) REFERENCES uom_units(code) ON DELETE CASCADE,
    UNIQUE KEY unique_conversion (from_unit, to_unit),
    INDEX idx_from_unit (from_unit),
    INDEX idx_to_unit (to_unit)
);
```

### Index Strategy

**Why These Indexes:**

1. **`idx_dimension` on `uom_units.dimension_code`**: Fast lookups for "get all units in dimension"
2. **`idx_system` on `uom_units.system_code`**: Fast lookups for "get all metric/imperial units"
3. **`idx_dimension_base` composite**: Quickly find base unit for a dimension
4. **`idx_from_unit` on `uom_conversions`**: Fast conversion path lookups
5. **`unique_conversion` on `(from_unit, to_unit)`**: Prevent duplicate conversion rules

---

## Common Patterns

### Pattern 1: Product Catalog with UoM

```php
use Nexus\Uom\ValueObjects\Quantity;

class Product
{
    private Quantity $baseQuantity;
    private array $alternateUnits = [];

    public function __construct(
        public string $sku,
        public string $name,
        float $baseValue,
        string $baseUnit,
        private UomConversionEngine $engine
    ) {
        $this->baseQuantity = new Quantity($baseValue, $baseUnit);
    }

    public function addAlternateUnit(string $unitCode, float $conversionFactor): void
    {
        $this->alternateUnits[$unitCode] = $conversionFactor;
    }

    public function getQuantityIn(string $unitCode): Quantity
    {
        return $this->baseQuantity->convertTo($unitCode, $this->engine);
    }

    public function price(Quantity $orderQty): float
    {
        // Convert order quantity to base unit
        $baseQty = $orderQty->convertTo($this->baseQuantity->getUnitCode(), $this->engine);
        
        // Calculate price (example: $10 per base unit)
        return $baseQty->getValue() * 10.0;
    }
}

// Usage
$product = new Product('WIDGET-001', 'Widget', 1.0, 'each', $engine);
$product->addAlternateUnit('case', 12.0);  // 1 case = 12 each
$product->addAlternateUnit('pallet', 1440.0);  // 1 pallet = 1440 each

$orderQty = new Quantity(5, 'case');
$totalPrice = $product->price($orderQty);
// Converts 5 cases → 60 each → $600
```

### Pattern 2: Temperature Monitoring

```php
class TemperatureSensor
{
    public function __construct(
        private string $sensorId,
        private UomConversionEngine $engine
    ) {}

    public function getCurrentTemp(): Quantity
    {
        // Sensor reads in Celsius
        $celsius = $this->readRawSensor();
        return new Quantity($celsius, 'celsius');
    }

    public function checkThreshold(Quantity $maxTemp): bool
    {
        $current = $this->getCurrentTemp();
        
        // Convert both to same unit for comparison
        $currentC = $current->convertTo('celsius', $this->engine);
        $maxC = $maxTemp->convertTo('celsius', $this->engine);
        
        return $currentC->lessThan($maxC, $this->engine);
    }

    private function readRawSensor(): float
    {
        // Simulate sensor reading
        return 22.5;
    }
}

// Usage
$sensor = new TemperatureSensor('SENSOR-001', $engine);
$maxTemp = new Quantity(75, 'fahrenheit');  // Max 75°F

if (!$sensor->checkThreshold($maxTemp)) {
    echo "Temperature exceeded threshold!";
}
```

### Pattern 3: Recipe Scaling

```php
class Recipe
{
    private array $ingredients = [];

    public function __construct(
        public string $name,
        private UomConversionEngine $engine
    ) {}

    public function addIngredient(string $name, Quantity $qty): void
    {
        $this->ingredients[$name] = $qty;
    }

    public function scale(float $factor): array
    {
        return array_map(
            fn($qty) => $qty->multiply($factor),
            $this->ingredients
        );
    }

    public function convertTo(string $unitCode): array
    {
        $converted = [];
        foreach ($this->ingredients as $name => $qty) {
            try {
                $converted[$name] = $qty->convertTo($unitCode, $this->engine);
            } catch (IncompatibleUnitException $e) {
                // Keep original if conversion not possible
                $converted[$name] = $qty;
            }
        }
        return $converted;
    }
}

// Usage
$recipe = new Recipe('Chocolate Cake', $engine);
$recipe->addIngredient('flour', new Quantity(500, 'g'));
$recipe->addIngredient('sugar', new Quantity(200, 'g'));
$recipe->addIngredient('milk', new Quantity(250, 'milliliter'));

// Scale recipe for 10 servings (from 6)
$scaled = $recipe->scale(10 / 6);

// Convert to cups for US market
$usRecipe = $recipe->convertTo('cup');
```

---

## Performance Optimization

### 1. Conversion Path Caching

The engine automatically caches conversion paths. Monitor cache effectiveness:

```php
use Illuminate\Support\Facades\Cache;

class CachedUomConversionEngine extends UomConversionEngine
{
    public function convert(float $value, string $fromUnit, string $toUnit): float
    {
        $cacheKey = "uom_conversion:{$fromUnit}:{$toUnit}";
        
        $ratio = Cache::remember($cacheKey, 3600, function () use ($fromUnit, $toUnit) {
            // First time: compute and cache
            return parent::convert(1.0, $fromUnit, $toUnit);
        });
        
        return $value * $ratio;
    }
}
```

### 2. Eager Load Conversions

For batch operations, eager load all conversions for a dimension:

```php
use Illuminate\Support\Facades\DB;

function preloadDimensionConversions(string $dimension): void
{
    $conversions = DB::table('uom_conversions as c')
        ->join('uom_units as u', 'c.from_unit', '=', 'u.code')
        ->where('u.dimension_code', $dimension)
        ->get();
    
    // Store in application cache
    Cache::put("dimension_conversions:{$dimension}", $conversions, 3600);
}

// Preload mass conversions during bootstrap
preloadDimensionConversions('mass');
```

### 3. Database Query Optimization

Use database views for common queries:

```sql
-- View for all metric units
CREATE VIEW vw_metric_units AS
SELECT u.*, d.name as dimension_name
FROM uom_units u
JOIN uom_dimensions d ON u.dimension_code = d.code
WHERE u.system_code = 'metric';

-- View for conversion paths
CREATE VIEW vw_conversion_paths AS
SELECT 
    c.from_unit,
    c.to_unit,
    c.ratio,
    u1.dimension_code as dimension,
    c.ratio * c2.ratio as compound_ratio
FROM uom_conversions c
JOIN uom_units u1 ON c.from_unit = u1.code
LEFT JOIN uom_conversions c2 ON c.to_unit = c2.from_unit;
```

---

## Troubleshooting

### Issue 1: Slow Conversion Performance

**Symptoms:**
- Conversions taking > 50ms
- High database query count

**Solutions:**

```php
// 1. Enable query logging to identify bottleneck
DB::enableQueryLog();
$result = $engine->convert(100, 'oz', 'kg');
dd(DB::getQueryLog());

// 2. Pre-warm conversion cache
$commonPairs = [
    ['kg', 'lb'], ['kg', 'g'], ['m', 'ft'],
    ['liter', 'gallon'], ['celsius', 'fahrenheit']
];

foreach ($commonPairs as [$from, $to]) {
    Cache::put("uom:{$from}:{$to}", $engine->convert(1.0, $from, $to), 86400);
}

// 3. Use Redis for caching
// config/cache.php
'stores' => [
    'uom' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

### Issue 2: Circular Conversion Errors

**Symptoms:**
```
CircularConversionException: Circular conversion path detected
```

**Solutions:**

```php
// Identify the cycle
$validator = app(UomValidationService::class);

try {
    $manager->createConversion('a', 'b', 2.0);
    $manager->createConversion('b', 'c', 3.0);
    $manager->createConversion('c', 'a', 0.5);  // Creates cycle!
} catch (CircularConversionException $e) {
    // Fix: Remove offending conversion
    $manager->deleteConversion('c', 'a');
    
    // Or: Use base unit pattern
    $manager->createConversion('base', 'a', 2.0);
    $manager->createConversion('base', 'b', 4.0);
    $manager->createConversion('base', 'c', 12.0);
}
```

### Issue 3: Missing Conversion Paths

**Symptoms:**
```
ConversionPathNotFoundException: No conversion path from 'oz' to 'kg'
```

**Solutions:**

```php
// 1. Check if units exist
$ozUnit = $repository->findUnitByCode('oz');
$kgUnit = $repository->findUnitByCode('kg');

if ($ozUnit === null) {
    $manager->createUnit('oz', 'Ounce', 'oz', 'mass', 'imperial');
}

// 2. Check dimension compatibility
if ($ozUnit->getDimension() !== $kgUnit->getDimension()) {
    throw new IncompatibleUnitException("oz and kg must be in same dimension");
}

// 3. Add missing conversion
$manager->createConversion('oz', 'kg', 0.0283495);

// OR create via base unit
$manager->createConversion('kg', 'oz', 35.274);  // Uses base unit
```

### Issue 4: Incorrect Conversion Results

**Symptoms:**
- 1 kg → lb returns 0.45 instead of 2.20
- Temperature conversions are wrong

**Solutions:**

```php
// 1. Verify ratio direction
$rule = $repository->findConversion('kg', 'lb');
echo "Ratio: " . $rule->getRatio();  // Should be 2.20462, not 0.45

// If wrong, delete and recreate
$manager->deleteConversion('kg', 'lb');
$manager->createConversion('kg', 'lb', 2.20462);  // Correct ratio

// 2. Check offset for temperature
$celsiusToFahrenheit = $repository->findConversion('celsius', 'fahrenheit');
echo "Ratio: " . $celsiusToFahrenheit->getRatio();    // Should be 1.8
echo "Offset: " . $celsiusToFahrenheit->getOffset();  // Should be 32.0

// Test conversion
$result = $engine->convert(25, 'celsius', 'fahrenheit');
// Expected: (25 × 1.8) + 32 = 77°F
echo "25°C = {$result}°F";
```

---

## Testing Your Integration

### Unit Tests

```php
<?php
// tests/Unit/UomConversionTest.php

namespace Tests\Unit;

use Tests\TestCase;
use Nexus\Uom\Services\UomConversionEngine;
use Nexus\Uom\ValueObjects\Quantity;

class UomConversionTest extends TestCase
{
    private UomConversionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(UomConversionEngine::class);
    }

    public function test_converts_kg_to_lb(): void
    {
        $quantity = new Quantity(100, 'kg');
        $pounds = $quantity->convertTo('lb', $this->engine);

        $this->assertEquals(220.462, $pounds->getValue(), delta: 0.001);
        $this->assertEquals('lb', $pounds->getUnitCode());
    }

    public function test_converts_celsius_to_fahrenheit(): void
    {
        $celsius = new Quantity(25, 'celsius');
        $fahrenheit = $celsius->convertTo('fahrenheit', $this->engine);

        $this->assertEquals(77.0, $fahrenheit->getValue(), delta: 0.1);
    }

    public function test_arithmetic_with_mixed_units(): void
    {
        $qty1 = new Quantity(100, 'kg');
        $qty2 = new Quantity(50, 'lb');

        $total = $qty1->add($qty2, $this->engine);

        $this->assertEquals(122.68, $total->getValue(), delta: 0.01);
        $this->assertEquals('kg', $total->getUnitCode());
    }
}
```

### Integration Tests

```php
<?php
// tests/Feature/UomApiTest.php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UomApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversion_endpoint(): void
    {
        $response = $this->postJson('/api/uom/convert', [
            'value' => 100,
            'from_unit' => 'kg',
            'to_unit' => 'lb',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'converted' => [
                        'value' => 220.462,
                        'unit_code' => 'lb',
                    ],
                ],
            ]);
    }

    public function test_creates_dimension_via_api(): void
    {
        $response = $this->postJson('/api/uom/dimensions', [
            'code' => 'custom',
            'name' => 'Custom Dimension',
            'base_unit' => 'custom_unit',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('uom_dimensions', [
            'code' => 'custom',
        ]);
    }
}
```

---

**Last Updated:** November 28, 2024  
**Package Version:** 1.0.0-dev  
**Minimum PHP:** 8.3+
