<?php

declare(strict_types=1);

/**
 * Basic Usage Examples for Nexus\Compliance Package
 * 
 * This file demonstrates simple, common use cases for the Compliance package.
 */

use Nexus\Compliance\Contracts\ComplianceManagerInterface;
use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\ValueObjects\SeverityLevel;
use Nexus\Compliance\Exceptions\SodViolationException;

// Assume these are injected via dependency injection
/** @var ComplianceManagerInterface $complianceManager */
/** @var SodManagerInterface $sodManager */

// ============================================================================
// Example 1: Activate a Compliance Scheme
// ============================================================================

function example1_activateComplianceScheme(
    ComplianceManagerInterface $complianceManager,
    string $tenantId
): void {
    echo "Example 1: Activating ISO 14001 Compliance Scheme\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // Activate ISO 14001 (Environmental Management)
    $schemeId = $complianceManager->activateScheme(
        tenantId: $tenantId,
        schemeName: 'ISO14001',
        configuration: [
            'audit_frequency' => 'quarterly',
            'enable_environmental_tracking' => true,
            'carbon_reporting' => true,
        ]
    );
    
    echo "✓ ISO 14001 scheme activated successfully\n";
    echo "Scheme ID: {$schemeId}\n\n";
    
    // Verify activation
    $isActive = $complianceManager->isSchemeActive($tenantId, 'ISO14001');
    echo "Scheme active: " . ($isActive ? 'Yes' : 'No') . "\n\n";
}

// ============================================================================
// Example 2: Create a Simple SOD Rule
// ============================================================================

function example2_createSodRule(
    SodManagerInterface $sodManager,
    string $tenantId
): void {
    echo "Example 2: Creating SOD Rule for Invoice Approval\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // Create SOD rule: Invoice creator cannot approve
    $ruleId = $sodManager->createRule(
        tenantId: $tenantId,
        ruleName: 'Invoice Creator Cannot Approve',
        transactionType: 'invoice_approval',
        severityLevel: SeverityLevel::CRITICAL,
        creatorRole: 'accountant',
        approverRole: 'manager'
    );
    
    echo "✓ SOD rule created successfully\n";
    echo "Rule ID: {$ruleId}\n";
    echo "Transaction Type: invoice_approval\n";
    echo "Severity: CRITICAL\n\n";
}

// ============================================================================
// Example 3: Validate Transaction (No Violation)
// ============================================================================

function example3_validateTransactionSuccess(
    SodManagerInterface $sodManager,
    string $tenantId
): void {
    echo "Example 3: Validating Transaction (Different Users)\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $creatorId = 'user-001'; // Accountant
    $approverId = 'user-002'; // Manager
    
    try {
        $sodManager->validateTransaction(
            tenantId: $tenantId,
            transactionType: 'invoice_approval',
            creatorId: $creatorId,
            approverId: $approverId
        );
        
        echo "✓ Transaction validation passed\n";
        echo "Creator: {$creatorId}\n";
        echo "Approver: {$approverId}\n";
        echo "Result: No SOD violation detected\n\n";
        
    } catch (SodViolationException $e) {
        echo "✗ SOD Violation: {$e->getMessage()}\n\n";
    }
}

// ============================================================================
// Example 4: Validate Transaction (Violation Detected)
// ============================================================================

function example4_validateTransactionViolation(
    SodManagerInterface $sodManager,
    string $tenantId
): void {
    echo "Example 4: Validating Transaction (Same User - Violation)\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $userId = 'user-001'; // Same user for creator and approver
    
    try {
        $sodManager->validateTransaction(
            tenantId: $tenantId,
            transactionType: 'invoice_approval',
            creatorId: $userId,
            approverId: $userId
        );
        
        echo "✓ Transaction validation passed\n\n";
        
    } catch (SodViolationException $e) {
        echo "✗ SOD Violation Detected!\n";
        echo "Error: {$e->getMessage()}\n";
        echo "Creator: {$userId}\n";
        echo "Approver: {$userId}\n";
        echo "Result: Transaction blocked due to SOD violation\n\n";
    }
}

// ============================================================================
// Example 5: Check Active Schemes
// ============================================================================

function example5_checkActiveSchemes(
    ComplianceManagerInterface $complianceManager,
    string $tenantId
): void {
    echo "Example 5: Listing Active Compliance Schemes\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $activeSchemes = $complianceManager->getActiveSchemes($tenantId);
    
    echo "Active compliance schemes for tenant {$tenantId}:\n\n";
    
    if (empty($activeSchemes)) {
        echo "No active schemes\n\n";
        return;
    }
    
    foreach ($activeSchemes as $scheme) {
        echo "- {$scheme->getSchemeName()}\n";
        echo "  Configuration: " . json_encode($scheme->getConfiguration()) . "\n";
    }
    
    echo "\nTotal: " . count($activeSchemes) . " scheme(s)\n\n";
}

// ============================================================================
// Example 6: Deactivate a Compliance Scheme
// ============================================================================

function example6_deactivateScheme(
    ComplianceManagerInterface $complianceManager,
    string $tenantId
): void {
    echo "Example 6: Deactivating Compliance Scheme\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $schemeName = 'ISO14001';
    
    // Check if active before deactivating
    $isActive = $complianceManager->isSchemeActive($tenantId, $schemeName);
    echo "Scheme '{$schemeName}' active before deactivation: " . ($isActive ? 'Yes' : 'No') . "\n";
    
    if ($isActive) {
        $complianceManager->deactivateScheme($tenantId, $schemeName);
        echo "✓ Scheme deactivated successfully\n";
    } else {
        echo "Scheme is not active\n";
    }
    
    // Verify deactivation
    $isActive = $complianceManager->isSchemeActive($tenantId, $schemeName);
    echo "Scheme active after deactivation: " . ($isActive ? 'Yes' : 'No') . "\n\n";
}

// ============================================================================
// Example 7: Get Active SOD Rules
// ============================================================================

function example7_getActiveRules(
    SodManagerInterface $sodManager,
    string $tenantId
): void {
    echo "Example 7: Listing Active SOD Rules\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $activeRules = $sodManager->getActiveRules($tenantId);
    
    echo "Active SOD rules for tenant {$tenantId}:\n\n";
    
    if (empty($activeRules)) {
        echo "No active SOD rules\n\n";
        return;
    }
    
    foreach ($activeRules as $rule) {
        echo "Rule: {$rule->getRuleName()}\n";
        echo "  Transaction Type: {$rule->getTransactionType()}\n";
        echo "  Severity: {$rule->getSeverityLevel()->value}\n";
        echo "  Creator Role: {$rule->getCreatorRole()}\n";
        echo "  Approver Role: {$rule->getApproverRole()}\n";
        echo "\n";
    }
    
    echo "Total: " . count($activeRules) . " rule(s)\n\n";
}

// ============================================================================
// Example 8: Get SOD Violations Report
// ============================================================================

function example8_getViolationsReport(
    SodManagerInterface $sodManager,
    string $tenantId
): void {
    echo "Example 8: SOD Violations Report (Last 30 Days)\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $from = new \DateTimeImmutable('-30 days');
    $to = new \DateTimeImmutable();
    
    $violations = $sodManager->getViolations($tenantId, $from, $to);
    
    echo "Period: {$from->format('Y-m-d')} to {$to->format('Y-m-d')}\n";
    echo "Tenant: {$tenantId}\n\n";
    
    if (empty($violations)) {
        echo "✓ No violations detected in this period\n\n";
        return;
    }
    
    echo "Violations detected: " . count($violations) . "\n\n";
    
    foreach ($violations as $violation) {
        echo "Violation ID: {$violation->getId()}\n";
        echo "  Transaction Type: {$violation->getTransactionType()}\n";
        echo "  Creator: {$violation->getCreatorId()}\n";
        echo "  Approver: {$violation->getApproverId()}\n";
        echo "  Violated At: {$violation->getViolatedAt()->format('Y-m-d H:i:s')}\n";
        echo "\n";
    }
}

// ============================================================================
// Run All Examples
// ============================================================================

function runAllExamples(): void
{
    // Mock implementations (in real application, these would be injected)
    // $complianceManager = app(ComplianceManagerInterface::class);
    // $sodManager = app(SodManagerInterface::class);
    
    $tenantId = 'tenant-demo-123';
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║       Nexus\\Compliance Package - Basic Usage Examples     ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    
    // Uncomment to run examples:
    // example1_activateComplianceScheme($complianceManager, $tenantId);
    // example2_createSodRule($sodManager, $tenantId);
    // example3_validateTransactionSuccess($sodManager, $tenantId);
    // example4_validateTransactionViolation($sodManager, $tenantId);
    // example5_checkActiveSchemes($complianceManager, $tenantId);
    // example6_deactivateScheme($complianceManager, $tenantId);
    // example7_getActiveRules($sodManager, $tenantId);
    // example8_getViolationsReport($sodManager, $tenantId);
    
    echo "Done! All basic examples completed.\n\n";
}

// Run examples (uncomment in actual usage)
// runAllExamples();
