# API Reference: Analytics

**Complete interface and class documentation for the Nexus Analytics package.**

---

## Table of Contents

1. [Public Interfaces](#public-interfaces)
2. [Services](#services)
3. [Value Objects](#value-objects)
4. [Exceptions](#exceptions)
5. [Core Engine Contracts](#core-engine-contracts)

---

## Public Interfaces

### AnalyticsManagerInterface (Planned - Not Yet Implemented)

**Purpose:** Primary service interface for analytics operations (planned for future release).

**Note:** Current implementation uses concrete `AnalyticsManager` class directly. Interface abstraction planned for v2.0.

---

## Services

### AnalyticsManager

**Namespace:** `Nexus\QueryEngine\Services\AnalyticsManager`

**Purpose:** Main orchestrator for analytics query execution with result caching and telemetry tracking.

#### Constructor

```php
public function __construct(
    private readonly QueryExecutorInterface $queryExecutor,
    private readonly ?LoggerInterface $logger = null,
    private readonly ?TelemetryTrackerInterface $telemetry = null
)
```

**Parameters:**
- `$queryExecutor` - Query execution engine
- `$logger` - PSR-3 logger (optional, for debugging)
- `$telemetry` - Telemetry tracker (optional, for performance monitoring)

#### Methods

##### executeQuery()

Execute an analytics query and return results.

```php
public function executeQuery(
    QueryDefinition $queryDefinition,
    GuardContextInterface $guardContext
): AnalyticsResult
```

**Parameters:**
- `$queryDefinition` - Immutable query definition (data sources, measures, dimensions, filters)
- `$guardContext` - User context for row-level security evaluation

**Returns:** `AnalyticsResult` - Query results with metadata

**Throws:**
- `InvalidQueryDefinitionException` - Query validation failed
- `QueryExecutionFailedException` - Execution failed
- `GuardEvaluationFailedException` - Row-level security check failed

**Example:**
```php
$result = $analyticsManager->executeQuery($queryDefinition, $guardContext);

foreach ($result->getRows() as $row) {
    echo "Region: {$row['region']}, Revenue: {$row['total_revenue']}\n";
}
```

---

##### validateQuery()

Validate a query definition without executing it.

```php
public function validateQuery(QueryDefinition $queryDefinition): bool
```

**Parameters:**
- `$queryDefinition` - Query to validate

**Returns:** `bool` - True if valid

**Throws:**
- `InvalidQueryDefinitionException` - If validation fails

**Example:**
```php
try {
    $analyticsManager->validateQuery($queryDefinition);
    echo "Query is valid\n";
} catch (InvalidQueryDefinitionException $e) {
    echo "Validation failed: " . $e->getMessage();
}
```

---

## Value Objects

### QueryDefinition

**Namespace:** `Nexus\QueryEngine\ValueObjects\QueryDefinition`

**Purpose:** Immutable query definition object.

#### Constructor

```php
public function __construct(
    private readonly array $dataSources,
    private readonly array $measures,
    private readonly array $dimensions,
    private readonly array $filters = [],
    private readonly array $guards = [],
    private readonly array $groupBy = [],
    private readonly array $orderBy = [],
    private readonly ?int $limit = null,
    private readonly int $offset = 0
)
```

**Parameters:**

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `$dataSources` | `array` | Yes | Data source names | `['customers', 'sales']` |
| `$measures` | `array` | Yes | Metrics to calculate | `[['column' => 'revenue', 'aggregation' => 'sum', 'alias' => 'total_revenue']]` |
| `$dimensions` | `array` | Yes | Grouping dimensions | `['region', 'product_category']` |
| `$filters` | `array` | No | WHERE conditions | `[['column' => 'status', 'operator' => '=', 'value' => 'active']]` |
| `$guards` | `array` | No | Row-level security conditions | `[['expression' => 'region = :user_region', 'parameters' => ['user_region' => 'Asia']]]` |
| `$groupBy` | `array` | No | GROUP BY columns | `['region', 'month']` |
| `$orderBy` | `array` | No | ORDER BY columns | `[['column' => 'revenue', 'direction' => 'desc']]` |
| `$limit` | `?int` | No | Max rows to return | `100` |
| `$offset` | `int` | No | Skip rows (pagination) | `20` |

#### Measure Format

```php
[
    'column' => 'revenue',        // Column name
    'aggregation' => 'sum',       // Aggregation function (sum, avg, count, min, max, count_distinct)
    'alias' => 'total_revenue'    // Result column alias
]
```

#### Filter Format

```php
[
    'column' => 'status',         // Column name
    'operator' => '=',            // Operator (=, !=, >, >=, <, <=, in, not_in, between, like, is_null, is_not_null)
    'value' => 'active'           // Filter value (can be scalar or array for 'in', 'between')
]
```

#### Guard Format

```php
[
    'expression' => 'region IN (:allowed_regions)',  // SQL-like expression
    'parameters' => [
        'allowed_regions' => ['Asia', 'Europe']      // Bound parameters
    ]
]
```

#### Methods

##### getDataSources()

```php
public function getDataSources(): array
```

**Returns:** Array of data source names

---

##### getMeasures()

```php
public function getMeasures(): array
```

**Returns:** Array of measure definitions

---

##### getDimensions()

```php
public function getDimensions(): array
```

**Returns:** Array of dimension column names

---

##### getFilters()

```php
public function getFilters(): array
```

**Returns:** Array of filter conditions

---

##### getGuards()

```php
public function getGuards(): array
```

**Returns:** Array of guard conditions

---

##### getGroupBy()

```php
public function getGroupBy(): array
```

**Returns:** Array of GROUP BY column names

---

##### getOrderBy()

```php
public function getOrderBy(): array
```

**Returns:** Array of ORDER BY definitions

---

##### getLimit()

```php
public function getLimit(): ?int
```

**Returns:** Limit value (null if unlimited)

---

##### getOffset()

```php
public function getOffset(): int
```

**Returns:** Offset value (default: 0)

---

### AnalyticsResult

**Namespace:** `Nexus\QueryEngine\ValueObjects\AnalyticsResult`

**Purpose:** Immutable query result object.

#### Constructor

```php
public function __construct(
    private readonly array $rows,
    private readonly array $columns,
    private readonly int $totalRows,
    private readonly array $dataSourcesUsed,
    private readonly float $executionTimeMs,
    private readonly array $metadata = []
)
```

**Parameters:**
- `$rows` - Result rows (array of associative arrays)
- `$columns` - Column names
- `$totalRows` - Total matching rows (before limit/offset)
- `$dataSourcesUsed` - Data sources queried
- `$executionTimeMs` - Query execution time in milliseconds
- `$metadata` - Additional metadata (e.g., cache hit, optimization info)

#### Methods

##### getRows()

```php
public function getRows(): array
```

**Returns:** Array of result rows

**Example:**
```php
foreach ($result->getRows() as $row) {
    echo $row['region'] . ': ' . $row['total_revenue'] . "\n";
}
```

---

##### getColumns()

```php
public function getColumns(): array
```

**Returns:** Array of column names

**Example:**
```php
$columns = $result->getColumns();
// ['region', 'total_revenue', 'customer_count']
```

---

##### getTotalRows()

```php
public function getTotalRows(): int
```

**Returns:** Total rows matching query (before pagination)

**Example:**
```php
$total = $result->getTotalRows();
$returned = count($result->getRows());
echo "Showing {$returned} of {$total} rows\n";
```

---

##### getDataSourcesUsed()

```php
public function getDataSourcesUsed(): array
```

**Returns:** Array of data source names used in query

---

##### getExecutionTimeMs()

```php
public function getExecutionTimeMs(): float
```

**Returns:** Query execution time in milliseconds

---

##### getMetadata()

```php
public function getMetadata(): array
```

**Returns:** Additional metadata (cache hit, optimization info, etc.)

---

##### getMetadataValue()

```php
public function getMetadataValue(string $key, mixed $default = null): mixed
```

**Parameters:**
- `$key` - Metadata key
- `$default` - Default value if key not found

**Returns:** Metadata value or default

---

## Exceptions

All exceptions extend PHP's base `\Exception` class.

### InvalidQueryDefinitionException

**Namespace:** `Nexus\QueryEngine\Exceptions\InvalidQueryDefinitionException`

**Thrown when:** Query validation fails (missing required fields, invalid aggregation, etc.)

**Example:**
```php
throw new InvalidQueryDefinitionException(
    "Measures array cannot be empty"
);
```

---

### QueryExecutionFailedException

**Namespace:** `Nexus\QueryEngine\Exceptions\QueryExecutionFailedException`

**Thrown when:** Query execution fails (data source unavailable, timeout, etc.)

**Example:**
```php
throw new QueryExecutionFailedException(
    "Data source 'customers' is unavailable"
);
```

---

### GuardEvaluationFailedException

**Namespace:** `Nexus\QueryEngine\Exceptions\GuardEvaluationFailedException`

**Thrown when:** Row-level security check fails

**Example:**
```php
throw new GuardEvaluationFailedException(
    "User does not have access to region 'Asia'"
);
```

---

### InvalidFilterExpressionException

**Namespace:** `Nexus\QueryEngine\Exceptions\InvalidFilterExpressionException`

**Thrown when:** Filter expression is malformed or invalid

---

### InvalidGuardExpressionException

**Namespace:** `Nexus\QueryEngine\Exceptions\InvalidGuardExpressionException`

**Thrown when:** Guard expression is malformed or invalid

---

### DataSourceNotFoundException

**Namespace:** `Nexus\QueryEngine\Exceptions\DataSourceNotFoundException`

**Thrown when:** Requested data source does not exist

---

### DataAggregationFailedException

**Namespace:** `Nexus\QueryEngine\Exceptions\DataAggregationFailedException`

**Thrown when:** Data aggregation fails (e.g., schema mismatch, type conversion error)

---

### InvalidDataSourceException

**Namespace:** `Nexus\QueryEngine\Exceptions\InvalidDataSourceException`

**Thrown when:** Data source configuration or implementation is invalid

---

## Core Engine Contracts

### DataSourceInterface

**Namespace:** `Nexus\QueryEngine\Core\Contracts\DataSourceInterface`

**Purpose:** Contract for data source implementations (databases, APIs, files, etc.)

#### Methods

##### getName()

```php
public function getName(): string
```

**Returns:** Unique data source name

**Example:**
```php
public function getName(): string
{
    return 'customers';
}
```

---

##### fetch()

```php
public function fetch(array $filters = [], array $columns = []): array
```

**Parameters:**
- `$filters` - Filter conditions to apply
- `$columns` - Columns to retrieve (empty = all columns)

**Returns:** Array of rows (each row is an associative array)

**Example:**
```php
public function fetch(array $filters = [], array $columns = []): array
{
    // Apply filters and fetch from database
    $query = $this->repository->query();
    
    foreach ($filters as $filter) {
        $query->where($filter['column'], $filter['operator'], $filter['value']);
    }
    
    return $query->get()->toArray();
}
```

---

##### getSchema()

```php
public function getSchema(): array
```

**Returns:** Schema definition (column name => data type mapping)

**Example:**
```php
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
```

---

### GuardContextInterface

**Namespace:** `Nexus\QueryEngine\Core\Contracts\GuardContextInterface`

**Purpose:** Contract for user context in row-level security evaluation

#### Methods

##### getUserId()

```php
public function getUserId(): string
```

**Returns:** Current user ID

---

##### getTenantId()

```php
public function getTenantId(): string
```

**Returns:** Current tenant ID

---

##### hasRole()

```php
public function hasRole(string $role): bool
```

**Parameters:**
- `$role` - Role name to check

**Returns:** True if user has role

---

##### hasPermission()

```php
public function hasPermission(string $permission): bool
```

**Parameters:**
- `$permission` - Permission name to check

**Returns:** True if user has permission

---

##### getAttribute()

```php
public function getAttribute(string $key): mixed
```

**Parameters:**
- `$key` - Attribute key

**Returns:** Attribute value (or null if not found)

**Example:**
```php
$userRegion = $guardContext->getAttribute('user_region');
$allowedDepartments = $guardContext->getAttribute('accessible_departments');
```

---

### QueryExecutorInterface

**Namespace:** `Nexus\QueryEngine\Core\Contracts\QueryExecutorInterface`

**Purpose:** Contract for query execution engine (implemented by `QueryExecutor`)

#### Methods

##### execute()

```php
public function execute(
    QueryDefinition $queryDefinition,
    GuardContextInterface $guardContext
): AnalyticsResult
```

**Parameters:**
- `$queryDefinition` - Query to execute
- `$guardContext` - User context for guards

**Returns:** `AnalyticsResult` - Query results

**Throws:**
- `InvalidQueryDefinitionException`
- `QueryExecutionFailedException`
- `GuardEvaluationFailedException`

---

### DataSourceAggregatorInterface

**Namespace:** `Nexus\QueryEngine\Core\Contracts\DataSourceAggregatorInterface`

**Purpose:** Contract for data source aggregation (implemented by `DataSourceAggregator`)

#### Methods

##### aggregate()

```php
public function aggregate(array $dataSourceNames, array $filters = []): array
```

**Parameters:**
- `$dataSourceNames` - Data sources to aggregate
- `$filters` - Filters to apply

**Returns:** Aggregated data rows

---

### GuardEvaluatorInterface

**Namespace:** `Nexus\QueryEngine\Core\Contracts\GuardEvaluatorInterface`

**Purpose:** Contract for guard evaluation (implemented by `GuardEvaluator`)

#### Methods

##### evaluate()

```php
public function evaluate(
    array $guards,
    array $data,
    GuardContextInterface $guardContext
): array
```

**Parameters:**
- `$guards` - Guard expressions to evaluate
- `$data` - Data rows to filter
- `$guardContext` - User context

**Returns:** Filtered data rows (only rows passing guards)

---

## Usage Patterns

### Pattern 1: Simple Dashboard Query

```php
use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\ValueObjects\QueryDefinition;

// Dependency injection
public function __construct(
    private readonly AnalyticsManager $analyticsManager
) {}

// Execute query
public function getSalesByRegion(): array
{
    $query = new QueryDefinition(
        dataSources: ['sales'],
        measures: [['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales']],
        dimensions: ['region'],
        groupBy: ['region'],
        orderBy: [['column' => 'total_sales', 'direction' => 'desc']]
    );
    
    $result = $this->analyticsManager->executeQuery($query, $this->getGuardContext());
    
    return $result->getRows();
}
```

### Pattern 2: Multi-Dimensional Analysis with Filters

```php
public function getProductPerformance(string $startDate, string $endDate): AnalyticsResult
{
    $query = new QueryDefinition(
        dataSources: ['sales', 'products'],
        measures: [
            ['column' => 'revenue', 'aggregation' => 'sum', 'alias' => 'total_revenue'],
            ['column' => 'quantity', 'aggregation' => 'sum', 'alias' => 'total_quantity'],
            ['column' => 'cost', 'aggregation' => 'sum', 'alias' => 'total_cost']
        ],
        dimensions: ['product_category', 'region', 'month'],
        filters: [
            ['column' => 'sale_date', 'operator' => 'between', 'value' => [$startDate, $endDate]],
            ['column' => 'status', 'operator' => '=', 'value' => 'completed']
        ],
        groupBy: ['product_category', 'region', 'month'],
        orderBy: [['column' => 'total_revenue', 'direction' => 'desc']],
        limit: 100
    );
    
    return $this->analyticsManager->executeQuery($query, $this->getGuardContext());
}
```

### Pattern 3: Row-Level Security with Guards

```php
public function getRestrictedCustomerAnalytics(): AnalyticsResult
{
    $guardContext = $this->getGuardContext();
    $userRegions = $guardContext->getAttribute('accessible_regions');
    
    $query = new QueryDefinition(
        dataSources: ['customers', 'sales'],
        measures: [
            ['column' => 'revenue', 'aggregation' => 'sum', 'alias' => 'total_revenue'],
            ['column' => 'customer_id', 'aggregation' => 'count_distinct', 'alias' => 'customer_count']
        ],
        dimensions: ['region', 'industry'],
        guards: [
            [
                'expression' => 'region IN (:allowed_regions)',
                'parameters' => ['allowed_regions' => $userRegions]
            ],
            [
                'expression' => 'tenant_id = :tenant_id',
                'parameters' => ['tenant_id' => $guardContext->getTenantId()]
            ]
        ],
        groupBy: ['region', 'industry'],
        orderBy: [['column' => 'total_revenue', 'direction' => 'desc']]
    );
    
    return $this->analyticsManager->executeQuery($query, $guardContext);
}
```

---

**See Also:**
- [Getting Started Guide](getting-started.md) - Quick start and basic usage
- [Integration Guide](integration-guide.md) - Laravel and Symfony integration
- [Examples](examples/) - Complete working examples

---

**Last Updated:** 2024-11-24  
**Package Version:** 1.0.0 (Development)
