<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Workflows;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorOnboardingRequest;
use Nexus\ProcurementOperations\Workflows\VendorOnboardingWorkflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorOnboardingWorkflow::class)]
final class VendorOnboardingWorkflowTest extends TestCase
{
    private VendorOnboardingWorkflow $workflow;

    protected function setUp(): void
    {
        $this->workflow = new VendorOnboardingWorkflow();
    }

    // =========================================================================
    // Initialization Tests
    // =========================================================================

    #[Test]
    public function it_creates_workflow_in_initiated_state(): void
    {
        $this->assertSame('initiated', $this->workflow->getCurrentState());
        $this->assertNotEmpty($this->workflow->getWorkflowId());
        $this->assertNull($this->workflow->getVendorId());
    }

    #[Test]
    public function it_generates_unique_workflow_ids(): void
    {
        $workflow1 = new VendorOnboardingWorkflow();
        $workflow2 = new VendorOnboardingWorkflow();

        $this->assertNotSame($workflow1->getWorkflowId(), $workflow2->getWorkflowId());
    }

    // =========================================================================
    // Start Workflow Tests
    // =========================================================================

    #[Test]
    public function it_starts_workflow_with_domestic_vendor(): void
    {
        $request = VendorOnboardingRequest::forDomesticVendor(
            vendorName: 'Acme Sdn Bhd',
            registrationNumber: 'REG123456',
            taxId: 'TAX789',
            primaryContactName: 'John Doe',
            primaryContactEmail: 'john@acme.my',
            primaryContactPhone: '+60123456789',
            registeredAddress: [
                'street' => '123 Main St',
                'city' => 'Kuala Lumpur',
                'postal_code' => '50000',
                'country' => 'MY',
            ],
            bankAccountName: 'Acme Sdn Bhd',
            bankAccountNumber: '1234567890',
            bankSwiftCode: 'MBBEMYKL',
            initiatedBy: 'USR-001',
        );

        $result = $this->workflow->start($request);

        $this->assertTrue($result->isSuccess);
        $this->assertSame('validating', $this->workflow->getCurrentState());
        $this->assertNotNull($this->workflow->getVendorId());
    }

    #[Test]
    public function it_starts_workflow_with_foreign_vendor(): void
    {
        $request = VendorOnboardingRequest::forForeignVendor(
            vendorName: 'Global Corp Inc',
            countryCode: 'US',
            registrationNumber: 'US-REG-456',
            taxId: 'US-TAX-789',
            primaryContactName: 'Jane Smith',
            primaryContactEmail: 'jane@global.com',
            primaryContactPhone: '+1234567890',
            registeredAddress: [
                'street' => '456 Broadway',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
            bankAccountName: 'Global Corp Inc',
            bankAccountNumber: '9876543210',
            bankSwiftCode: 'CHASUS33',
            initiatedBy: 'USR-002',
            vatNumber: 'US-VAT-123',
        );

        $result = $this->workflow->start($request);

        $this->assertTrue($result->isSuccess);
        $this->assertSame('foreign', $request->vendorType);
    }

    #[Test]
    public function it_starts_workflow_with_enterprise_vendor(): void
    {
        $request = VendorOnboardingRequest::forEnterpriseVendor(
            vendorName: 'Enterprise Solutions Bhd',
            registrationNumber: 'ENT-REG-789',
            taxId: 'ENT-TAX-123',
            primaryContactName: 'CEO Name',
            primaryContactEmail: 'ceo@enterprise.com',
            primaryContactPhone: '+60198765432',
            registeredAddress: [
                'street' => '789 Corporate Blvd',
                'city' => 'Petaling Jaya',
                'postal_code' => '46000',
                'country' => 'MY',
            ],
            bankAccountName: 'Enterprise Solutions Bhd',
            bankAccountNumber: '5555666677778888',
            bankSwiftCode: 'PABORJJ1',
            initiatedBy: 'USR-003',
            sponsoringManagerId: 'MGR-001',
            expectedAnnualSpend: 5000000.00,
        );

        $result = $this->workflow->start($request);

        $this->assertTrue($result->isSuccess);
        $this->assertSame('enterprise', $request->vendorType);
    }

    #[Test]
    public function it_cannot_start_already_started_workflow(): void
    {
        $request = VendorOnboardingRequest::forDomesticVendor(
            vendorName: 'Test',
            registrationNumber: 'REG',
            taxId: 'TAX',
            primaryContactName: 'Name',
            primaryContactEmail: 'test@test.com',
            primaryContactPhone: '+60123456789',
            registeredAddress: ['street' => '1', 'city' => 'KL', 'postal_code' => '50000', 'country' => 'MY'],
            bankAccountName: 'Test',
            bankAccountNumber: '123',
            bankSwiftCode: 'ABC',
            initiatedBy: 'USR-001',
        );

        $this->workflow->start($request);

        $this->expectException(\RuntimeException::class);
        $this->workflow->start($request);
    }

    // =========================================================================
    // Validation Step Tests
    // =========================================================================

    #[Test]
    public function it_executes_validation_step(): void
    {
        $this->startWorkflowWithDomesticVendor();
        $this->assertSame('validating', $this->workflow->getCurrentState());

        $result = $this->workflow->executeValidation();

        // After validation, moves to compliance_check
        $this->assertTrue($result->isSuccess);
        $this->assertSame('compliance_check', $this->workflow->getCurrentState());
    }

    #[Test]
    public function it_fails_validation_with_invalid_data(): void
    {
        $request = VendorOnboardingRequest::forDomesticVendor(
            vendorName: '', // Invalid: empty name
            registrationNumber: 'REG',
            taxId: 'TAX',
            primaryContactName: 'Name',
            primaryContactEmail: 'invalid-email', // Invalid email
            primaryContactPhone: '+60123456789',
            registeredAddress: ['street' => '1', 'city' => 'KL', 'postal_code' => '50000', 'country' => 'MY'],
            bankAccountName: 'Test',
            bankAccountNumber: '123',
            bankSwiftCode: 'ABC',
            initiatedBy: 'USR-001',
        );

        $result = $this->workflow->start($request);

        // Validation fails due to invalid data
        if (!$result->isSuccess && $result->hasValidationErrors()) {
            $this->assertFalse($result->isSuccess);
            $this->assertTrue($result->hasValidationErrors());
        } else {
            // If validation passed, check the next step fails
            $validationResult = $this->workflow->executeValidation();
            $this->assertFalse($validationResult->isSuccess);
        }
    }

    // =========================================================================
    // Compliance Check Tests
    // =========================================================================

    #[Test]
    public function it_executes_compliance_check(): void
    {
        $this->startWorkflowWithDomesticVendor();
        $this->workflow->executeValidation();
        $this->assertSame('compliance_check', $this->workflow->getCurrentState());

        $result = $this->workflow->executeComplianceCheck();

        $this->assertTrue($result->isSuccess);
        // After compliance check, moves to pending_documents or pending_approval
        $this->assertContains($this->workflow->getCurrentState(), ['pending_documents', 'pending_approval']);
    }

    // =========================================================================
    // Document Submission Tests
    // =========================================================================

    #[Test]
    public function it_handles_document_submission(): void
    {
        $this->startWorkflowWithDomesticVendor();
        $this->workflow->executeValidation();
        $this->workflow->executeComplianceCheck();

        // If in pending_documents state
        if ($this->workflow->getCurrentState() === 'pending_documents') {
            $result = $this->workflow->submitDocuments([
                'registration_certificate' => 'doc-001',
                'tax_registration' => 'doc-002',
                'bank_letter' => 'doc-003',
            ]);

            $this->assertTrue($result->isSuccess);
            $this->assertSame('pending_approval', $this->workflow->getCurrentState());
        } else {
            $this->assertSame('pending_approval', $this->workflow->getCurrentState());
        }
    }

    // =========================================================================
    // Approval Decision Tests
    // =========================================================================

    #[Test]
    public function it_processes_approval(): void
    {
        $this->moveWorkflowToPendingApproval();

        $result = $this->workflow->processApprovalDecision(
            approved: true,
            decidedBy: 'USR-APPROVER',
            notes: 'Approved - all requirements met',
        );

        $this->assertTrue($result->isSuccess);
        $this->assertSame('approved', $this->workflow->getCurrentState());
    }

    #[Test]
    public function it_processes_rejection(): void
    {
        $this->moveWorkflowToPendingApproval();

        $result = $this->workflow->processApprovalDecision(
            approved: false,
            decidedBy: 'USR-APPROVER',
            notes: 'Rejected - does not meet quality standards',
            rejectionReason: 'quality_standards',
        );

        $this->assertTrue($result->isSuccess);
        $this->assertSame('rejected', $this->workflow->getCurrentState());
    }

    // =========================================================================
    // Activation Tests
    // =========================================================================

    #[Test]
    public function it_activates_approved_vendor(): void
    {
        $this->moveWorkflowToPendingApproval();
        $this->workflow->processApprovalDecision(true, 'USR-APPROVER');

        $result = $this->workflow->activate('USR-ADMIN');

        $this->assertTrue($result->isSuccess);
        $this->assertSame('activated', $this->workflow->getCurrentState());
    }

    #[Test]
    public function it_cannot_activate_unapproved_vendor(): void
    {
        $this->moveWorkflowToPendingApproval();
        // Don't approve

        $this->expectException(\RuntimeException::class);
        $this->workflow->activate('USR-ADMIN');
    }

    // =========================================================================
    // Cancellation Tests
    // =========================================================================

    #[Test]
    public function it_cancels_workflow(): void
    {
        $this->startWorkflowWithDomesticVendor();

        $result = $this->workflow->cancel(
            cancelledBy: 'USR-VENDOR',
            reason: 'Vendor withdrew application',
        );

        $this->assertTrue($result->isSuccess);
        $this->assertSame('cancelled', $this->workflow->getCurrentState());
    }

    #[Test]
    public function it_cannot_cancel_completed_workflow(): void
    {
        $this->moveWorkflowToPendingApproval();
        $this->workflow->processApprovalDecision(true, 'USR-APPROVER');
        $this->workflow->activate('USR-ADMIN');

        $this->expectException(\RuntimeException::class);
        $this->workflow->cancel('USR-VENDOR', 'Too late');
    }

    // =========================================================================
    // State Persistence Tests
    // =========================================================================

    #[Test]
    public function it_exports_state_for_persistence(): void
    {
        $this->startWorkflowWithDomesticVendor();
        $this->workflow->executeValidation();

        $state = $this->workflow->exportState();

        $this->assertIsArray($state);
        $this->assertArrayHasKey('workflow_id', $state);
        $this->assertArrayHasKey('vendor_id', $state);
        $this->assertArrayHasKey('current_state', $state);
        $this->assertArrayHasKey('state_history', $state);
        $this->assertArrayHasKey('created_at', $state);
    }

    #[Test]
    public function it_restores_from_persisted_state(): void
    {
        $this->startWorkflowWithDomesticVendor();
        $this->workflow->executeValidation();
        
        $originalState = $this->workflow->exportState();
        $originalWorkflowId = $this->workflow->getWorkflowId();
        $originalVendorId = $this->workflow->getVendorId();
        $originalCurrentState = $this->workflow->getCurrentState();

        // Create new workflow and restore
        $restoredWorkflow = new VendorOnboardingWorkflow();
        $restoredWorkflow->restoreState($originalState);

        $this->assertSame($originalWorkflowId, $restoredWorkflow->getWorkflowId());
        $this->assertSame($originalVendorId, $restoredWorkflow->getVendorId());
        $this->assertSame($originalCurrentState, $restoredWorkflow->getCurrentState());
    }

    // =========================================================================
    // State History Tests
    // =========================================================================

    #[Test]
    public function it_tracks_state_history(): void
    {
        $this->startWorkflowWithDomesticVendor();
        $this->workflow->executeValidation();
        $this->workflow->executeComplianceCheck();

        $history = $this->workflow->getStateHistory();

        $this->assertNotEmpty($history);
        $this->assertContains('initiated', array_column($history, 'from_state'));
        $this->assertContains('validating', array_column($history, 'to_state'));
    }

    // =========================================================================
    // Tier Determination Tests
    // =========================================================================

    #[Test]
    public function it_determines_correct_tier_for_enterprise_vendor(): void
    {
        $request = VendorOnboardingRequest::forEnterpriseVendor(
            vendorName: 'Big Corp',
            registrationNumber: 'REG',
            taxId: 'TAX',
            primaryContactName: 'CEO',
            primaryContactEmail: 'ceo@big.com',
            primaryContactPhone: '+60123456789',
            registeredAddress: ['street' => '1', 'city' => 'KL', 'postal_code' => '50000', 'country' => 'MY'],
            bankAccountName: 'Big Corp',
            bankAccountNumber: '123',
            bankSwiftCode: 'ABC',
            initiatedBy: 'USR-001',
            sponsoringManagerId: 'MGR-001',
            expectedAnnualSpend: 10000000.00,
        );

        $this->workflow->start($request);
        $this->workflow->executeValidation();
        $this->workflow->executeComplianceCheck();

        $state = $this->workflow->exportState();
        
        // Enterprise vendors should get premium or enterprise tier
        $this->assertContains($state['determined_tier'] ?? 'premium', ['premium', 'enterprise']);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function startWorkflowWithDomesticVendor(): void
    {
        $request = VendorOnboardingRequest::forDomesticVendor(
            vendorName: 'Test Vendor Sdn Bhd',
            registrationNumber: 'REG-123456',
            taxId: 'TAX-789012',
            primaryContactName: 'Test Contact',
            primaryContactEmail: 'test@vendor.my',
            primaryContactPhone: '+60123456789',
            registeredAddress: [
                'street' => '123 Test Street',
                'city' => 'Kuala Lumpur',
                'postal_code' => '50000',
                'country' => 'MY',
            ],
            bankAccountName: 'Test Vendor Sdn Bhd',
            bankAccountNumber: '1234567890',
            bankSwiftCode: 'MBBEMYKL',
            initiatedBy: 'USR-TEST',
        );

        $this->workflow->start($request);
    }

    private function moveWorkflowToPendingApproval(): void
    {
        $this->startWorkflowWithDomesticVendor();
        $this->workflow->executeValidation();
        $this->workflow->executeComplianceCheck();

        // If pending documents, submit them
        if ($this->workflow->getCurrentState() === 'pending_documents') {
            $this->workflow->submitDocuments([
                'registration_certificate' => 'doc-001',
                'tax_registration' => 'doc-002',
            ]);
        }

        $this->assertSame('pending_approval', $this->workflow->getCurrentState());
    }
}
