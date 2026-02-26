<?php declare(strict_types=1);

/**
 * Basic Analytics Usage Examples
 * 
 * Demonstrates simple queries and common use cases for the Nexus Analytics package.
 */

use Nexus\QueryEngine\Services\AnalyticsManager;
use Nexus\QueryEngine\ValueObjects\QueryDefinition;
use Nexus\QueryEngine\ValueObjects\AnalyticsResult;

// Assume $analyticsManager and $guardContext are injected
// public function __construct(
//     private readonly AnalyticsManager $analyticsManager,
//     private readonly GuardContextInterface $guardContext
// ) {}

// ============================================================================
// Example 1: Simple Revenue by Region
// ============================================================================

function getSalesByRegion(AnalyticsManager $analyticsManager, $guardContext): array
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
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    echo "Sales by Region:\n";
    foreach ($result->getRows() as $row) {
        echo sprintf(
            "  %s: $%s\n",
            $row['region'],
            number_format($row['total_sales'], 2)
        );
    }
    
    return $result->getRows();
}

// ============================================================================
// Example 2: Customer Count by Industry
// ============================================================================

function getCustomerCountByIndustry(AnalyticsManager $analyticsManager, $guardContext): array
{
    $query = new QueryDefinition(
        dataSources: ['customers'],
        measures: [
            ['column' => 'customer_id', 'aggregation' => 'count', 'alias' => 'customer_count']
        ],
        dimensions: ['industry'],
        groupBy: ['industry'],
        orderBy: [['column' => 'customer_count', 'direction' => 'desc']],
        limit: 10
    );
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    echo "Top 10 Industries by Customer Count:\n";
    foreach ($result->getRows() as $row) {
        echo sprintf(
            "  %s: %d customers\n",
            $row['industry'],
            $row['customer_count']
        );
    }
    
    return $result->getRows();
}

// ============================================================================
// Example 3: Product Sales with Filters
// ============================================================================

function getProductSales(
    AnalyticsManager $analyticsManager,
    $guardContext,
    string $startDate,
    string $endDate
): array {
    $query = new QueryDefinition(
        dataSources: ['sales', 'products'],
        measures: [
            ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_revenue'],
            ['column' => 'quantity', 'aggregation' => 'sum', 'alias' => 'total_quantity']
        ],
        dimensions: ['product_name', 'product_category'],
        filters: [
            ['column' => 'sale_date', 'operator' => 'between', 'value' => [$startDate, $endDate]],
            ['column' => 'status', 'operator' => '=', 'value' => 'completed']
        ],
        groupBy: ['product_name', 'product_category'],
        orderBy: [['column' => 'total_revenue', 'direction' => 'desc']],
        limit: 20
    );
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    echo "Product Sales ($startDate to $endDate):\n";
    foreach ($result->getRows() as $row) {
        echo sprintf(
            "  %s (%s): $%s - %d units\n",
            $row['product_name'],
            $row['product_category'],
            number_format($row['total_revenue'], 2),
            $row['total_quantity']
        );
    }
    
    echo "\nExecution Time: {$result->getExecutionTimeMs()}ms\n";
    
    return $result->getRows();
}

// ============================================================================
// Example 4: Monthly Sales Trend
// ============================================================================

function getMonthlySalesTrend(
    AnalyticsManager $analyticsManager,
    $guardContext,
    int $year
): array {
    $query = new QueryDefinition(
        dataSources: ['sales'],
        measures: [
            ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales'],
            ['column' => 'sale_id', 'aggregation' => 'count', 'alias' => 'order_count'],
            ['column' => 'amount', 'aggregation' => 'avg', 'alias' => 'avg_order_value']
        ],
        dimensions: ['month'],
        filters: [
            ['column' => 'sale_date', 'operator' => '>=', 'value' => "{$year}-01-01"],
            ['column' => 'sale_date', 'operator' => '<=', 'value' => "{$year}-12-31"]
        ],
        groupBy: ['month'],
        orderBy: [['column' => 'month', 'direction' => 'asc']]
    );
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    echo "Monthly Sales Trend ({$year}):\n";
    foreach ($result->getRows() as $row) {
        echo sprintf(
            "  %s: $%s (%d orders, avg: $%s)\n",
            $row['month'],
            number_format($row['total_sales'], 2),
            $row['order_count'],
            number_format($row['avg_order_value'], 2)
        );
    }
    
    return $result->getRows();
}

// ============================================================================
// Example 5: Top Customers by Revenue
// ============================================================================

function getTopCustomers(AnalyticsManager $analyticsManager, $guardContext, int $limit = 10): array
{
    $query = new QueryDefinition(
        dataSources: ['sales', 'customers'],
        measures: [
            ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_revenue'],
            ['column' => 'sale_id', 'aggregation' => 'count', 'alias' => 'order_count']
        ],
        dimensions: ['customer_name', 'region'],
        filters: [
            ['column' => 'status', 'operator' => '=', 'value' => 'completed']
        ],
        groupBy: ['customer_id', 'customer_name', 'region'],
        orderBy: [['column' => 'total_revenue', 'direction' => 'desc']],
        limit: $limit
    );
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    echo "Top {$limit} Customers:\n";
    foreach ($result->getRows() as $index => $row) {
        echo sprintf(
            "  %d. %s (%s): $%s - %d orders\n",
            $index + 1,
            $row['customer_name'],
            $row['region'],
            number_format($row['total_revenue'], 2),
            $row['order_count']
        );
    }
    
    return $result->getRows();
}

// ============================================================================
// Example 6: Inventory Stock Levels
// ============================================================================

function getCurrentStockLevels(AnalyticsManager $analyticsManager, $guardContext): array
{
    $query = new QueryDefinition(
        dataSources: ['inventory', 'products'],
        measures: [
            ['column' => 'quantity', 'aggregation' => 'sum', 'alias' => 'total_quantity'],
            ['column' => 'value', 'aggregation' => 'sum', 'alias' => 'total_value']
        ],
        dimensions: ['warehouse', 'product_category'],
        filters: [
            ['column' => 'status', 'operator' => '=', 'value' => 'available']
        ],
        groupBy: ['warehouse', 'product_category'],
        orderBy: [['column' => 'total_value', 'direction' => 'desc']]
    );
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    echo "Stock Levels by Warehouse:\n";
    foreach ($result->getRows() as $row) {
        echo sprintf(
            "  %s - %s: %d units ($%s)\n",
            $row['warehouse'],
            $row['product_category'],
            $row['total_quantity'],
            number_format($row['total_value'], 2)
        );
    }
    
    return $result->getRows();
}

// ============================================================================
// Example 7: Employee Headcount by Department
// ============================================================================

function getHeadcountByDepartment(AnalyticsManager $analyticsManager, $guardContext): array
{
    $query = new QueryDefinition(
        dataSources: ['employees'],
        measures: [
            ['column' => 'employee_id', 'aggregation' => 'count', 'alias' => 'headcount'],
            ['column' => 'salary', 'aggregation' => 'sum', 'alias' => 'total_payroll'],
            ['column' => 'salary', 'aggregation' => 'avg', 'alias' => 'avg_salary']
        ],
        dimensions: ['department'],
        filters: [
            ['column' => 'status', 'operator' => '=', 'value' => 'active']
        ],
        groupBy: ['department'],
        orderBy: [['column' => 'headcount', 'direction' => 'desc']]
    );
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    echo "Headcount by Department:\n";
    foreach ($result->getRows() as $row) {
        echo sprintf(
            "  %s: %d employees (Payroll: $%s, Avg Salary: $%s)\n",
            $row['department'],
            $row['headcount'],
            number_format($row['total_payroll'], 2),
            number_format($row['avg_salary'], 2)
        );
    }
    
    return $result->getRows();
}

// ============================================================================
// Example 8: Pagination Example
// ============================================================================

function getPaginatedSales(
    AnalyticsManager $analyticsManager,
    $guardContext,
    int $page = 1,
    int $pageSize = 20
): array {
    $offset = ($page - 1) * $pageSize;
    
    $query = new QueryDefinition(
        dataSources: ['sales'],
        measures: [
            ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales']
        ],
        dimensions: ['product_name'],
        groupBy: ['product_name'],
        orderBy: [['column' => 'total_sales', 'direction' => 'desc']],
        limit: $pageSize,
        offset: $offset
    );
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    $totalRows = $result->getTotalRows();
    $totalPages = ceil($totalRows / $pageSize);
    
    echo "Sales by Product (Page {$page} of {$totalPages}):\n";
    foreach ($result->getRows() as $row) {
        echo sprintf(
            "  %s: $%s\n",
            $row['product_name'],
            number_format($row['total_sales'], 2)
        );
    }
    
    echo "\nShowing " . count($result->getRows()) . " of {$totalRows} results\n";
    
    return $result->getRows();
}

// ============================================================================
// Example 9: Error Handling
// ============================================================================

function safeExecuteQuery(AnalyticsManager $analyticsManager, $guardContext): ?array
{
    try {
        $query = new QueryDefinition(
            dataSources: ['sales'],
            measures: [
                ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_sales']
            ],
            dimensions: ['region'],
            groupBy: ['region']
        );
        
        $result = $analyticsManager->executeQuery($query, $guardContext);
        return $result->getRows();
        
    } catch (\Nexus\QueryEngine\Exceptions\InvalidQueryDefinitionException $e) {
        echo "Invalid query: " . $e->getMessage() . "\n";
        return null;
    } catch (\Nexus\QueryEngine\Exceptions\GuardEvaluationFailedException $e) {
        echo "Access denied: " . $e->getMessage() . "\n";
        return null;
    } catch (\Nexus\QueryEngine\Exceptions\QueryExecutionFailedException $e) {
        echo "Query execution failed: " . $e->getMessage() . "\n";
        return null;
    }
}
