# Integration Guide: Procurement

This guide shows how to integrate the Procurement package into your application.

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/procurement:"*@dev"
```

### Step 2: Create Database Migrations

#### Requisitions Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('number')->unique();
            $table->string('requester_id', 26)->index();
            $table->text('description')->nullable();
            $table->string('department')->nullable();
            $table->string('status')->default('draft')->index();
            $table->decimal('total_estimate', 19, 4)->default(0);
            $table->string('approved_by', 26)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_by', 26)->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_converted')->default(false)->index();
            $table->string('converted_po_id', 26)->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'requester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
```

#### Requisition Lines Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_lines', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('requisition_id', 26);
            $table->integer('line_number');
            $table->string('item_code')->index();
            $table->text('description');
            $table->decimal('quantity', 19, 4);
            $table->string('unit');
            $table->decimal('estimated_unit_price', 19, 4);
            $table->decimal('line_total', 19, 4);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('requisition_id')
                ->references('id')
                ->on('requisitions')
                ->onDelete('cascade');
            
            $table->unique(['requisition_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_lines');
    }
};
```

#### Purchase Orders Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('number')->unique();
            $table->string('vendor_id', 26)->index();
            $table->string('creator_id', 26)->index();
            $table->string('requisition_id', 26)->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->string('po_type')->default('standard')->index(); // standard|blanket|release
            $table->string('blanket_po_id', 26)->nullable()->index();
            $table->decimal('total_amount', 19, 4)->default(0);
            $table->decimal('total_committed_value', 19, 4)->nullable(); // For blanket POs
            $table->decimal('total_released_value', 19, 4)->nullable();
            $table->string('currency', 3)->default('MYR');
            $table->date('expected_delivery_date')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('approved_by', 26)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('requisition_id')
                ->references('id')
                ->on('requisitions')
                ->onDelete('set null');
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
```

#### Purchase Order Lines Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('purchase_order_id', 26);
            $table->string('line_reference')->unique(); // e.g., PO-2024-001-L001
            $table->integer('line_number');
            $table->string('requisition_line_id', 26)->nullable();
            $table->string('item_code')->index();
            $table->text('description');
            $table->decimal('quantity', 19, 4);
            $table->string('unit');
            $table->decimal('unit_price', 19, 4);
            $table->decimal('line_total', 19, 4);
            $table->decimal('quantity_received', 19, 4)->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('cascade');
            
            $table->unique(['purchase_order_id', 'line_number']);
            $table->index('line_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
```

#### Goods Receipt Notes Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('number')->unique();
            $table->string('purchase_order_id', 26);
            $table->string('receiver_id', 26)->index();
            $table->date('received_date');
            $table->string('status')->default('draft')->index();
            $table->string('warehouse_location')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_authorizer_id', 26)->nullable();
            $table->timestamp('payment_authorized_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('purchase_orders')
                ->onDelete('cascade');
            
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};
```

#### Goods Receipt Lines Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('goods_receipt_note_id', 26);
            $table->integer('line_number');
            $table->string('po_line_reference'); // Links to purchase_order_lines.line_reference
            $table->decimal('quantity', 19, 4);
            $table->string('unit');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('goods_receipt_note_id')
                ->references('id')
                ->on('goods_receipt_notes')
                ->onDelete('cascade');
            
            $table->index('po_line_reference');
            $table->unique(['goods_receipt_note_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
```

### Step 3: Create Eloquent Models

#### Requisition Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Procurement\Contracts\RequisitionInterface;

class Requisition extends Model implements RequisitionInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'number',
        'requester_id',
        'description',
        'department',
        'status',
        'total_estimate',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'is_converted',
        'converted_po_id',
        'converted_at',
        'metadata',
    ];

    protected $casts = [
        'total_estimate' => 'decimal:4',
        'is_converted' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'converted_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Interface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getRequisitionNumber(): string
    {
        return $this->number;
    }

    public function getRequesterId(): string
    {
        return $this->requester_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalEstimate(): float
    {
        return (float) $this->total_estimate;
    }

    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getApprovedBy(): ?string
    {
        return $this->approved_by;
    }

    public function getApprovedAt(): ?\DateTimeImmutable
    {
        return $this->approved_at 
            ? \DateTimeImmutable::createFromMutable($this->approved_at)
            : null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->created_at);
    }

    // Relationships
    public function lines()
    {
        return $this->hasMany(RequisitionLine::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
```

#### PurchaseOrder Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Procurement\Contracts\PurchaseOrderInterface;

class PurchaseOrder extends Model implements PurchaseOrderInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'number',
        'vendor_id',
        'creator_id',
        'requisition_id',
        'status',
        'po_type',
        'blanket_po_id',
        'total_amount',
        'total_committed_value',
        'total_released_value',
        'currency',
        'expected_delivery_date',
        'valid_from',
        'valid_until',
        'payment_terms',
        'notes',
        'approved_by',
        'approved_at',
        'released_at',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:4',
        'total_committed_value' => 'decimal:4',
        'total_released_value' => 'decimal:4',
        'expected_delivery_date' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Interface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getPoNumber(): string
    {
        return $this->number;
    }

    public function getVendorId(): string
    {
        return $this->vendor_id;
    }

    public function getRequisitionId(): ?string
    {
        return $this->requisition_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalAmount(): float
    {
        return (float) $this->total_amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getLines(): array
    {
        return $this->lines->all();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->released_at 
            ? \DateTimeImmutable::createFromMutable($this->released_at)
            : null;
    }

    // Relationships
    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceiptNote::class);
    }
}
```

### Step 4: Create Repository Implementations

```php
<?php

namespace App\Repositories;

use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Exceptions\RequisitionNotFoundException;
use App\Models\Requisition;

final readonly class EloquentRequisitionRepository implements RequisitionRepositoryInterface
{
    public function findById(string $id): RequisitionInterface
    {
        $requisition = Requisition::with('lines')->find($id);
        
        if (!$requisition) {
            throw new RequisitionNotFoundException("Requisition not found: {$id}");
        }

        return $requisition;
    }

    public function findByTenant(string $tenantId): array
    {
        return Requisition::where('tenant_id', $tenantId)
            ->with('lines')
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return Requisition::where('tenant_id', $tenantId)
            ->where('status', $status)
            ->with('lines')
            ->get()
            ->all();
    }

    public function create(array $data): RequisitionInterface
    {
        $requisition = Requisition::create([
            'tenant_id' => $data['tenant_id'],
            'number' => $data['number'],
            'requester_id' => $data['requester_id'],
            'description' => $data['description'] ?? null,
            'department' => $data['department'] ?? null,
            'status' => 'draft',
            'total_estimate' => 0,
        ]);

        $totalEstimate = 0;
        foreach ($data['lines'] ?? [] as $index => $lineData) {
            $lineTotal = $lineData['quantity'] * $lineData['estimated_unit_price'];
            $totalEstimate += $lineTotal;

            $requisition->lines()->create([
                'line_number' => $index + 1,
                'item_code' => $lineData['item_code'],
                'description' => $lineData['description'],
                'quantity' => $lineData['quantity'],
                'unit' => $lineData['unit'],
                'estimated_unit_price' => $lineData['estimated_unit_price'],
                'line_total' => $lineTotal,
            ]);
        }

        $requisition->update(['total_estimate' => $totalEstimate]);
        $requisition->load('lines');

        return $requisition;
    }

    public function updateStatus(string $id, string $status): RequisitionInterface
    {
        $requisition = $this->findById($id);
        $requisition->update(['status' => $status]);
        return $requisition->fresh();
    }

    public function markAsConverted(string $id, string $poId): RequisitionInterface
    {
        $requisition = $this->findById($id);
        $requisition->update([
            'status' => 'converted',
            'is_converted' => true,
            'converted_po_id' => $poId,
            'converted_at' => new \DateTimeImmutable(), // Framework-agnostic
        ]);
        return $requisition->fresh();
    }
}
```

### Step 5: Create Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Procurement\Contracts\{
    ProcurementManagerInterface,
    RequisitionRepositoryInterface,
    PurchaseOrderRepositoryInterface,
    GoodsReceiptRepositoryInterface,
    VendorQuoteRepositoryInterface
};
use Nexus\Procurement\Services\{
    ProcurementManager,
    RequisitionManager,
    PurchaseOrderManager,
    GoodsReceiptManager,
    MatchingEngine,
    VendorQuoteManager
};
use App\Repositories\{
    EloquentRequisitionRepository,
    EloquentPurchaseOrderRepository,
    EloquentGoodsReceiptRepository,
    EloquentVendorQuoteRepository
};
use Psr\Log\LoggerInterface;

class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/procurement.php', 'procurement'
        );

        // Repositories
        $this->app->singleton(
            RequisitionRepositoryInterface::class, 
            EloquentRequisitionRepository::class
        );
        $this->app->singleton(
            PurchaseOrderRepositoryInterface::class, 
            EloquentPurchaseOrderRepository::class
        );
        $this->app->singleton(
            GoodsReceiptRepositoryInterface::class, 
            EloquentGoodsReceiptRepository::class
        );
        $this->app->singleton(
            VendorQuoteRepositoryInterface::class, 
            EloquentVendorQuoteRepository::class
        );

        // Matching Engine
        $this->app->singleton(MatchingEngine::class, function ($app) {
            return new MatchingEngine(
                logger: $app->make(LoggerInterface::class),
                quantityTolerancePercent: config('procurement.quantity_tolerance_percent', 5.0),
                priceTolerancePercent: config('procurement.price_tolerance_percent', 5.0)
            );
        });

        // Managers
        $this->app->singleton(RequisitionManager::class);
        $this->app->singleton(PurchaseOrderManager::class);
        $this->app->singleton(GoodsReceiptManager::class);
        $this->app->singleton(VendorQuoteManager::class);

        // Main interface
        $this->app->singleton(
            ProcurementManagerInterface::class, 
            ProcurementManager::class
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/procurement.php' => config_path('procurement.php'),
        ], 'procurement-config');
    }
}
```

### Step 6: Register Service Provider

Add to `bootstrap/providers.php` (Laravel 11+):

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ProcurementServiceProvider::class,
];
```

Or in `config/app.php` (Laravel 10):

```php
'providers' => [
    // ...
    App\Providers\ProcurementServiceProvider::class,
],
```

### Step 7: Use in Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Exceptions\{
    InvalidRequisitionDataException,
    RequisitionNotFoundException,
    UnauthorizedApprovalException
};

class RequisitionController extends Controller
{
    public function __construct(
        private readonly ProcurementManagerInterface $procurement
    ) {}

    public function index(Request $request)
    {
        $requisitions = $this->procurement->getRequisitionsForTenant(
            $request->user()->tenant_id
        );

        return response()->json(['data' => $requisitions]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'department' => 'required|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_code' => 'required|string',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric|min:0.01',
            'lines.*.unit' => 'required|string',
            'lines.*.estimated_unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $requisition = $this->procurement->createRequisition(
                tenantId: $request->user()->tenant_id,
                requesterId: $request->user()->id,
                data: [
                    'number' => 'REQ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    ...$validated,
                ]
            );

            return response()->json([
                'message' => 'Requisition created',
                'data' => $requisition,
            ], 201);

        } catch (InvalidRequisitionDataException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function approve(Request $request, string $id)
    {
        try {
            $requisition = $this->procurement->approveRequisition(
                requisitionId: $id,
                approverId: $request->user()->id
            );

            return response()->json([
                'message' => 'Requisition approved',
                'data' => $requisition,
            ]);

        } catch (RequisitionNotFoundException $e) {
            return response()->json(['error' => 'Requisition not found'], 404);
        } catch (UnauthorizedApprovalException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/procurement:"*@dev"
```

### Step 2: Create Doctrine Entities

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nexus\Procurement\Contracts\RequisitionInterface;

#[ORM\Entity(repositoryClass: RequisitionRepository::class)]
#[ORM\Table(name: 'requisitions')]
class Requisition implements RequisitionInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(type: 'string', length: 26)]
    private string $tenantId;

    #[ORM\Column(type: 'string', unique: true)]
    private string $number;

    #[ORM\Column(type: 'string', length: 26)]
    private string $requesterId;

    #[ORM\Column(type: 'string')]
    private string $status = 'draft';

    #[ORM\Column(type: 'decimal', precision: 19, scale: 4)]
    private float $totalEstimate = 0;

    #[ORM\OneToMany(mappedBy: 'requisition', targetEntity: RequisitionLine::class, cascade: ['persist'])]
    private Collection $lines;

    public function __construct()
    {
        $this->id = (new \Symfony\Component\Uid\Ulid())->toBase32();
        $this->lines = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRequisitionNumber(): string
    {
        return $this->number;
    }

    public function getRequesterId(): string
    {
        return $this->requesterId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTotalEstimate(): float
    {
        return $this->totalEstimate;
    }

    public function getLines(): array
    {
        return $this->lines->toArray();
    }

    // ... implement other interface methods
}
```

### Step 3: Create Repository

```php
<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Nexus\Procurement\Contracts\RequisitionRepositoryInterface;
use Nexus\Procurement\Contracts\RequisitionInterface;
use Nexus\Procurement\Exceptions\RequisitionNotFoundException;
use App\Entity\Requisition;

class RequisitionRepository extends ServiceEntityRepository implements RequisitionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Requisition::class);
    }

    public function findById(string $id): RequisitionInterface
    {
        $requisition = $this->find($id);
        
        if (!$requisition) {
            throw new RequisitionNotFoundException("Requisition not found: {$id}");
        }

        return $requisition;
    }

    public function findByTenant(string $tenantId): array
    {
        return $this->findBy(['tenantId' => $tenantId], ['createdAt' => 'DESC']);
    }

    public function create(array $data): RequisitionInterface
    {
        $em = $this->getEntityManager();
        
        $requisition = new Requisition();
        $requisition->setTenantId($data['tenant_id']);
        $requisition->setNumber($data['number']);
        $requisition->setRequesterId($data['requester_id']);
        
        $em->persist($requisition);
        $em->flush();

        return $requisition;
    }
}
```

### Step 4: Configure Services

`config/services.yaml`:

```yaml
services:
    # Repositories
    Nexus\Procurement\Contracts\RequisitionRepositoryInterface:
        class: App\Repository\RequisitionRepository

    Nexus\Procurement\Contracts\PurchaseOrderRepositoryInterface:
        class: App\Repository\PurchaseOrderRepository

    Nexus\Procurement\Contracts\GoodsReceiptRepositoryInterface:
        class: App\Repository\GoodsReceiptRepository

    # Matching Engine
    Nexus\Procurement\Services\MatchingEngine:
        arguments:
            $logger: '@logger'
            $quantityTolerancePercent: '%env(float:PROCUREMENT_QUANTITY_TOLERANCE)%'
            $priceTolerancePercent: '%env(float:PROCUREMENT_PRICE_TOLERANCE)%'

    # Managers
    Nexus\Procurement\Services\RequisitionManager:
        autowire: true

    Nexus\Procurement\Services\PurchaseOrderManager:
        autowire: true

    Nexus\Procurement\Services\GoodsReceiptManager:
        autowire: true

    Nexus\Procurement\Services\ProcurementManager:
        autowire: true

    Nexus\Procurement\Contracts\ProcurementManagerInterface:
        alias: Nexus\Procurement\Services\ProcurementManager
```

### Step 5: Use in Controller

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\Procurement\Contracts\ProcurementManagerInterface;
use Nexus\Procurement\Exceptions\UnauthorizedApprovalException;

#[Route('/api/requisitions')]
class RequisitionController extends AbstractController
{
    public function __construct(
        private readonly ProcurementManagerInterface $procurement
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $requisition = $this->procurement->createRequisition(
            tenantId: $user->getTenantId(),
            requesterId: $user->getId(),
            data: $data
        );

        return $this->json(['data' => $requisition], 201);
    }

    #[Route('/{id}/approve', methods: ['POST'])]
    public function approve(string $id): JsonResponse
    {
        try {
            $requisition = $this->procurement->approveRequisition(
                requisitionId: $id,
                approverId: $this->getUser()->getId()
            );

            return $this->json(['data' => $requisition]);
        } catch (UnauthorizedApprovalException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }
}
```

---

## Common Patterns

### Pattern 1: Dependency Injection

Always inject interfaces, never concrete classes:

```php
// ✅ CORRECT
public function __construct(
    private readonly ProcurementManagerInterface $procurement
) {}

// ❌ WRONG
public function __construct(
    private readonly ProcurementManager $procurement  // Concrete class!
) {}
```

### Pattern 2: Multi-Tenancy

All repositories should automatically scope by tenant:

```php
public function findAll(): array
{
    $tenantId = $this->tenantContext->getCurrentTenantId();
    
    return $this->model
        ->where('tenant_id', $tenantId)
        ->get()
        ->all();
}
```

### Pattern 3: Exception Handling

```php
use Nexus\Procurement\Exceptions\{
    RequisitionNotFoundException,
    UnauthorizedApprovalException
};

try {
    $req = $this->procurement->approveRequisition($id, $approverId);
} catch (RequisitionNotFoundException $e) {
    return response()->json(['error' => 'Not found'], 404);
} catch (UnauthorizedApprovalException $e) {
    return response()->json(['error' => $e->getMessage()], 403);
}
```

---

## Performance Optimization

### Database Indexes

Always index foreign keys and tenant_id:

```php
$table->string('tenant_id', 26)->index();
$table->string('requisition_id', 26)->index();
$table->string('line_reference')->index();
```

### Eager Loading

Use eager loading to avoid N+1 queries:

```php
public function findById(string $id): RequisitionInterface
{
    return Requisition::with('lines')->findOrFail($id);
}
```

### Batch Matching

For large invoices, use batch matching:

```php
$result = $matchingEngine->performBatchMatch($matchSet);
// Single call for 100+ lines, <500ms
```

---

## Testing

### Unit Testing Package Logic

```php
use Nexus\Procurement\Services\MatchingEngine;
use Nexus\Procurement\Contracts\{
    PurchaseOrderLineInterface,
    GoodsReceiptLineInterface
};
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MatchingEngineTest extends TestCase
{
    private MatchingEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new MatchingEngine(
            logger: new NullLogger(),
            quantityTolerancePercent: 5.0,
            priceTolerancePercent: 5.0
        );
    }

    public function test_exact_match_returns_approved(): void
    {
        $poLine = $this->createMock(PurchaseOrderLineInterface::class);
        $poLine->method('getLineReference')->willReturn('PO-001-L001');
        $poLine->method('getQuantity')->willReturn(10.0);
        $poLine->method('getUnitPrice')->willReturn(25.0);

        $grnLine = $this->createMock(GoodsReceiptLineInterface::class);
        $grnLine->method('getPoLineReference')->willReturn('PO-001-L001');
        $grnLine->method('getQuantity')->willReturn(10.0);

        $result = $this->engine->performThreeWayMatch($poLine, $grnLine, [
            'quantity' => 10.0,
            'unit_price' => 25.0,
            'line_total' => 250.0,
        ]);

        $this->assertTrue($result['matched']);
        $this->assertStringContainsString('APPROVE', $result['recommendation']);
    }
}
```

### Integration Testing (Laravel)

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_requisition_via_api(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/requisitions', [
                'description' => 'Test requisition',
                'department' => 'IT',
                'lines' => [
                    [
                        'item_code' => 'LAPTOP-001',
                        'description' => 'Dell Laptop',
                        'quantity' => 5,
                        'unit' => 'unit',
                        'estimated_unit_price' => 1500.00,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'number', 'status'],
            ]);

        $this->assertDatabaseHas('requisitions', [
            'requester_id' => $user->id,
            'status' => 'draft',
        ]);
    }
}
```

---

**Last Updated:** 2025-11-26  
**Maintained By:** Nexus Architecture Team
