# Integration Guide: Nexus\\Sequencing

This guide demonstrates how to wire Nexus\\Sequencing into common frameworks while keeping the package's core purely framework agnostic.

---

## Laravel Integration

### 1. Install and Publish Dependencies
```bash
composer require nexus/sequencing:"*@dev"
```

### 2. Database Schema (Example)
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('name');
            $table->string('scope_id')->nullable();
            $table->string('pattern');
            $table->string('gap_policy');
            $table->string('overflow_behavior');
            $table->string('reset_period');
            $table->boolean('locked')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['name', 'scope_id']);
        });

        Schema::create('sequence_counters', function (Blueprint $table) {
            $table->string('sequence_id', 26)->primary();
            $table->unsignedBigInteger('current_value')->default(0);
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();
        });
    }
};
```

### 3. Repository Implementations
```php
namespace App\Sequencing\Repositories;

use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\Sequencing\Contracts\SequenceInterface;
use App\Models\Sequence;

final readonly class DbSequenceRepository implements SequenceRepositoryInterface
{
    public function findByNameAndScope(string $sequenceName, ?string $scopeIdentifier = null): SequenceInterface
    {
        $record = Sequence::query()
            ->where('name', $sequenceName)
            ->where('scope_id', $scopeIdentifier)
            ->firstOrFail();

        return $record->toContract(); // Map Eloquent model to DTO implementing SequenceInterface
    }

    public function lock(SequenceInterface $sequence): void
    {
        Sequence::query()
            ->whereKey($sequence->getId())
            ->update(['locked' => true]);
    }

    public function unlock(SequenceInterface $sequence): void
    {
        Sequence::query()
            ->whereKey($sequence->getId())
            ->update(['locked' => false]);
    }

    public function save(SequenceInterface $sequence): void
    {
        Sequence::updateOrCreate(
            ['id' => $sequence->getId()],
            [
                'name' => $sequence->getName(),
                'scope_id' => $sequence->getScopeIdentifier(),
                'pattern' => $sequence->getPattern(),
                'gap_policy' => $sequence->getGapPolicy(),
                'overflow_behavior' => $sequence->getOverflowBehavior(),
                'reset_period' => $sequence->getResetPeriod(),
                'metadata' => $sequence->getMetadata(),
            ],
        );
    }
}
```

Provide analogous implementations for `CounterRepositoryInterface`, `ReservationRepositoryInterface`, `GapRepositoryInterface`, and `SequenceAuditInterface` to complete the storage layer.

### 4. Service Provider Binding
```php
use App\Sequencing\Repositories\DbSequenceRepository;
use Nexus\Sequencing\Contracts\SequenceRepositoryInterface;
use Nexus\Sequencing\Services\SequenceManager;

final class SequencingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SequenceRepositoryInterface::class, DbSequenceRepository::class);
        // Repeat for other repository interfaces...

        $this->app->singleton(SequenceManager::class, function ($app) {
            return new SequenceManager(
                $app->make(SequenceRepositoryInterface::class),
                $app->make(CounterRepositoryInterface::class),
                $app->make(GapRepositoryInterface::class),
                $app->make(PatternParser::class),
                $app->make(CounterService::class),
                $app->make(SequenceAuditInterface::class),
            );
        });
    }
}
```

### 5. Usage Inside Controllers/Jobs
```php
final class InvoiceController
{
    public function __construct(private SequenceManager $sequenceManager) {}

    public function store(Request $request): JsonResponse
    {
        $number = $this->sequenceManager->generate('invoice_number', tenant()->id, [
            'DEPARTMENT' => $request->input('department', 'SALES'),
        ]);

        // ... persist invoice using $number
    }
}
```

---

## Symfony Integration

### 1. Install Package
```bash
composer require nexus/sequencing:"*@dev"
```

### 2. Doctrine Entity (Example)
```php
use Doctrine\ORM\Mapping as ORM;
use Nexus\Sequencing\Contracts\SequenceInterface;

#[ORM\Entity]
#[ORM\Table(name: 'sequences')]
class SequenceEntity implements SequenceInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(nullable: true)]
    private ?string $scopeId = null;

    // ...other fields...

    public function getName(): string
    {
        return $this->name;
    }

    // implement remaining contract methods
}
```

### 3. Service Wiring (`config/services.yaml`)
```yaml
services:
    App\Sequencing\Repositories\DoctrineSequenceRepository:
        arguments:
            - '@doctrine.orm.entity_manager'

    Nexus\Sequencing\Contracts\SequenceRepositoryInterface: '@App\Sequencing\Repositories\DoctrineSequenceRepository'
    Nexus\Sequencing\Contracts\CounterRepositoryInterface: '@App\Sequencing\Repositories\DoctrineCounterRepository'
    Nexus\Sequencing\Contracts\GapRepositoryInterface: '@App\Sequencing\Repositories\DoctrineGapRepository'
    Nexus\Sequencing\Contracts\ReservationRepositoryInterface: '@App\Sequencing\Repositories\DoctrineReservationRepository'
    Nexus\Sequencing\Contracts\SequenceAuditInterface: '@App\Sequencing\Auditing\AuditLogger'

    Nexus\Sequencing\Services\SequenceManager:
        arguments:
            - '@Nexus\Sequencing\Contracts\SequenceRepositoryInterface'
            - '@Nexus\Sequencing\Contracts\CounterRepositoryInterface'
            - '@Nexus\Sequencing\Contracts\GapRepositoryInterface'
            - '@Nexus\Sequencing\Services\PatternParser'
            - '@Nexus\Sequencing\Services\CounterService'
            - '@Nexus\Sequencing\Contracts\SequenceAuditInterface'
```

### 4. Controller Usage
```php
use Nexus\Sequencing\Services\SequenceManager;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class PurchaseOrderController
{
    public function __construct(private SequenceManager $sequenceManager) {}

    public function create(): JsonResponse
    {
        $poNumber = $this->sequenceManager->generate('po_number', scopeIdentifier: 'tenant_123');
        // ... persist PO ...
        return new JsonResponse(['po_number' => $poNumber]);
    }
}
```

---

## Dependency Injection Tips

1. **Use Interfaces Everywhere** – Services already depend on interfaces; ensure your container bindings follow the same pattern.
2. **Transaction Boundaries** – Wrap counter increments in the same DB transaction as business operations. If using Laravel, call `SequenceManager::generate()` inside `DB::transaction()`.
3. **Tenant Context** – Read tenant identifier from `Nexus\Tenant\Contracts\TenantContextInterface` (or your own context provider) and pass it into `scopeIdentifier`.
4. **Telemetry** – Decorate `SequenceManager` with `Nexus\Monitoring\Contracts\TelemetryTrackerInterface` inside your application to capture throughput metrics.

## Troubleshooting

| Symptom | Likely Cause | Fix |
| --- | --- | --- |
| Duplicate numbers under load | Counter repository not locking rows. | Use `SELECT ... FOR UPDATE` (MySQL) or `FOR UPDATE SKIP LOCKED` (PostgreSQL) before incrementing. |
| Gaps never reclaimed | Gap policy stored as `allow_gaps`. | Set gap policy to `fill_gaps` or `report_gaps_only` and ensure `GapRepositoryInterface` stores voided numbers. |
| Reservation TTL not respected | Cron/queue not calling `releaseExpiredReservations()`. | Schedule a job (e.g., Laravel command or Symfony Messenger task) to release expired entries. |
| Preview differs from generated number | Context variables differ between preview and generate. | Always pass identical `contextVariables` to both methods. |

## Related Documentation

- [Getting Started](getting-started.md)
- [API Reference](api-reference.md)
- [Examples](examples)

