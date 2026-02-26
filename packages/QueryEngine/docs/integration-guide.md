# Integration Guide: Analytics

**How to integrate the Nexus Analytics package into Laravel and Symfony applications.**

---

## Table of Contents

1. [Laravel Integration](#laravel-integration)
2. [Symfony Integration](#symfony-integration)
3. [Common Integration Patterns](#common-integration-patterns)
4. [Troubleshooting](#troubleshooting)

---

## Laravel Integration

### Step 1: Install Package

```bash
composer require nexus/query-engine:"*@dev"
```

### Step 2: Create Service Provider

`app/Providers/AnalyticsServiceProvider.php`:

```php
<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\Core\Engine\QueryExecutor;
use Nexus\QueryEngine\Core\Engine\DataSourceAggregator;
use Nexus\QueryEngine\Core\Engine\GuardEvaluator;
use App\Analytics\DataSources\CustomerDataSource;
use App\Analytics\DataSources\SalesDataSource;
use App\Analytics\DataSources\ProductDataSource;
use App\Analytics\DataSources\InventoryDataSource;
use Psr\Log\LoggerInterface;
use Nexus\Telemetry\Contracts\TelemetryTrackerInterface;

final class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register data sources
        $this->app->singleton('analytics.dataSources', function ($app) {
            return [
                $app->make(CustomerDataSource::class),
                $app->make(SalesDataSource::class),
                $app->make(ProductDataSource::class),
                $app->make(InventoryDataSource::class),
            ];
        });
        
        // Register data source aggregator
        $this->app->singleton(DataSourceAggregator::class, function ($app) {
            return new DataSourceAggregator(
                $app->make('analytics.dataSources')
            );
        });
        
        // Register guard evaluator
        $this->app->singleton(GuardEvaluator::class, function ($app) {
            return new GuardEvaluator();
        });
        
        // Register query executor
        $this->app->singleton(QueryExecutor::class, function ($app) {
            return new QueryExecutor(
                $app->make(DataSourceAggregator::class),
                $app->make(GuardEvaluator::class)
            );
        });
        
        // Register analytics manager
        $this->app->singleton(AnalyticsManager::class, function ($app) {
            return new AnalyticsManager(
                queryExecutor: $app->make(QueryExecutor::class),
                logger: $app->make(LoggerInterface::class),
                telemetry: $app->make(TelemetryTrackerInterface::class)
            );
        });
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\AnalyticsServiceProvider::class,
],
```

### Step 3: Implement Data Sources

`app/Analytics/DataSources/CustomerDataSource.php`:

```php
<?php declare(strict_types=1);

namespace App\Analytics\DataSources;

use Nexus\QueryEngine\Core\Contracts\DataSourceInterface;
use App\Repositories\CustomerRepository;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class CustomerDataSource implements DataSourceInterface
{
    public function __construct(
        private CustomerRepository $repository,
        private TenantContextInterface $tenantContext
    ) {}
    
    public function getName(): string
    {
        return 'customers';
    }
    
    public function fetch(array $filters = [], array $columns = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        $query = $this->repository
            ->query()
            ->where('tenant_id', $tenantId);
        
        // Apply filters
        foreach ($filters as $filter) {
            $column = $filter['column'];
            $operator = $filter['operator'];
            $value = $filter['value'];
            
            match ($operator) {
                '=' => $query->where($column, $value),
                '!=' => $query->where($column, '!=', $value),
                '>' => $query->where($column, '>', $value),
                '>=' => $query->where($column, '>=', $value),
                '<' => $query->where($column, '<', $value),
                '<=' => $query->where($column, '<=', $value),
                'in' => $query->whereIn($column, $value),
                'not_in' => $query->whereNotIn($column, $value),
                'between' => $query->whereBetween($column, $value),
                'like' => $query->where($column, 'like', $value),
                'is_null' => $query->whereNull($column),
                'is_not_null' => $query->whereNotNull($column),
            };
        }
        
        // Select specific columns if provided
        if (!empty($columns)) {
            $query->select($columns);
        }
        
        return $query->get()->toArray();
    }
    
    public function getSchema(): array
    {
        return [
            'customer_id' => 'string',
            'name' => 'string',
            'email' => 'string',
            'region' => 'string',
            'industry' => 'string',
            'revenue' => 'float',
            'created_at' => 'date',
        ];
    }
}
```

### Step 4: Implement Guard Context

`app/Analytics/UserGuardContext.php`:

```php
<?php declare(strict_types=1);

namespace App\Analytics;

use Nexus\QueryEngine\Core\Contracts\GuardContextInterface;
use Illuminate\Support\Facades\Auth;
use Nexus\Tenant\Contracts\TenantContextInterface;

final readonly class UserGuardContext implements GuardContextInterface
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}
    
    public function getUserId(): string
    {
        return Auth::id();
    }
    
    public function getTenantId(): string
    {
        return $this->tenantContext->getCurrentTenantId();
    }
    
    public function hasRole(string $role): bool
    {
        return Auth::user()->hasRole($role);
    }
    
    public function hasPermission(string $permission): bool
    {
        return Auth::user()->can($permission);
    }
    
    public function getAttribute(string $key): mixed
    {
        return match ($key) {
            'user_id' => $this->getUserId(),
            'tenant_id' => $this->getTenantId(),
            'user_region' => Auth::user()->region,
            'accessible_regions' => Auth::user()->getAccessibleRegions(),
            'accessible_departments' => Auth::user()->getAccessibleDepartments(),
            default => null,
        };
    }
}
```

### Step 5: Use in Controller

`app/Http/Controllers/AnalyticsController.php`:

```php
<?php declare(strict_types=1);

namespace App\Http\Controllers;

use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\ValueObjects\QueryDefinition;
use App\Analytics\UserGuardContext;
use Illuminate\Http\JsonResponse;

final readonly class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsManager $analyticsManager,
        private UserGuardContext $guardContext
    ) {}
    
    public function salesByRegion(): JsonResponse
    {
        $query = new QueryDefinition(
            dataSources: ['sales'],
            measures: [
                ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales']
            ],
            dimensions: ['region'],
            groupBy: ['region'],
            orderBy: [['column' => 'total_sales', 'direction' => 'desc']]
        );
        
        $result = $this->analyticsManager->executeQuery($query, $this->guardContext);
        
        return response()->json([
            'data' => $result->getRows(),
            'total' => $result->getTotalRows(),
            'execution_time_ms' => $result->getExecutionTimeMs(),
        ]);
    }
}
```

---

## Symfony Integration

### Step 1: Install Package

```bash
composer require nexus/query-engine:"*@dev"
```

### Step 2: Configure Services

`config/services.yaml`:

```yaml
services:
    # Data Sources
    App\Analytics\DataSources\CustomerDataSource:
        arguments:
            $repository: '@App\Repository\CustomerRepository'
            $tenantContext: '@Nexus\Tenant\Contracts\TenantContextInterface'
    
    App\Analytics\DataSources\SalesDataSource:
        arguments:
            $repository: '@App\Repository\SalesRepository'
            $tenantContext: '@Nexus\Tenant\Contracts\TenantContextInterface'
    
    # Data Source Aggregator
    Nexus\QueryEngine\Core\Engine\DataSourceAggregator:
        arguments:
            $dataSources:
                - '@App\Analytics\DataSources\CustomerDataSource'
                - '@App\Analytics\DataSources\SalesDataSource'
    
    # Guard Evaluator
    Nexus\QueryEngine\Core\Engine\GuardEvaluator: ~
    
    # Query Executor
    Nexus\QueryEngine\Core\Engine\QueryExecutor:
        arguments:
            $aggregator: '@Nexus\QueryEngine\Core\Engine\DataSourceAggregator'
            $guardEvaluator: '@Nexus\QueryEngine\Core\Engine\GuardEvaluator'
    
    # Analytics Manager
    Nexus\QueryEngine\Services\AnalyticsManager:
        arguments:
            $queryExecutor: '@Nexus\QueryEngine\Core\Engine\QueryExecutor'
            $logger: '@Psr\Log\LoggerInterface'
            $telemetry: '@Nexus\Telemetry\Contracts\TelemetryTrackerInterface'
```

### Step 3: Use in Controller

`src/Controller/AnalyticsController.php`:

```php
<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\ValueObjects\QueryDefinition;
use App\Analytics\UserGuardContext;

final class AnalyticsController extends AbstractController
{
    public function __construct(
        private readonly AnalyticsManager $analyticsManager,
        private readonly UserGuardContext $guardContext
    ) {}
    
    #[Route('/api/analytics/sales-by-region', name: 'analytics_sales_by_region', methods: ['GET'])]
    public function salesByRegion(): JsonResponse
    {
        $query = new QueryDefinition(
            dataSources: ['sales'],
            measures: [
                ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales']
            ],
            dimensions: ['region'],
            groupBy: ['region'],
            orderBy: [['column' => 'total_sales', 'direction' => 'desc']]
        );
        
        $result = $this->analyticsManager->executeQuery($query, $this->guardContext);
        
        return $this->json([
            'data' => $result->getRows(),
            'total' => $result->getTotalRows(),
            'execution_time_ms' => $result->getExecutionTimeMs(),
        ]);
    }
}
```

---

## Common Integration Patterns

### Pattern 1: Dashboard Service

```php
<?php declare(strict_types=1);

namespace App\Services;

use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\ValueObjects\QueryDefinition;
use Nexus\QueryEngine\Core\Contracts\GuardContextInterface;

final readonly class DashboardService
{
    public function __construct(
        private AnalyticsManager $analyticsManager
    ) {}
    
    public function getExecutiveDashboard(GuardContextInterface $guardContext): array
    {
        return [
            'sales_by_region' => $this->getSalesByRegion($guardContext),
            'top_customers' => $this->getTopCustomers($guardContext),
            'product_performance' => $this->getProductPerformance($guardContext),
            'inventory_status' => $this->getInventoryStatus($guardContext),
        ];
    }
    
    private function getSalesByRegion(GuardContextInterface $guardContext): array
    {
        $query = new QueryDefinition(
            dataSources: ['sales'],
            measures: [['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales']],
            dimensions: ['region'],
            groupBy: ['region'],
            orderBy: [['column' => 'total_sales', 'direction' => 'desc']]
        );
        
        return $this->analyticsManager->executeQuery($query, $guardContext)->getRows();
    }
    
    // ... other methods
}
```

### Pattern 2: Report Generator

```php
<?php declare(strict_types=1);

namespace App\Services;

use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\ValueObjects\QueryDefinition;
use Nexus\Export\Contracts\ExportManagerInterface;

final readonly class ReportGenerator
{
    public function __construct(
        private AnalyticsManager $analyticsManager,
        private ExportManagerInterface $exporter
    ) {}
    
    public function generateSalesReport(string $startDate, string $endDate, string $format = 'excel'): string
    {
        $query = new QueryDefinition(
            dataSources: ['sales', 'customers', 'products'],
            measures: [
                ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales'],
                ['column' => 'quantity', 'aggregation' => 'sum', 'alias' => 'total_quantity'],
            ],
            dimensions: ['region', 'product_category', 'month'],
            filters: [
                ['column' => 'sale_date', 'operator' => 'between', 'value' => [$startDate, $endDate]]
            ],
            groupBy: ['region', 'product_category', 'month'],
            orderBy: [['column' => 'total_sales', 'direction' => 'desc']]
        );
        
        $result = $this->analyticsManager->executeQuery($query, $this->getGuardContext());
        
        // Export to Excel/PDF
        return $this->exporter->export(
            data: $result->getRows(),
            format: $format,
            template: 'reports.sales-report'
        );
    }
}
```

### Pattern 3: Cached Analytics

```php
<?php declare(strict_types=1);

namespace App\Services;

use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\ValueObjects\QueryDefinition;
use Psr\SimpleCache\CacheInterface;

final readonly class CachedAnalyticsService
{
    public function __construct(
        private AnalyticsManager $analyticsManager,
        private CacheInterface $cache
    ) {}
    
    public function getSalesByRegion(bool $useCache = true): array
    {
        $cacheKey = 'analytics.sales_by_region';
        
        if ($useCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        
        $query = new QueryDefinition(
            dataSources: ['sales'],
            measures: [['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales']],
            dimensions: ['region'],
            groupBy: ['region'],
            orderBy: [['column' => 'total_sales', 'direction' => 'desc']]
        );
        
        $result = $this->analyticsManager->executeQuery($query, $this->getGuardContext());
        $data = $result->getRows();
        
        // Cache for 1 hour
        $this->cache->set($cacheKey, $data, 3600);
        
        return $data;
    }
}
```

---

## Troubleshooting

### Issue 1: "Data source not found"

**Symptom:**
```
Nexus\QueryEngine\Exceptions\DataSourceNotFoundException: Data source 'customers' not found
```

**Solution:**
Ensure data source is registered in service provider:

```php
$this->app->singleton('analytics.dataSources', function ($app) {
    return [
        $app->make(CustomerDataSource::class), // ✅ Add this
        // ... other data sources
    ];
});
```

---

### Issue 2: "Guard evaluation failed"

**Symptom:**
```
Nexus\QueryEngine\Exceptions\GuardEvaluationFailedException: User does not have access to region 'Asia'
```

**Solution:**
Check guard context implementation:

```php
public function getAttribute(string $key): mixed
{
    return match ($key) {
        'accessible_regions' => Auth::user()->getAccessibleRegions(), // ✅ Ensure method exists
        default => null,
    };
}
```

---

### Issue 3: Slow query performance

**Symptom:**
Queries taking >5 seconds to execute.

**Solutions:**

1. **Add database indexes:**
```sql
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_region ON sales(region);
```

2. **Use query limits:**
```php
$query = new QueryDefinition(
    // ...
    limit: 1000 // ✅ Add limit
);
```

3. **Enable caching:**
```php
// Cache result for 1 hour
$this->cache->set($cacheKey, $result->getRows(), 3600);
```

---

### Issue 4: Memory exhaustion with large datasets

**Symptom:**
```
Fatal error: Allowed memory size exhausted
```

**Solution:**
Use pagination:

```php
// Fetch 1000 rows at a time
$query = new QueryDefinition(
    dataSources: ['sales'],
    measures: [['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales']],
    dimensions: ['region'],
    groupBy: ['region'],
    limit: 1000, // ✅ Limit rows
    offset: 0
);
```

---

**See Also:**
- [Getting Started Guide](getting-started.md) - Basic usage
- [API Reference](api-reference.md) - Complete interface documentation
- [Examples](examples/) - Working code examples

---

**Last Updated:** 2024-11-24  
**Package Version:** 1.0.0 (Development)
