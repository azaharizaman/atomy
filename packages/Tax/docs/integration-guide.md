# Integration Guide: Nexus\Tax

This guide demonstrates how to integrate the Nexus\Tax package into Laravel and Symfony applications with advanced patterns including caching decorators, repository implementations, and EventStream publishing.

---

## Table of Contents

- [Laravel Integration](#laravel-integration)
- [Symfony Integration](#symfony-integration)
- [Caching Strategy](#caching-strategy)
- [Storage Decorator](#storage-decorator)
- [EventStream Publishing](#eventstream-publishing)
- [Multi-Tenant Setup](#multi-tenant-setup)

---

## Laravel Integration

### 1. Repository Implementation

Create Eloquent repository adapters:

**app/Tax/Repositories/EloquentTaxRateRepository.php:**

```php
<?php

declare(strict_types=1);

namespace App\Tax\Repositories;

use Nexus\Tax\Contracts\TaxRateRepositoryInterface;
use Nexus\Tax\ValueObjects\{TaxRate, TaxJurisdiction};
use Nexus\Tax\Enums\{TaxType, TaxLevel};
use Nexus\Tax\Exceptions\TaxRateNotFoundException;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class EloquentTaxRateRepository implements TaxRateRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}
    
    public function findRateByCode(
        string $taxCode,
        \DateTimeInterface $effectiveDate
    ): TaxRate {
        $model = \App\Models\TaxRate::query()
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('tax_code', $taxCode)
            ->where('effective_from', '<=', $effectiveDate->format('Y-m-d'))
            ->where(function ($query) use ($effectiveDate) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $effectiveDate->format('Y-m-d'));
            })
            ->first();
        
        if (!$model) {
            throw new TaxRateNotFoundException($taxCode, $effectiveDate);
        }
        
        return $this->toValueObject($model);
    }
    
    public function findApplicableRates(
        TaxJurisdiction $jurisdiction,
        \DateTimeInterface $effectiveDate
    ): array {
        $query = \App\Models\TaxRate::query()
            ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
            ->where('effective_from', '<=', $effectiveDate->format('Y-m-d'))
            ->where(function ($query) use ($effectiveDate) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $effectiveDate->format('Y-m-d'));
            });
        
        // Filter by jurisdiction hierarchy
        $query->where(function ($q) use ($jurisdiction) {
            $q->where('jurisdiction_code', $jurisdiction->federalCode);
            
            if ($jurisdiction->stateCode) {
                $q->orWhere('jurisdiction_code', $jurisdiction->stateCode);
            }
            
            if ($jurisdiction->localCode) {
                $q->orWhere('jurisdiction_code', $jurisdiction->localCode);
            }
        });
        
        $models = $query->orderBy('application_order')->get();
        
        return $models->map(fn($model) => $this->toValueObject($model))->toArray();
    }
    
    private function toValueObject(\App\Models\TaxRate $model): TaxRate
    {
        return new TaxRate(
            taxCode: $model->tax_code,
            taxType: TaxType::from($model->tax_type),
            taxLevel: TaxLevel::from($model->tax_level),
            ratePercentage: $model->rate_percentage,
            effectiveFrom: new \DateTimeImmutable($model->effective_from),
            effectiveTo: $model->effective_to ? new \DateTimeImmutable($model->effective_to) : null,
            glAccountCode: $model->gl_account_code,
            applicationOrder: $model->application_order
        );
    }
}
```

### 2. Service Provider Binding

**app/Providers/TaxServiceProvider.php:**

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\Tax\Contracts\{
    TaxCalculatorInterface,
    TaxRateRepositoryInterface,
    TaxJurisdictionResolverInterface,
    TaxNexusManagerInterface,
    TaxExemptionManagerInterface
};
use Nexus\Tax\Services\{TaxCalculator, JurisdictionResolver, ExemptionManager};
use App\Tax\Repositories\{
    EloquentTaxRateRepository,
    EloquentNexusManager,
    EloquentExemptionManager
};
use App\Tax\Decorators\{CachedTaxCalculator, CachedJurisdictionResolver};

final class TaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository implementations
        $this->app->singleton(
            TaxRateRepositoryInterface::class,
            EloquentTaxRateRepository::class
        );
        
        $this->app->singleton(
            TaxNexusManagerInterface::class,
            EloquentNexusManager::class
        );
        
        $this->app->singleton(
            TaxExemptionManagerInterface::class,
            EloquentExemptionManager::class
        );
        
        // Core services
        $this->app->singleton(TaxCalculator::class);
        $this->app->singleton(JurisdictionResolver::class);
        $this->app->singleton(ExemptionManager::class);
        
        // Cached decorators (production only)
        if ($this->app->environment('production')) {
            $this->app->singleton(
                TaxCalculatorInterface::class,
                CachedTaxCalculator::class
            );
            
            $this->app->singleton(
                TaxJurisdictionResolverInterface::class,
                CachedJurisdictionResolver::class
            );
        } else {
            // Direct binding for development (no cache)
            $this->app->singleton(
                TaxCalculatorInterface::class,
                TaxCalculator::class
            );
            
            $this->app->singleton(
                TaxJurisdictionResolverInterface::class,
                JurisdictionResolver::class
            );
        }
    }
}
```

### 3. Migration

**database/migrations/2024_11_24_create_tax_tables.php:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('tax_code', 50)->index();
            $table->string('tax_type', 20);
            $table->string('tax_level', 20);
            $table->string('jurisdiction_code', 50)->nullable()->index();
            $table->decimal('rate_percentage', 10, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('gl_account_code', 50);
            $table->integer('application_order')->default(1);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'tax_code', 'effective_from']);
        });
        
        Schema::create('tax_audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('transaction_id', 50)->index();
            $table->string('transaction_type', 50);
            $table->date('transaction_date');
            $table->string('tax_code', 50);
            $table->json('context');
            $table->decimal('taxable_amount', 15, 4);
            $table->decimal('tax_amount', 15, 4);
            $table->json('tax_breakdown');
            $table->boolean('is_adjustment')->default(false);
            $table->uuid('original_transaction_id')->nullable();
            $table->timestamp('calculated_at');
            
            $table->index(['tenant_id', 'transaction_date']);
            $table->index(['tenant_id', 'tax_code', 'transaction_date']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('tax_audit_log');
        Schema::dropIfExists('tax_rates');
    }
};
```

---

## Symfony Integration

### 1. Repository Implementation

**src/Tax/Repository/DoctrineTaxRateRepository.php:**

```php
<?php

declare(strict_types=1);

namespace App\Tax\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Nexus\Tax\Contracts\TaxRateRepositoryInterface;
use Nexus\Tax\ValueObjects\{TaxRate, TaxJurisdiction};
use Nexus\Tax\Exceptions\TaxRateNotFoundException;

final readonly class DoctrineTaxRateRepository implements TaxRateRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}
    
    public function findRateByCode(
        string $taxCode,
        \DateTimeInterface $effectiveDate
    ): TaxRate {
        $qb = $this->entityManager->createQueryBuilder();
        
        $entity = $qb->select('t')
            ->from(\App\Entity\TaxRate::class, 't')
            ->where('t.taxCode = :code')
            ->andWhere('t.effectiveFrom <= :date')
            ->andWhere('t.effectiveTo IS NULL OR t.effectiveTo >= :date')
            ->setParameter('code', $taxCode)
            ->setParameter('date', $effectiveDate->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$entity) {
            throw new TaxRateNotFoundException($taxCode, $effectiveDate);
        }
        
        return $this->toValueObject($entity);
    }
    
    // ... similar implementation for findApplicableRates()
}
```

### 2. Service Configuration

**config/services.yaml:**

```yaml
services:
  # Repositories
  App\Tax\Repository\DoctrineTaxRateRepository:
    arguments:
      $entityManager: '@doctrine.orm.entity_manager'
    
  Nexus\Tax\Contracts\TaxRateRepositoryInterface:
    alias: App\Tax\Repository\DoctrineTaxRateRepository
  
  # Core services
  Nexus\Tax\Services\TaxCalculator:
    arguments:
      $rateRepository: '@Nexus\Tax\Contracts\TaxRateRepositoryInterface'
      $jurisdictionResolver: '@Nexus\Tax\Contracts\TaxJurisdictionResolverInterface'
      $nexusManager: '@Nexus\Tax\Contracts\TaxNexusManagerInterface'
      $exemptionManager: '@Nexus\Tax\Contracts\TaxExemptionManagerInterface'
      $telemetry: '@Nexus\Telemetry\Contracts\TelemetryTrackerInterface'
      $auditLogger: '@Nexus\AuditLogger\Contracts\AuditLogManagerInterface'
  
  # Bind interface to implementation
  Nexus\Tax\Contracts\TaxCalculatorInterface:
    alias: Nexus\Tax\Services\TaxCalculator
```

---

## Caching Strategy

### Decorator Pattern for Tax Calculator

**app/Tax/Decorators/CachedTaxCalculator.php:**

```php
<?php

declare(strict_types=1);

namespace App\Tax\Decorators;

use Nexus\Tax\Contracts\TaxCalculatorInterface;
use Nexus\Tax\ValueObjects\{TaxContext, TaxBreakdown};
use Nexus\Currency\ValueObjects\Money;
use Psr\Cache\CacheItemPoolInterface;

final readonly class CachedTaxCalculator implements TaxCalculatorInterface
{
    public function __construct(
        private TaxCalculatorInterface $decorated,
        private CacheItemPoolInterface $cache
    ) {}
    
    public function calculate(TaxContext $context, Money $taxableAmount): TaxBreakdown
    {
        // Generate cache key
        $cacheKey = $this->generateCacheKey($context, $taxableAmount);
        
        // Try cache
        $item = $this->cache->getItem($cacheKey);
        
        if ($item->isHit()) {
            return $item->get();
        }
        
        // Calculate (cache miss)
        $breakdown = $this->decorated->calculate($context, $taxableAmount);
        
        // Store in cache (1 hour TTL)
        $item->set($breakdown);
        $item->expiresAfter(3600);
        $this->cache->save($item);
        
        return $breakdown;
    }
    
    private function generateCacheKey(TaxContext $context, Money $taxableAmount): string
    {
        return sprintf(
            'tax_calc_%s_%s_%s_%s',
            $context->taxCode,
            $context->transactionDate->format('Y-m-d'),
            $taxableAmount->getAmount(),
            $taxableAmount->getCurrency()
        );
    }
}
```

### Jurisdiction Resolution Caching

**app/Tax/Decorators/CachedJurisdictionResolver.php:**

```php
<?php

declare(strict_types=1);

namespace App\Tax\Decorators;

use Nexus\Tax\Contracts\TaxJurisdictionResolverInterface;
use Nexus\Tax\ValueObjects\{TaxContext, TaxJurisdiction};
use Psr\Cache\CacheItemPoolInterface;

final readonly class CachedJurisdictionResolver implements TaxJurisdictionResolverInterface
{
    public function __construct(
        private TaxJurisdictionResolverInterface $decorated,
        private CacheItemPoolInterface $cache
    ) {}
    
    public function resolve(TaxContext $context): TaxJurisdiction
    {
        // Cache key based on destination address
        $cacheKey = $this->generateCacheKey($context->destinationAddress);
        
        $item = $this->cache->getItem($cacheKey);
        
        if ($item->isHit()) {
            return $item->get();
        }
        
        // Resolve jurisdiction (geocoding API call)
        $jurisdiction = $this->decorated->resolve($context);
        
        // Cache for 24 hours (addresses rarely change jurisdiction)
        $item->set($jurisdiction);
        $item->expiresAfter(86400);
        $this->cache->save($item);
        
        return $jurisdiction;
    }
    
    private function generateCacheKey(array $address): string
    {
        return 'jurisdiction_' . md5(json_encode($address));
    }
}
```

---

## Storage Decorator

### Exemption Certificate Storage

**app/Tax/Decorators/StorageBackedExemptionManager.php:**

```php
<?php

declare(strict_types=1);

namespace App\Tax\Decorators;

use Nexus\Tax\Contracts\TaxExemptionManagerInterface;
use Nexus\Tax\ValueObjects\ExemptionCertificate;
use Nexus\Storage\Contracts\StorageInterface;

final readonly class StorageBackedExemptionManager implements TaxExemptionManagerInterface
{
    public function __construct(
        private TaxExemptionManagerInterface $decorated,
        private StorageInterface $storage
    ) {}
    
    public function validateExemption(
        string $certificateId,
        \DateTimeInterface $transactionDate
    ): ExemptionCertificate {
        $certificate = $this->decorated->validateExemption($certificateId, $transactionDate);
        
        // If certificate has storage key, verify PDF exists
        if ($certificate->storageKey) {
            if (!$this->storage->exists($certificate->storageKey)) {
                throw new \RuntimeException(
                    "Exemption certificate PDF not found: {$certificate->storageKey}"
                );
            }
        }
        
        return $certificate;
    }
    
    public function getExpiringCertificates(\DateTimeInterface $withinDays): array
    {
        return $this->decorated->getExpiringCertificates($withinDays);
    }
    
    public function storeCertificatePdf(string $certificateId, string $pdfPath): string
    {
        $storageKey = "exemption-certificates/{$certificateId}.pdf";
        
        $this->storage->put(
            path: $storageKey,
            contents: file_get_contents($pdfPath)
        );
        
        return $storageKey;
    }
}
```

---

## EventStream Publishing

### Optional Audit Trail via EventStream

**app/Tax/Publishers/TaxAuditPublisher.php:**

```php
<?php

declare(strict_types=1);

namespace App\Tax\Publishers;

use Nexus\Tax\Contracts\TaxAuditPublisherInterface;
use Nexus\Tax\ValueObjects\{TaxContext, TaxBreakdown, TaxAdjustmentContext};
use Nexus\EventStream\Contracts\EventStoreInterface;
use App\Tax\Events\{TaxCalculatedEvent, TaxAdjustedEvent};

final readonly class TaxAuditPublisher implements TaxAuditPublisherInterface
{
    public function __construct(
        private EventStoreInterface $eventStore
    ) {}
    
    public function publishCalculationEvent(
        TaxContext $context,
        TaxBreakdown $breakdown
    ): void {
        $event = new TaxCalculatedEvent(
            transactionId: $context->transactionId,
            taxCode: $context->taxCode,
            taxableAmount: $breakdown->netAmount,
            taxAmount: $breakdown->totalTaxAmount,
            isReverseCharge: $breakdown->isReverseCharge,
            calculatedAt: new \DateTimeImmutable()
        );
        
        $this->eventStore->append(
            streamName: "tax-{$context->transactionId}",
            event: $event
        );
    }
    
    public function publishAdjustmentEvent(
        TaxAdjustmentContext $adjustment
    ): void {
        $event = new TaxAdjustedEvent(
            originalTransactionId: $adjustment->originalTransactionId,
            adjustmentReason: $adjustment->adjustmentReason,
            adjustedBy: $adjustment->adjustedBy,
            adjustedAt: $adjustment->adjustmentDate
        );
        
        $this->eventStore->append(
            streamName: "tax-{$adjustment->originalTransactionId}",
            event: $event
        );
    }
}
```

---

## Multi-Tenant Setup

### Tenant-Scoped Queries

**app/Tax/Repositories/EloquentTaxRateRepository.php (Enhanced):**

```php
<?php

declare(strict_types=1);

namespace App\Tax\Repositories;

use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class EloquentTaxRateRepository implements TaxRateRepositoryInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}
    
    private function baseTenantQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        if (!$tenantId) {
            throw new \RuntimeException('No tenant context available');
        }
        
        return \App\Models\TaxRate::query()
            ->where('tenant_id', $tenantId);
    }
    
    public function findRateByCode(
        string $taxCode,
        \DateTimeInterface $effectiveDate
    ): TaxRate {
        $model = $this->baseTenantQuery()
            ->where('tax_code', $taxCode)
            ->where('effective_from', '<=', $effectiveDate->format('Y-m-d'))
            ->where(function ($query) use ($effectiveDate) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $effectiveDate->format('Y-m-d'));
            })
            ->first();
        
        if (!$model) {
            throw new TaxRateNotFoundException($taxCode, $effectiveDate);
        }
        
        return $this->toValueObject($model);
    }
}
```

### Queue Job Tenant Propagation

**app/Jobs/CalculateInvoiceTaxJob.php:**

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Tax\Contracts\TaxCalculatorInterface;

final class CalculateInvoiceTaxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    
    public function __construct(
        private readonly string $invoiceId,
        private readonly string $tenantId // Explicitly pass tenant
    ) {}
    
    public function handle(
        TaxCalculatorInterface $taxCalculator,
        TenantContextInterface $tenantContext
    ): void {
        // Set tenant context for this job
        $tenantContext->setCurrentTenant($this->tenantId);
        
        // Now all repository queries are tenant-scoped
        $invoice = \App\Models\Invoice::findOrFail($this->invoiceId);
        
        // Calculate tax (repository automatically scoped)
        $breakdown = $taxCalculator->calculate($context, $taxableAmount);
        
        // Save results...
    }
}
```

---

## Advanced Patterns

### Reverse Charge Handler

**app/Tax/Handlers/ReverseChargeHandler.php:**

```php
<?php

declare(strict_types=1);

namespace App\Tax\Handlers;

use Nexus\Tax\ValueObjects\TaxBreakdown;
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;

final readonly class ReverseChargeHandler
{
    public function __construct(
        private GeneralLedgerManagerInterface $glManager
    ) {}
    
    public function handleReverseCharge(TaxBreakdown $breakdown, string $vendorId): void
    {
        if (!$breakdown->isReverseCharge) {
            return; // Not reverse charge
        }
        
        // Post deferred tax liability (buyer self-assesses)
        foreach ($breakdown->taxLines as $taxLine) {
            $this->glManager->postJournalEntry([
                'debit' => [
                    'account' => '5100', // Tax expense
                    'amount' => $taxLine->taxAmount,
                ],
                'credit' => [
                    'account' => '2300', // Tax liability
                    'amount' => $taxLine->taxAmount,
                ],
                'description' => "Reverse charge VAT - {$taxLine->description}",
                'reference' => $vendorId,
            ]);
        }
    }
}
```

---

**For complete examples, see:**
- [Getting Started](getting-started.md)
- [API Reference](api-reference.md)
- [Examples](examples/)
