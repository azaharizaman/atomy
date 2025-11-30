<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples: Nexus AuditLogger Package
 * 
 * This file demonstrates advanced scenarios including:
 * - Auditable trait for automatic logging
 * - Batch operations
 * - Sensitive data masking
 * - Retention policy enforcement
 * - Multi-format exports
 */

use Nexus\AuditLogger\Services\{
    AuditLogManager,
    AuditLogSearchService,
    AuditLogExportService,
    RetentionPolicyService,
    SensitiveDataMasker
};
use Nexus\AuditLogger\Contracts\{
    AuditLogRepositoryInterface,
    AuditConfigInterface
};
use Nexus\AuditLogger\ValueObjects\{AuditLevel, RetentionPolicy};

// Assume dependency injection provides these implementations
/** @var AuditLogRepositoryInterface $repository */
/** @var AuditConfigInterface $config */

// ============================================================================
// Example 1: Using Auditable Trait (Automatic CRUD Logging)
// ============================================================================

/**
 * Example model with automatic audit logging via trait.
 * 
 * This approach eliminates manual logging for standard CRUD operations.
 */
class Customer
{
    use AuditableTrait;
    
    public string $id;
    public string $name;
    public string $email;
    public string $status;
    public string $tenantId;
    
    protected function getAuditLevel(): int
    {
        // Customize audit level based on entity importance
        return AuditLevel::High->value;
    }
    
    protected function getAuditLogName(string $action): string
    {
        // Customize log names
        return "customer_{$action}";
    }
}

// When you create/update/delete a Customer, audit logs are created automatically
$customer = new Customer();
$customer->id = 'customer_001';
$customer->name = 'Acme Corp';
$customer->email = 'contact@acme.com';
$customer->status = 'active';
$customer->tenantId = 'tenant_12345';

// Trait automatically logs: "customer_created"
// (This would be triggered by ORM events in real implementation)

echo "Auditable trait handles automatic CRUD logging\n";

// ============================================================================
// Example 2: Batch Operations with UUID Grouping
// ============================================================================

$auditManager = new AuditLogManager($repository, $config);

// Generate a batch UUID to group related operations
$batchUuid = \Ramsey\Uuid\Uuid::uuid4()->toString();

// Perform bulk operation and log each with same batch UUID
$customerIds = ['cust_001', 'cust_002', 'cust_003'];

foreach ($customerIds as $customerId) {
    $auditManager->log(
        logName: 'customer_status_changed',
        description: "Customer {$customerId} marked as inactive",
        subjectType: 'Customer',
        subjectId: $customerId,
        causerType: 'System',
        causerId: 'bulk_deactivation_job',
        properties: [
            'old_status' => 'active',
            'new_status' => 'inactive',
            'reason' => 'Bulk deactivation process',
        ],
        level: AuditLevel::Medium->value,
        tenantId: 'tenant_12345',
        batchUuid: $batchUuid
    );
}

echo "Logged batch operation with UUID: {$batchUuid}\n";

// Query all logs from this batch
$searchService = new AuditLogSearchService($repository);
$batchLogs = array_filter(
    $repository->findAll('tenant_12345'),
    fn($log) => $log->getBatchUuid() === $batchUuid
);

echo "Batch contains " . count($batchLogs) . " operations\n";

// ============================================================================
// Example 3: Sensitive Data Masking
// ============================================================================

$masker = new SensitiveDataMasker($config);

// Example user data with sensitive fields
$userData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'SuperSecret123!',
    'api_token' => 'sk_live_abc123xyz789',
    'credit_card' => '4111111111111111',
    'address' => '123 Main St',
];

// Mask sensitive fields before logging
$maskedData = $masker->mask($userData);

$auditManager->log(
    logName: 'user_created',
    description: 'New user account created',
    subjectType: 'User',
    subjectId: 'user_12345',
    properties: ['attributes' => $maskedData],
    level: AuditLevel::High->value,
    tenantId: 'tenant_12345'
);

echo "Logged with sensitive data masked:\n";
print_r($maskedData);
// Output:
// [
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'password' => '***MASKED***',
//     'api_token' => '***MASKED***',
//     'credit_card' => '***MASKED***',
//     'address' => '123 Main St',
// ]

// ============================================================================
// Example 4: Retention Policy Management
// ============================================================================

$retentionService = new RetentionPolicyService($repository, $config);

// Create retention policy: Keep logs for 90 days
$policy = new RetentionPolicy(retentionDays: 90);

// Check if retention is needed
if ($retentionService->isRetentionDue(policy: $policy, tenantId: 'tenant_12345')) {
    echo "Retention policy enforcement is due\n";
    
    // Purge expired logs
    $deletedCount = $retentionService->purgeExpiredLogs(
        policy: $policy,
        tenantId: 'tenant_12345'
    );
    
    echo "Purged {$deletedCount} expired audit logs\n";
}

// Create different retention policies for different log levels
$criticalPolicy = RetentionPolicy::forever(); // Never delete
$lowPolicy = new RetentionPolicy(retentionDays: 30);

echo "Critical logs kept forever, low priority logs kept for 30 days\n";

// ============================================================================
// Example 5: Advanced Search with Multiple Filters
// ============================================================================

// Complex search: High/Critical customer changes in Q4 2024
$startDate = new \DateTimeImmutable('2024-10-01');
$endDate = new \DateTimeImmutable('2024-12-31');

$q4CustomerChanges = $searchService->search(
    tenantId: 'tenant_12345',
    keyword: 'customer',
    entityType: 'Customer',
    startDate: $startDate,
    endDate: $endDate,
    level: AuditLevel::High->value,
    limit: 500
);

echo "Found " . count($q4CustomerChanges) . " high-priority customer changes in Q4 2024\n";

// Search with pagination
$page1 = $searchService->search(
    tenantId: 'tenant_12345',
    limit: 100,
    offset: 0
);

$page2 = $searchService->search(
    tenantId: 'tenant_12345',
    limit: 100,
    offset: 100
);

echo "Page 1: " . count($page1) . " logs, Page 2: " . count($page2) . " logs\n";

// ============================================================================
// Example 6: Multi-Format Export
// ============================================================================

$exportService = new AuditLogExportService($repository);

// Export to CSV
$csvData = $exportService->export(
    format: 'csv',
    tenantId: 'tenant_12345',
    startDate: new \DateTimeImmutable('-7 days'),
    endDate: new \DateTimeImmutable()
);

file_put_contents('/tmp/audit_last_7_days.csv', $csvData);
echo "Exported CSV: " . strlen($csvData) . " bytes\n";

// Export to JSON
$jsonData = $exportService->export(
    format: 'json',
    tenantId: 'tenant_12345',
    level: AuditLevel::Critical->value
);

file_put_contents('/tmp/critical_audit_logs.json', $jsonData);
echo "Exported JSON: " . strlen($jsonData) . " bytes\n";

// Export to PDF (if supported)
if (in_array('pdf', $config->getExportFormats())) {
    $pdfData = $exportService->export(
        format: 'pdf',
        tenantId: 'tenant_12345',
        entityType: 'Invoice',
        limit: 100
    );
    
    file_put_contents('/tmp/invoice_audit_report.pdf', $pdfData);
    echo "Exported PDF: " . strlen($pdfData) . " bytes\n";
}

// ============================================================================
// Example 7: Compliance Audit Report
// ============================================================================

// Generate comprehensive compliance report
$reportStartDate = new \DateTimeImmutable('2024-01-01');
$reportEndDate = new \DateTimeImmutable('2024-12-31');

$allLogs = $searchService->search(
    tenantId: 'tenant_12345',
    startDate: $reportStartDate,
    endDate: $reportEndDate,
    limit: 100000
);

// Analyze audit data
$analysis = [
    'total_logs' => count($allLogs),
    'by_level' => [
        'low' => 0,
        'medium' => 0,
        'high' => 0,
        'critical' => 0,
    ],
    'by_entity_type' => [],
    'by_month' => [],
    'top_users' => [],
];

foreach ($allLogs as $log) {
    // Count by level
    $levelName = AuditLevel::from($log->getLevel())->name;
    $analysis['by_level'][strtolower($levelName)]++;
    
    // Count by entity type
    $entityType = $log->getSubjectType() ?? 'system';
    if (!isset($analysis['by_entity_type'][$entityType])) {
        $analysis['by_entity_type'][$entityType] = 0;
    }
    $analysis['by_entity_type'][$entityType]++;
    
    // Count by month
    $month = $log->getCreatedAt()->format('Y-m');
    if (!isset($analysis['by_month'][$month])) {
        $analysis['by_month'][$month] = 0;
    }
    $analysis['by_month'][$month]++;
    
    // Count by user
    $causerId = $log->getCauserId() ?? 'system';
    if (!isset($analysis['top_users'][$causerId])) {
        $analysis['top_users'][$causerId] = 0;
    }
    $analysis['top_users'][$causerId]++;
}

// Sort top users
arsort($analysis['top_users']);
$analysis['top_users'] = array_slice($analysis['top_users'], 0, 10, true);

echo "\n=== Compliance Audit Report 2024 ===\n";
echo "Total Logs: " . $analysis['total_logs'] . "\n\n";

echo "By Audit Level:\n";
foreach ($analysis['by_level'] as $level => $count) {
    echo "  " . ucfirst($level) . ": {$count}\n";
}

echo "\nBy Entity Type:\n";
arsort($analysis['by_entity_type']);
foreach ($analysis['by_entity_type'] as $type => $count) {
    echo "  {$type}: {$count}\n";
}

echo "\nTop 10 Most Active Users:\n";
foreach ($analysis['top_users'] as $userId => $count) {
    echo "  {$userId}: {$count} actions\n";
}

// ============================================================================
// Example 8: Async Logging (Laravel Queue Example)
// ============================================================================

/**
 * For high-volume systems, queue audit logging to avoid blocking requests.
 */
if ($config->isAsyncEnabled()) {
    echo "\nAsync logging enabled - logs processed via queue\n";
    
    // In Laravel, you would dispatch a job:
    // dispatch(new ProcessAuditLogJob($auditLog));
    
    // The job would then call:
    // $repository->save($auditLog);
}

// ============================================================================
// Example 9: Entity Activity Timeline with Aggregation
// ============================================================================

// Get detailed timeline for specific customer
$customerId = 'customer_001';

$timeline = $searchService->search(
    tenantId: 'tenant_12345',
    entityType: 'Customer',
    limit: 1000
);

$customerTimeline = array_filter($timeline, fn($log) => $log->getSubjectId() === $customerId);

// Aggregate activity by day
$activityByDay = [];
foreach ($customerTimeline as $log) {
    $day = $log->getCreatedAt()->format('Y-m-d');
    if (!isset($activityByDay[$day])) {
        $activityByDay[$day] = ['count' => 0, 'logs' => []];
    }
    $activityByDay[$day]['count']++;
    $activityByDay[$day]['logs'][] = $log;
}

echo "\nCustomer {$customerId} Activity Timeline:\n";
foreach ($activityByDay as $day => $data) {
    echo "  {$day}: {$data['count']} events\n";
}

// ============================================================================
// Example 10: Critical Action Monitoring
// ============================================================================

// Monitor critical actions and alert if threshold exceeded
$criticalLogs = $searchService->search(
    tenantId: 'tenant_12345',
    level: AuditLevel::Critical->value,
    startDate: new \DateTimeImmutable('-1 hour'),
    endDate: new \DateTimeImmutable()
);

$criticalThreshold = 10;

if (count($criticalLogs) > $criticalThreshold) {
    echo "\n⚠️  ALERT: " . count($criticalLogs) . " critical audit events in last hour (threshold: {$criticalThreshold})\n";
    
    // In production, trigger notification:
    // $notifier->sendAlert('Critical audit activity spike detected');
    
    // Log the anomaly
    $auditManager->log(
        logName: 'security_alert',
        description: "Critical audit activity spike: " . count($criticalLogs) . " events",
        level: AuditLevel::Critical->value,
        tenantId: 'tenant_12345',
        properties: [
            'event_count' => count($criticalLogs),
            'threshold' => $criticalThreshold,
            'time_window' => '1 hour',
        ]
    );
}

echo "\nAdvanced usage examples complete!\n";
