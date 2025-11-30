<?php

declare(strict_types=1);

/**
 * Basic Usage Examples: Nexus AuditLogger Package
 * 
 * This file demonstrates basic audit logging scenarios for user-facing activity tracking.
 */

use Nexus\AuditLogger\Services\{
    AuditLogManager,
    AuditLogSearchService,
    AuditLogExportService
};
use Nexus\AuditLogger\Contracts\{
    AuditLogRepositoryInterface,
    AuditConfigInterface
};
use Nexus\AuditLogger\ValueObjects\AuditLevel;

// Assume dependency injection provides these implementations
/** @var AuditLogRepositoryInterface $repository */
/** @var AuditConfigInterface $config */

// ============================================================================
// Example 1: Basic Manual Logging
// ============================================================================

$auditManager = new AuditLogManager($repository, $config);

// Log a simple user action (default level: Medium)
$logId = $auditManager->log(
    logName: 'user_login',
    description: 'User John Doe logged in successfully',
    level: AuditLevel::Low->value,
    tenantId: 'tenant_12345',
    ipAddress: '192.168.1.100',
    userAgent: 'Mozilla/5.0...'
);

echo "Created audit log: {$logId}\n";

// ============================================================================
// Example 2: Logging with Subject and Causer
// ============================================================================

// Log a user updating a customer record
$logId = $auditManager->log(
    logName: 'customer_updated',
    description: 'Customer "Acme Corp" updated by admin',
    subjectType: 'Customer',          // What was changed
    subjectId: 'customer_001',        // Which customer
    causerType: 'User',               // Who changed it
    causerId: 'user_admin_001',       // Which user
    level: AuditLevel::Medium->value,
    tenantId: 'tenant_12345'
);

echo "Logged customer update: {$logId}\n";

// ============================================================================
// Example 3: Logging with Before/After Data
// ============================================================================

// Capture before and after state
$oldCustomerData = [
    'name' => 'Acme Corp',
    'email' => 'old@acme.com',
    'status' => 'active',
];

$newCustomerData = [
    'name' => 'Acme Corporation',
    'email' => 'new@acme.com',
    'status' => 'active',
];

$logId = $auditManager->log(
    logName: 'customer_updated',
    description: 'Customer details updated',
    subjectType: 'Customer',
    subjectId: 'customer_001',
    causerType: 'User',
    causerId: 'user_admin_001',
    properties: [
        'old' => $oldCustomerData,
        'new' => $newCustomerData,
    ],
    level: AuditLevel::Medium->value,
    tenantId: 'tenant_12345'
);

echo "Logged with before/after data: {$logId}\n";

// ============================================================================
// Example 4: Searching Audit Logs
// ============================================================================

$searchService = new AuditLogSearchService($repository);

// Search for all customer-related logs
$customerLogs = $searchService->search(
    tenantId: 'tenant_12345',
    entityType: 'Customer',
    limit: 50
);

echo "Found " . count($customerLogs) . " customer audit logs\n";

// Search by keyword
$searchResults = $searchService->search(
    tenantId: 'tenant_12345',
    keyword: 'updated',
    limit: 100
);

echo "Found " . count($searchResults) . " logs containing 'updated'\n";

// Search by date range
$startDate = new \DateTimeImmutable('2024-01-01');
$endDate = new \DateTimeImmutable('2024-12-31');

$yearLogs = $searchService->search(
    tenantId: 'tenant_12345',
    startDate: $startDate,
    endDate: $endDate,
    limit: 1000
);

echo "Found " . count($yearLogs) . " logs from 2024\n";

// Search by audit level (Critical only)
$criticalLogs = $searchService->search(
    tenantId: 'tenant_12345',
    level: AuditLevel::Critical->value,
    limit: 100
);

echo "Found " . count($criticalLogs) . " critical audit logs\n";

// ============================================================================
// Example 5: Simple Export to CSV
// ============================================================================

$exportService = new AuditLogExportService($repository);

// Export all logs from last 30 days to CSV
$thirtyDaysAgo = new \DateTimeImmutable('-30 days');
$now = new \DateTimeImmutable();

$csvContent = $exportService->export(
    format: 'csv',
    tenantId: 'tenant_12345',
    startDate: $thirtyDaysAgo,
    endDate: $now
);

// Save to file
file_put_contents('/tmp/audit_logs_30days.csv', $csvContent);
echo "Exported last 30 days to CSV\n";

// ============================================================================
// Example 6: Export to JSON
// ============================================================================

$jsonContent = $exportService->export(
    format: 'json',
    tenantId: 'tenant_12345',
    entityType: 'Customer',
    limit: 100
);

file_put_contents('/tmp/customer_audit_logs.json', $jsonContent);
echo "Exported customer logs to JSON\n";

// ============================================================================
// Example 7: Get Audit Timeline for Specific Entity
// ============================================================================

// Get all audit logs for a specific customer (timeline view)
$customerId = 'customer_001';

$timeline = $searchService->search(
    tenantId: 'tenant_12345',
    entityType: 'Customer',
    limit: 100
);

// Filter by specific entity ID
$customerTimeline = array_filter($timeline, function($log) use ($customerId) {
    return $log->getSubjectId() === $customerId;
});

echo "Customer timeline contains " . count($customerTimeline) . " events\n";

// Display timeline
foreach ($customerTimeline as $log) {
    echo sprintf(
        "[%s] %s - %s\n",
        $log->getCreatedAt()->format('Y-m-d H:i:s'),
        $log->getLogName(),
        $log->getDescription()
    );
}

// ============================================================================
// Example 8: Viewing Log Details
// ============================================================================

// Retrieve specific audit log by ID
$specificLog = $repository->findById($logId);

echo "Log Details:\n";
echo "  ID: " . $specificLog->getId() . "\n";
echo "  Log Name: " . $specificLog->getLogName() . "\n";
echo "  Description: " . $specificLog->getDescription() . "\n";
echo "  Level: " . AuditLevel::from($specificLog->getLevel())->name . "\n";
echo "  Created: " . $specificLog->getCreatedAt()->format('Y-m-d H:i:s') . "\n";

// Display properties (before/after data)
$properties = $specificLog->getProperties();
if (!empty($properties['old']) && !empty($properties['new'])) {
    echo "  Changes:\n";
    
    $old = $properties['old'];
    $new = $properties['new'];
    
    foreach ($new as $field => $newValue) {
        if (isset($old[$field]) && $old[$field] !== $newValue) {
            echo "    - {$field}: '{$old[$field]}' → '{$newValue}'\n";
        }
    }
}

// ============================================================================
// Example 9: Count Logs by Level
// ============================================================================

// Get counts for each audit level
$lowCount = count($searchService->search(
    tenantId: 'tenant_12345',
    level: AuditLevel::Low->value
));

$mediumCount = count($searchService->search(
    tenantId: 'tenant_12345',
    level: AuditLevel::Medium->value
));

$highCount = count($searchService->search(
    tenantId: 'tenant_12345',
    level: AuditLevel::High->value
));

$criticalCount = count($searchService->search(
    tenantId: 'tenant_12345',
    level: AuditLevel::Critical->value
));

echo "\nAudit Log Summary:\n";
echo "  Low:      {$lowCount}\n";
echo "  Medium:   {$mediumCount}\n";
echo "  High:     {$highCount}\n";
echo "  Critical: {$criticalCount}\n";

// ============================================================================
// Example 10: Simple Compliance Report
// ============================================================================

// Generate compliance report for last quarter
$quarterStart = new \DateTimeImmutable('-3 months');
$quarterEnd = new \DateTimeImmutable();

$quarterLogs = $searchService->search(
    tenantId: 'tenant_12345',
    startDate: $quarterStart,
    endDate: $quarterEnd,
    limit: 10000
);

// Group by log name
$logsByType = [];
foreach ($quarterLogs as $log) {
    $logName = $log->getLogName();
    if (!isset($logsByType[$logName])) {
        $logsByType[$logName] = 0;
    }
    $logsByType[$logName]++;
}

echo "\nQuarterly Audit Activity Summary:\n";
arsort($logsByType);
foreach ($logsByType as $logName => $count) {
    echo "  {$logName}: {$count}\n";
}

echo "\nBasic usage examples complete!\n";
