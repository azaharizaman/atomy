# Getting Started with Nexus Analytics

**Quick start guide for integrating the Nexus Analytics package into your PHP application.**

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Core Concepts](#core-concepts)
4. [Basic Configuration](#basic-configuration)
5. [Your First Query](#your-first-query)
6. [Common Use Cases](#common-use-cases)
7. [Next Steps](#next-steps)

---

## Prerequisites

Before using the Nexus Analytics package, ensure you have:

- **PHP 8.3 or higher**
- **Composer** for dependency management
- **PSR-3 Logger** (optional but recommended for debugging)
- **Data source implementations** (database, API, files, etc.)

---

## Installation

### Step 1: Install via Composer

In your Nexus monorepo or consuming application:

```bash
composer require nexus/query-engine:"*@dev"
```

### Step 2: Verify Installation

```bash
composer show nexus/query-engine
```

You should see package details confirming successful installation.

---

## Core Concepts

### What is Nexus Analytics?

Nexus Analytics is a **framework-agnostic analytics engine** that provides:

- **Multi-dimensional analysis** (GROUP BY dimensions, aggregate measures)
- **Flexible filtering** (WHERE conditions with complex expressions)
- **Data source aggregation** (combine data from multiple sources)
- **Row-level security** (guard conditions for access control)
- **Query optimization** (efficient execution plans)
- **Multi-tenant isolation** (automatic tenant scoping)

### Key Components

#### 1. AnalyticsManager (Orchestrator)
The main entry point for executing analytical queries. It coordinates query execution, result caching, and telemetry tracking.

#### 2. QueryExecutor (Engine)
Executes query definitions against data sources, applies filters, groups, and aggregates data.

#### 3. DataSourceAggregator (Aggregation Engine)
Merges data from multiple sources (databases, APIs, files) into a unified result set.

#### 4. GuardEvaluator (Security Engine)
Evaluates row-level security conditions (guards) to filter data based on user permissions.

#### 5. QueryDefinition (Value Object)
Immutable object defining the query structure (data sources, measures, dimensions, filters, guards).

#### 6. AnalyticsResult (Value Object)
Immutable object containing query results (rows, columns, pagination info, metadata).

---

## Basic Configuration

### Step 1: Define Your Contracts

The Analytics package requires you to implement the following interfaces:

#### Required Contracts

```php
// 1. Data Source Interface
namespace App\Analytics;

use Nexus\QueryEngine\Core\Contracts\DataSourceInterface;

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
        
        // Fetch data from your repository
        $customers = $this->repository->findByFilters($filters, $tenantId);
        
        // Transform to array format
        return array_map(fn($c) => [
            'customer_id' => $c->getId(),
            'name' => $c->getName(),
            'region' => $c->getRegion(),
            'revenue' => $c->getTotalRevenue(),
            'created_at' => $c->getCreatedAt()->format('Y-m-d'),
        ], $customers);
    }
    
    public function getSchema(): array
    {
        return [
            'customer_id' => 'string',
            'name' => 'string',
            'region' => 'string',
            'revenue' => 'float',
            'created_at' => 'date',
        ];
    }
}
```

```php
// 2. Guard Context Interface (for row-level security)
namespace App\Analytics;

use Nexus\QueryEngine\Core\Contracts\GuardContextInterface;

final readonly class UserGuardContext implements GuardContextInterface
{
    public function __construct(
        private string $userId,
        private array $userRoles,
        private array $userPermissions,
        private string $tenantId
    ) {}
    
    public function getUserId(): string
    {
        return $this->userId;
    }
    
    public function getTenantId(): string
    {
        return $this->tenantId;
    }
    
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->userRoles, true);
    }
    
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->userPermissions, true);
    }
    
    public function getAttribute(string $key): mixed
    {
        return match ($key) {
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'roles' => $this->userRoles,
            'permissions' => $this->userPermissions,
            default => null,
        };
    }
}
```

### Step 2: Register Data Sources

In your service provider (Laravel example):

```php
use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\Core\Engine\QueryExecutor;
use Nexus\QueryEngine\Core\Engine\DataSourceAggregator;
use Nexus\QueryEngine\Core\Engine\GuardEvaluator;
use App\Analytics\CustomerDataSource;
use App\Analytics\SalesDataSource;

// Register data sources
$dataSources = [
    new CustomerDataSource($customerRepository, $tenantContext),
    new SalesDataSource($salesRepository, $tenantContext),
    new ProductDataSource($productRepository, $tenantContext),
];

// Create aggregator
$aggregator = new DataSourceAggregator($dataSources);

// Create guard evaluator
$guardEvaluator = new GuardEvaluator();

// Create query executor
$queryExecutor = new QueryExecutor($aggregator, $guardEvaluator);

// Create analytics manager
$analyticsManager = new AnalyticsManager(
    queryExecutor: $queryExecutor,
    logger: $logger, // PSR-3 logger (optional)
    telemetry: $telemetry // TelemetryTrackerInterface (optional)
);
```

---

## Your First Query

### Example 1: Simple Revenue by Region

```php
use Nexus\QueryEngine\ValueObjects\QueryDefinition;
use App\Analytics\UserGuardContext;

// Create query definition
$queryDefinition = new QueryDefinition(
    dataSources: ['customers'],
    measures: [
        ['column' => 'revenue', 'aggregation' => 'sum', 'alias' => 'total_revenue']
    ],
    dimensions: ['region'],
    filters: [],
    guards: [],
    groupBy: ['region'],
    orderBy: [['column' => 'total_revenue', 'direction' => 'desc']],
    limit: 10,
    offset: 0
);

// Create guard context (user context for row-level security)
$guardContext = new UserGuardContext(
    userId: 'user-123',
    userRoles: ['sales_manager'],
    userPermissions: ['view_all_customers'],
    tenantId: 'tenant-abc'
);

// Execute query
$result = $analyticsManager->executeQuery($queryDefinition, $guardContext);

// Access results
foreach ($result->getRows() as $row) {
    echo "Region: {$row['region']}, Total Revenue: {$row['total_revenue']}\n";
}

// Output:
// Region: Asia, Total Revenue: 5000000
// Region: Europe, Total Revenue: 3500000
// Region: North America, Total Revenue: 3200000
```

### Example 2: Filtered Analysis with Row-Level Security

```php
// Create query with filters and guards
$queryDefinition = new QueryDefinition(
    dataSources: ['customers'],
    measures: [
        ['column' => 'revenue', 'aggregation' => 'sum', 'alias' => 'total_revenue'],
        ['column' => 'customer_id', 'aggregation' => 'count', 'alias' => 'customer_count']
    ],
    dimensions: ['region', 'created_at'], // Year
    filters: [
        [
            'column' => 'created_at',
            'operator' => 'between',
            'value' => ['2024-01-01', '2024-12-31']
        ],
        [
            'column' => 'revenue',
            'operator' => '>',
            'value' => 10000
        ]
    ],
    guards: [
        [
            'expression' => 'region IN (:allowed_regions)',
            'parameters' => [
                'allowed_regions' => ['Asia', 'Europe'] // User can only see these regions
            ]
        ]
    ],
    groupBy: ['region', 'created_at'],
    orderBy: [
        ['column' => 'created_at', 'direction' => 'desc'],
        ['column' => 'total_revenue', 'direction' => 'desc']
    ],
    limit: 20,
    offset: 0
);

// Execute
$result = $analyticsManager->executeQuery($queryDefinition, $guardContext);

// Get metadata
echo "Total Rows: " . $result->getTotalRows() . "\n";
echo "Execution Time: " . $result->getExecutionTimeMs() . "ms\n";
echo "Data Sources: " . implode(', ', $result->getDataSourcesUsed()) . "\n";
```

---

## Common Use Cases

### Use Case 1: Sales Dashboard (Multi-Dimensional Analysis)

```php
// Sales by Region, Product Category, and Month
$salesQuery = new QueryDefinition(
    dataSources: ['sales', 'products', 'customers'],
    measures: [
        ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales'],
        ['column' => 'quantity', 'aggregation' => 'sum', 'alias' => 'total_quantity'],
        ['column' => 'sale_id', 'aggregation' => 'count', 'alias' => 'order_count'],
        ['column' => 'amount', 'aggregation' => 'avg', 'alias' => 'avg_order_value']
    ],
    dimensions: ['region', 'product_category', 'month'],
    filters: [
        ['column' => 'sale_date', 'operator' => '>=', 'value' => '2024-01-01'],
        ['column' => 'status', 'operator' => '=', 'value' => 'completed']
    ],
    guards: [
        ['expression' => 'tenant_id = :tenant_id', 'parameters' => ['tenant_id' => $guardContext->getTenantId()]]
    ],
    groupBy: ['region', 'product_category', 'month'],
    orderBy: [['column' => 'total_sales', 'direction' => 'desc']],
    limit: 100
);

$result = $analyticsManager->executeQuery($salesQuery, $guardContext);
```

### Use Case 2: Inventory Aging Analysis

```php
$inventoryAgingQuery = new QueryDefinition(
    dataSources: ['inventory', 'products', 'warehouses'],
    measures: [
        ['column' => 'quantity', 'aggregation' => 'sum', 'alias' => 'total_quantity'],
        ['column' => 'value', 'aggregation' => 'sum', 'alias' => 'total_value'],
        ['column' => 'product_id', 'aggregation' => 'count', 'alias' => 'product_count']
    ],
    dimensions: ['warehouse', 'aging_bucket'], // 0-30, 31-60, 61-90, 90+ days
    filters: [
        ['column' => 'status', 'operator' => '=', 'value' => 'available']
    ],
    guards: [],
    groupBy: ['warehouse', 'aging_bucket'],
    orderBy: [['column' => 'aging_bucket', 'direction' => 'asc']],
    limit: 50
);

$result = $analyticsManager->executeQuery($inventoryAgingQuery, $guardContext);
```

### Use Case 3: HR Headcount Trends

```php
$headcountQuery = new QueryDefinition(
    dataSources: ['employees', 'departments'],
    measures: [
        ['column' => 'employee_id', 'aggregation' => 'count', 'alias' => 'headcount'],
        ['column' => 'salary', 'aggregation' => 'sum', 'alias' => 'total_payroll'],
        ['column' => 'salary', 'aggregation' => 'avg', 'alias' => 'avg_salary']
    ],
    dimensions: ['department', 'hire_year', 'employment_type'],
    filters: [
        ['column' => 'status', 'operator' => '=', 'value' => 'active']
    ],
    guards: [
        ['expression' => 'department IN (:allowed_departments)', 'parameters' => [
            'allowed_departments' => $guardContext->getAttribute('accessible_departments')
        ]]
    ],
    groupBy: ['department', 'hire_year', 'employment_type'],
    orderBy: [['column' => 'headcount', 'direction' => 'desc']],
    limit: 100
);

$result = $analyticsManager->executeQuery($headcountQuery, $guardContext);
```

---

## Pagination

```php
// Page 1 (first 20 rows)
$page1Query = new QueryDefinition(
    dataSources: ['customers'],
    measures: [['column' => 'revenue', 'aggregation' => 'sum', 'alias' => 'total_revenue']],
    dimensions: ['region'],
    filters: [],
    guards: [],
    groupBy: ['region'],
    orderBy: [['column' => 'total_revenue', 'direction' => 'desc']],
    limit: 20,
    offset: 0
);

$page1Result = $analyticsManager->executeQuery($page1Query, $guardContext);

// Page 2 (next 20 rows)
$page2Query = new QueryDefinition(
    // ... same as above ...
    limit: 20,
    offset: 20
);

$page2Result = $analyticsManager->executeQuery($page2Query, $guardContext);

// Check if more pages exist
if ($page1Result->getTotalRows() > 40) {
    echo "More pages available\n";
}
```

---

## Error Handling

```php
use Nexus\QueryEngine\Exceptions\InvalidQueryDefinitionException;
use Nexus\QueryEngine\Exceptions\QueryExecutionFailedException;
use Nexus\QueryEngine\Exceptions\GuardEvaluationFailedException;

try {
    $result = $analyticsManager->executeQuery($queryDefinition, $guardContext);
} catch (InvalidQueryDefinitionException $e) {
    // Query validation failed (e.g., missing required fields, invalid aggregation)
    echo "Invalid query: " . $e->getMessage();
} catch (GuardEvaluationFailedException $e) {
    // Row-level security check failed
    echo "Access denied: " . $e->getMessage();
} catch (QueryExecutionFailedException $e) {
    // Execution failed (e.g., data source unavailable, timeout)
    echo "Query execution failed: " . $e->getMessage();
}
```

---

## Supported Aggregation Functions

| Function | Description | Example |
|----------|-------------|---------|
| `sum` | Sum of values | `SUM(revenue)` |
| `avg` | Average of values | `AVG(order_value)` |
| `count` | Count of rows | `COUNT(customer_id)` |
| `min` | Minimum value | `MIN(order_date)` |
| `max` | Maximum value | `MAX(order_date)` |
| `count_distinct` | Count unique values | `COUNT(DISTINCT customer_id)` |

---

## Supported Filter Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `=` | Equals | `['column' => 'status', 'operator' => '=', 'value' => 'active']` |
| `!=` | Not equals | `['column' => 'status', 'operator' => '!=', 'value' => 'deleted']` |
| `>` | Greater than | `['column' => 'revenue', 'operator' => '>', 'value' => 10000]` |
| `>=` | Greater than or equal | `['column' => 'quantity', 'operator' => '>=', 'value' => 100]` |
| `<` | Less than | `['column' => 'age', 'operator' => '<', 'value' => 30]` |
| `<=` | Less than or equal | `['column' => 'stock', 'operator' => '<=', 'value' => 10]` |
| `in` | In array | `['column' => 'region', 'operator' => 'in', 'value' => ['Asia', 'Europe']]` |
| `not_in` | Not in array | `['column' => 'status', 'operator' => 'not_in', 'value' => ['deleted', 'archived']]` |
| `between` | Between two values | `['column' => 'date', 'operator' => 'between', 'value' => ['2024-01-01', '2024-12-31']]` |
| `like` | Pattern match | `['column' => 'name', 'operator' => 'like', 'value' => '%Corp%']` |
| `is_null` | Is NULL | `['column' => 'deleted_at', 'operator' => 'is_null']` |
| `is_not_null` | Is not NULL | `['column' => 'approved_at', 'operator' => 'is_not_null']` |

---

## Next Steps

Now that you've learned the basics, explore:

1. **[API Reference](api-reference.md)** - Complete interface documentation
2. **[Integration Guide](integration-guide.md)** - Laravel and Symfony integration examples
3. **[Advanced Examples](examples/advanced-usage.php)** - Multi-dimensional analysis, drill-down, forecasting
4. **[IMPLEMENTATION_SUMMARY.md](../IMPLEMENTATION_SUMMARY.md)** - Architecture and design decisions

---

## Need Help?

- **Implementation Guide:** See `IMPLEMENTATION_SUMMARY.md` for comprehensive architecture documentation
- **Requirements:** See `REQUIREMENTS.md` for detailed feature specifications
- **Code Examples:** See `docs/examples/` for working code samples

---

**Last Updated:** 2024-11-24  
**Package Version:** 1.0.0 (Development)
