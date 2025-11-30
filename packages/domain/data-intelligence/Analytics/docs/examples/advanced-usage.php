<?php declare(strict_types=1);

/**
 * Advanced Analytics Usage Examples
 * 
 * Demonstrates complex queries, multi-dimensional analysis, and row-level security.
 */

use Nexus\Analytics\Services\AnalyticsManager;
use Nexus\Analytics\ValueObjects\QueryDefinition;

// Multi-dimensional sales analysis with drill-down capability
function getMultiDimensionalSalesAnalysis(
    AnalyticsManager $analyticsManager,
    $guardContext
): array {
    $query = new QueryDefinition(
        dataSources: ['sales', 'customers', 'products'],
        measures: [
            ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'total_revenue'],
            ['column' => 'quantity', 'aggregation' => 'sum', 'alias' => 'total_quantity'],
            ['column' => 'cost', 'aggregation' => 'sum', 'alias' => 'total_cost'],
            ['column' => 'sale_id', 'aggregation' => 'count', 'alias' => 'order_count']
        ],
        dimensions: ['region', 'product_category', 'customer_segment', 'month'],
        filters: [
            ['column' => 'sale_date', 'operator' => '>=', 'value' => '2024-01-01'],
            ['column' => 'status', 'operator' => '=', 'value' => 'completed']
        ],
        guards: [
            ['expression' => 'region IN (:allowed_regions)', 'parameters' => [
                'allowed_regions' => $guardContext->getAttribute('accessible_regions')
            ]],
            ['expression' => 'tenant_id = :tenant_id', 'parameters' => [
                'tenant_id' => $guardContext->getTenantId()
            ]]
        ],
        groupBy: ['region', 'product_category', 'customer_segment', 'month'],
        orderBy: [['column' => 'total_revenue', 'direction' => 'desc']],
        limit: 100
    );
    
    $result = $analyticsManager->executeQuery($query, $guardContext);
    
    // Calculate profit margin
    $enrichedData = array_map(function($row) {
        $profit = $row['total_revenue'] - $row['total_cost'];
        $margin = $row['total_revenue'] > 0 
            ? ($profit / $row['total_revenue']) * 100 
            : 0;
            
        return array_merge($row, [
            'profit' => $profit,
            'profit_margin_pct' => round($margin, 2)
        ]);
    }, $result->getRows());
    
    return $enrichedData;
}

// Customer cohort analysis
function getCustomerCohortAnalysis(
    AnalyticsManager $analyticsManager,
    $guardContext
): array {
    $query = new QueryDefinition(
        dataSources: ['customers', 'sales'],
        measures: [
            ['column' => 'customer_id', 'aggregation' => 'count_distinct', 'alias' => 'customer_count'],
            ['column' => 'amount', 'aggregation' => 'sum', 'alias' => 'lifetime_value'],
            ['column' => 'amount', 'aggregation' => 'avg', 'alias' => 'avg_lifetime_value']
        ],
        dimensions: ['signup_year', 'customer_segment'],
        groupBy: ['signup_year', 'customer_segment'],
        orderBy: [
            ['column' => 'signup_year', 'direction' => 'desc'],
            ['column' => 'lifetime_value', 'direction' => 'desc']
        ]
    );
    
    return $analyticsManager->executeQuery($query, $guardContext)->getRows();
}
