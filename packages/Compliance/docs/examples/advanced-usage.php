<?php

declare(strict_types=1);

/**
 * Advanced Usage Examples for Nexus\Compliance Package
 * 
 * This file demonstrates complex, real-world scenarios for the Compliance package.
 */

use Nexus\Compliance\Contracts\ComplianceManagerInterface;
use Nexus\Compliance\Contracts\SodManagerInterface;
use Nexus\Compliance\ValueObjects\SeverityLevel;
use Nexus\Compliance\Exceptions\SodViolationException;
use Nexus\Compliance\Exceptions\SchemeAlreadyActiveException;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;

// ============================================================================
// Example 1: Multi-Scheme Activation with Configuration Audit
// ============================================================================

function example1_multiSchemeActivation(
    ComplianceManagerInterface $complianceManager,
    string $tenantId
): void {
    echo "Example 1: Multi-Scheme Activation (ISO 14001 + SOX)\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $schemes = [
        'ISO14001' => [
            'audit_frequency' => 'quarterly',
            'enable_environmental_tracking' => true,
            'carbon_reporting' => true,
            'waste_management' => true,
        ],
        'SOX' => [
            'enable_maker_checker' => true,
            'require_dual_approval_threshold' => 10000,
            'financial_audit_frequency' => 'monthly',
        ],
    ];
    
    foreach ($schemes as $schemeName => $configuration) {
        try {
            $schemeId = $complianceManager->activateScheme(
                tenantId: $tenantId,
                schemeName: $schemeName,
                configuration: $configuration
            );
            
            echo "✓ {$schemeName} activated successfully\n";
            echo "  Scheme ID: {$schemeId}\n";
            echo "  Configuration: " . json_encode($configuration) . "\n\n";
            
        } catch (SchemeAlreadyActiveException $e) {
            echo "⚠ {$schemeName} already active: {$e->getMessage()}\n\n";
        }
    }
    
    // List all active schemes
    $activeSchemes = $complianceManager->getActiveSchemes($tenantId);
    echo "Total active schemes: " . count($activeSchemes) . "\n\n";
}

// ============================================================================
// Example 2: SOD Rules for Complete Approval Workflow
// ============================================================================

function example2_approvalWorkflowWithSod(
    SodManagerInterface $sodManager,
    string $tenantId
): void {
    echo "Example 2: Complete Approval Workflow with SOD\n";
    echo str_repeat('=', 70) . "\n\n";
    
    // Define multiple SOD rules for various transaction types
    $rules = [
        [
            'name' => 'Purchase Order Approval',
            'type' => 'purchase_order_approval',
            'severity' => SeverityLevel::CRITICAL,
            'creator' => 'purchaser',
            'approver' => 'procurement_manager',
        ],
        [
            'name' => 'Invoice Approval',
            'type' => 'invoice_approval',
            'severity' => SeverityLevel::CRITICAL,
            'creator' => 'accountant',
            'approver' => 'finance_manager',
        ],
        [
            'name' => 'Payment Approval',
            'type' => 'payment_approval',
            'severity' => SeverityLevel::CRITICAL,
            'creator' => 'accounts_payable',
            'approver' => 'cfo',
        ],
        [
            'name' => 'Expense Report Approval',
            'type' => 'expense_report_approval',
            'severity' => SeverityLevel::HIGH,
            'creator' => 'employee',
            'approver' => 'department_head',
        ],
    ];
    
    foreach ($rules as $rule) {
        $ruleId = $sodManager->createRule(
            tenantId: $tenantId,
            ruleName: $rule['name'],
            transactionType: $rule['type'],
            severityLevel: $rule['severity'],
            creatorRole: $rule['creator'],
            approverRole: $rule['approver']
        );
        
        echo "✓ {$rule['name']} rule created\n";
        echo "  Rule ID: {$ruleId}\n";
        echo "  Severity: {$rule['severity']->value}\n\n";
    }
}

// ============================================================================
// Example 3: Invoice Approval with SOD Validation and Audit Logging
// ============================================================================

final readonly class InvoiceApprovalService
{
    public function __construct(
        private SodManagerInterface $sodManager,
        private AuditLogManagerInterface $auditLogger,
        private TenantContextInterface $tenantContext
    ) {}
    
    public function approveInvoice(
        string $invoiceId,
        string $approverId,
        array $invoiceData
    ): bool {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $creatorId = $invoiceData['created_by'];
        
        echo "Attempting to approve invoice {$invoiceId}...\n";
        
        try {
            // Step 1: SOD Validation
            $this->sodManager->validateTransaction(
                tenantId: $tenantId,
                transactionType: 'invoice_approval',
                creatorId: $creatorId,
                approverId: $approverId
            );
            
            echo "✓ SOD validation passed\n";
            
            // Step 2: Approve invoice (business logic)
            // $invoice->setStatus('approved');
            // $invoice->setApprovedBy($approverId);
            // $invoice->setApprovedAt(new DateTimeImmutable());
            // $invoice->save();
            
            echo "✓ Invoice approved successfully\n";
            
            // Step 3: Audit log
            $this->auditLogger->log(
                entityId: $invoiceId,
                action: 'invoice_approved',
                description: "Invoice approved by {$approverId}",
                metadata: [
                    'approver_id' => $approverId,
                    'invoice_amount' => $invoiceData['amount'],
                ]
            );
            
            echo "✓ Audit log recorded\n\n";
            
            return true;
            
        } catch (SodViolationException $e) {
            echo "✗ SOD violation detected: {$e->getMessage()}\n";
            
            // Log violation
            $this->auditLogger->log(
                entityId: $invoiceId,
                action: 'sod_violation',
                description: "SOD violation: {$e->getMessage()}",
                metadata: [
                    'creator_id' => $creatorId,
                    'approver_id' => $approverId,
                ]
            );
            
            echo "✓ Violation logged for review\n\n";
            
            return false;
        }
    }
}

function example3_invoiceApprovalWithSod(): void
{
    echo "Example 3: Invoice Approval with SOD Validation\n";
    echo str_repeat('=', 70) . "\n\n";
    
    // Mock dependencies (in real app, these would be injected)
    // $service = new InvoiceApprovalService($sodManager, $auditLogger, $tenantContext);
    
    $invoiceData = [
        'id' => 'inv-001',
        'created_by' => 'user-accountant',
        'amount' => 5000.00,
    ];
    
    echo "Scenario 1: Different users (Valid)\n";
    echo str_repeat('-', 40) . "\n";
    // $service->approveInvoice('inv-001', 'user-manager', $invoiceData);
    
    echo "\nScenario 2: Same user (SOD Violation)\n";
    echo str_repeat('-', 40) . "\n";
    // $service->approveInvoice('inv-001', 'user-accountant', $invoiceData);
}

// ============================================================================
// Example 4: Compliance Dashboard with Violation Monitoring
// ============================================================================

final readonly class ComplianceDashboardService
{
    public function __construct(
        private ComplianceManagerInterface $complianceManager,
        private SodManagerInterface $sodManager,
        private TenantContextInterface $tenantContext
    ) {}
    
    public function getDashboardData(): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        // Get active schemes
        $activeSchemes = $this->complianceManager->getActiveSchemes($tenantId);
        
        // Get active SOD rules
        $activeRules = $this->sodManager->getActiveRules($tenantId);
        
        // Get recent violations (last 30 days)
        $from = new \DateTimeImmutable('-30 days');
        $to = new \DateTimeImmutable();
        $violations = $this->sodManager->getViolations($tenantId, $from, $to);
        
        // Calculate metrics
        $criticalViolations = array_filter(
            $violations,
            fn($v) => $v->getSeverityLevel() === SeverityLevel::CRITICAL
        );
        
        return [
            'active_schemes' => array_map(
                fn($s) => $s->getSchemeName(),
                $activeSchemes
            ),
            'active_rules_count' => count($activeRules),
            'total_violations_30d' => count($violations),
            'critical_violations_30d' => count($criticalViolations),
            'compliance_score' => $this->calculateComplianceScore($violations),
        ];
    }
    
    private function calculateComplianceScore(array $violations): float
    {
        // Simple scoring: 100% - (violations * 2%)
        $deduction = count($violations) * 2;
        return max(0, 100 - $deduction);
    }
}

function example4_complianceDashboard(): void
{
    echo "Example 4: Compliance Dashboard with Violation Monitoring\n";
    echo str_repeat('=', 70) . "\n\n";
    
    // Mock service (in real app, injected via DI)
    // $dashboard = new ComplianceDashboardService(
    //     $complianceManager,
    //     $sodManager,
    //     $tenantContext
    // );
    
    // $data = $dashboard->getDashboardData();
    
    // Mock data for demo
    $data = [
        'active_schemes' => ['ISO14001', 'SOX'],
        'active_rules_count' => 12,
        'total_violations_30d' => 3,
        'critical_violations_30d' => 1,
        'compliance_score' => 94.0,
    ];
    
    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║              COMPLIANCE DASHBOARD SUMMARY                 ║\n";
    echo "╠═══════════════════════════════════════════════════════════╣\n";
    echo "║ Active Schemes: " . implode(', ', $data['active_schemes']) . str_repeat(' ', 38 - strlen(implode(', ', $data['active_schemes']))) . "║\n";
    echo "║ Active SOD Rules: {$data['active_rules_count']}" . str_repeat(' ', 43) . "║\n";
    echo "║ Violations (30 days): {$data['total_violations_30d']}" . str_repeat(' ', 39) . "║\n";
    echo "║ Critical Violations: {$data['critical_violations_30d']}" . str_repeat(' ', 40) . "║\n";
    echo "║ Compliance Score: {$data['compliance_score']}%" . str_repeat(' ', 38) . "║\n";
    echo "╚═══════════════════════════════════════════════════════════╝\n\n";
}

// ============================================================================
// Example 5: Event-Driven Violation Notification
// ============================================================================

final readonly class SodViolationNotificationService
{
    public function __construct(
        private NotificationManagerInterface $notifier,
        private AuditLogManagerInterface $auditLogger
    ) {}
    
    public function notifyViolation(
        string $tenantId,
        string $violationId,
        string $transactionType,
        string $creatorId,
        string $approverId
    ): void {
        // Send email notification
        $this->notifier->send(
            recipient: 'compliance@company.com',
            channel: 'email',
            template: 'compliance.sod_violation',
            data: [
                'tenant_id' => $tenantId,
                'violation_id' => $violationId,
                'transaction_type' => $transactionType,
                'creator_id' => $creatorId,
                'approver_id' => $approverId,
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );
        
        // Send in-app notification
        $this->notifier->send(
            recipient: 'compliance-officer',
            channel: 'in-app',
            template: 'compliance.sod_alert',
            data: [
                'title' => 'SOD Violation Detected',
                'message' => "SOD violation in {$transactionType}",
                'severity' => 'critical',
            ]
        );
        
        // Log notification
        $this->auditLogger->log(
            entityId: $violationId,
            action: 'violation_notified',
            description: 'Compliance officer notified of SOD violation'
        );
    }
}

function example5_violationNotification(): void
{
    echo "Example 5: Event-Driven Violation Notification\n";
    echo str_repeat('=', 70) . "\n\n";
    
    // In real application, this would be triggered by an event listener
    // Event: SodViolationDetected
    // Listener: NotifyComplianceOfficer
    
    echo "SOD Violation Event Flow:\n";
    echo "1. SOD violation detected during transaction validation\n";
    echo "2. SodViolationDetected event dispatched\n";
    echo "3. NotifyComplianceOfficer listener triggered\n";
    echo "4. Email sent to compliance@company.com\n";
    echo "5. In-app notification sent to compliance officer\n";
    echo "6. Violation logged in audit trail\n\n";
}

// ============================================================================
// Example 6: Multi-Tenant Compliance Isolation
// ============================================================================

function example6_multiTenantIsolation(
    ComplianceManagerInterface $complianceManager,
    SodManagerInterface $sodManager
): void {
    echo "Example 6: Multi-Tenant Compliance Isolation\n";
    echo str_repeat('=', 70) . "\n\n";
    
    // Tenant A: Manufacturing company (ISO 14001)
    $tenantA = 'tenant-manufacturing';
    $complianceManager->activateScheme($tenantA, 'ISO14001', [
        'environmental_tracking' => true,
    ]);
    $sodManager->createRule(
        $tenantA,
        'Production Approval',
        'production_order',
        SeverityLevel::HIGH,
        'operator',
        'supervisor'
    );
    
    echo "Tenant A (Manufacturing):\n";
    echo "  ✓ ISO 14001 activated\n";
    echo "  ✓ Production SOD rule created\n\n";
    
    // Tenant B: Financial services (SOX)
    $tenantB = 'tenant-financial';
    $complianceManager->activateScheme($tenantB, 'SOX', [
        'maker_checker' => true,
    ]);
    $sodManager->createRule(
        $tenantB,
        'Trade Execution',
        'trade_execution',
        SeverityLevel::CRITICAL,
        'trader',
        'compliance_officer'
    );
    
    echo "Tenant B (Financial Services):\n";
    echo "  ✓ SOX activated\n";
    echo "  ✓ Trade execution SOD rule created\n\n";
    
    // Verify isolation
    $tenantASchemes = $complianceManager->getActiveSchemes($tenantA);
    $tenantBSchemes = $complianceManager->getActiveSchemes($tenantB);
    
    echo "Isolation Verified:\n";
    echo "  Tenant A schemes: " . implode(', ', array_map(fn($s) => $s->getSchemeName(), $tenantASchemes)) . "\n";
    echo "  Tenant B schemes: " . implode(', ', array_map(fn($s) => $s->getSchemeName(), $tenantBSchemes)) . "\n\n";
}

// ============================================================================
// Example 7: Periodic Compliance Report Generation
// ============================================================================

final readonly class ComplianceReportService
{
    public function __construct(
        private SodManagerInterface $sodManager,
        private ComplianceManagerInterface $complianceManager
    ) {}
    
    public function generateMonthlyReport(string $tenantId): array
    {
        $from = (new \DateTimeImmutable())->modify('first day of this month');
        $to = (new \DateTimeImmutable())->modify('last day of this month');
        
        $violations = $this->sodManager->getViolations($tenantId, $from, $to);
        $activeSchemes = $this->complianceManager->getActiveSchemes($tenantId);
        $activeRules = $this->sodManager->getActiveRules($tenantId);
        
        // Group violations by type
        $violationsByType = [];
        foreach ($violations as $violation) {
            $type = $violation->getTransactionType();
            $violationsByType[$type] = ($violationsByType[$type] ?? 0) + 1;
        }
        
        return [
            'period' => $from->format('F Y'),
            'active_schemes' => count($activeSchemes),
            'active_rules' => count($activeRules),
            'total_violations' => count($violations),
            'violations_by_type' => $violationsByType,
            'compliance_status' => count($violations) === 0 ? 'Compliant' : 'Review Required',
        ];
    }
}

function example7_periodicReport(): void
{
    echo "Example 7: Periodic Compliance Report\n";
    echo str_repeat('=', 70) . "\n\n";
    
    // Mock report data
    $report = [
        'period' => 'November 2025',
        'active_schemes' => 2,
        'active_rules' => 12,
        'total_violations' => 3,
        'violations_by_type' => [
            'invoice_approval' => 2,
            'payment_approval' => 1,
        ],
        'compliance_status' => 'Review Required',
    ];
    
    echo "╔═══════════════════════════════════════════════════════════╗\n";
    echo "║           MONTHLY COMPLIANCE REPORT - {$report['period']}        ║\n";
    echo "╠═══════════════════════════════════════════════════════════╣\n";
    echo "║ Active Schemes: {$report['active_schemes']}" . str_repeat(' ', 44) . "║\n";
    echo "║ Active SOD Rules: {$report['active_rules']}" . str_repeat(' ', 42) . "║\n";
    echo "║ Total Violations: {$report['total_violations']}" . str_repeat(' ', 42) . "║\n";
    echo "║ Status: {$report['compliance_status']}" . str_repeat(' ', 44 - strlen($report['compliance_status'])) . "║\n";
    echo "╠═══════════════════════════════════════════════════════════╣\n";
    echo "║ Violations by Type:                                       ║\n";
    
    foreach ($report['violations_by_type'] as $type => $count) {
        $line = "║   - {$type}: {$count}";
        echo $line . str_repeat(' ', 60 - strlen($line)) . "║\n";
    }
    
    echo "╚═══════════════════════════════════════════════════════════╝\n\n";
}

// ============================================================================
// Run Advanced Examples
// ============================================================================

function runAdvancedExamples(): void
{
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║     Nexus\\Compliance Package - Advanced Usage Examples    ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    
    // Uncomment to run examples:
    // example1_multiSchemeActivation($complianceManager, 'tenant-123');
    // example2_approvalWorkflowWithSod($sodManager, 'tenant-123');
    // example3_invoiceApprovalWithSod();
    // example4_complianceDashboard();
    // example5_violationNotification();
    // example6_multiTenantIsolation($complianceManager, $sodManager);
    // example7_periodicReport();
    
    echo "All advanced examples demonstrated.\n\n";
}

// Run examples (uncomment in actual usage)
// runAdvancedExamples();
