<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\DTOs\Vendor;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorOnboardingResult;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorValidationError;
use Nexus\ProcurementOperations\Enums\VendorPortalTier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VendorOnboardingResult::class)]
final class VendorOnboardingResultTest extends TestCase
{
    #[Test]
    public function it_creates_success_result(): void
    {
        $result = VendorOnboardingResult::success(
            vendorId: 'VND-MY-ABC123',
            assignedTier: VendorPortalTier::PREMIUM,
            workflowId: 'wf-123',
        );

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isRejected());
        $this->assertFalse($result->isPending());
        $this->assertSame('VND-MY-ABC123', $result->vendorId);
        $this->assertSame(VendorPortalTier::PREMIUM, $result->assignedTier);
        $this->assertSame('approved', $result->status);
    }

    #[Test]
    public function it_creates_pending_approval_result(): void
    {
        $result = VendorOnboardingResult::pendingApproval(
            workflowId: 'wf-123',
            assignedApprover: 'approver-456',
        );

        $this->assertTrue($result->isPending());
        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isRejected());
        $this->assertSame('pending_approval', $result->status);
        $this->assertSame('approver-456', $result->assignedApprover);
    }

    #[Test]
    public function it_creates_pending_compliance_result(): void
    {
        $pendingItems = ['tax_certificate', 'bank_letter'];

        $result = VendorOnboardingResult::pendingCompliance(
            workflowId: 'wf-123',
            pendingItems: $pendingItems,
        );

        $this->assertTrue($result->isPending());
        $this->assertSame('pending_compliance', $result->status);
        $this->assertSame($pendingItems, $result->pendingItems);
    }

    #[Test]
    public function it_creates_rejected_result(): void
    {
        $result = VendorOnboardingResult::rejected(
            workflowId: 'wf-123',
            rejectionReason: 'Sanctions match found',
            rejectedBy: 'SYSTEM',
        );

        $this->assertTrue($result->isRejected());
        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isPending());
        $this->assertSame('rejected', $result->status);
        $this->assertSame('Sanctions match found', $result->rejectionReason);
        $this->assertSame('SYSTEM', $result->rejectedBy);
    }

    #[Test]
    public function it_creates_validation_failed_result(): void
    {
        $errors = [
            VendorValidationError::required('vendor_name', 'Vendor name is required'),
            VendorValidationError::invalidFormat('email', 'Invalid email format'),
        ];

        $result = VendorOnboardingResult::validationFailed(
            workflowId: 'wf-123',
            errors: $errors,
        );

        $this->assertTrue($result->isRejected());
        $this->assertSame('validation_failed', $result->status);
        $this->assertCount(2, $result->validationErrors);
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $result = VendorOnboardingResult::success(
            vendorId: 'VND-MY-ABC123',
            assignedTier: VendorPortalTier::BASIC,
            workflowId: 'wf-123',
        );

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertSame('approved', $array['status']);
        $this->assertSame('VND-MY-ABC123', $array['vendor_id']);
        $this->assertSame('basic', $array['assigned_tier']);
        $this->assertTrue($array['is_success']);
    }

    #[Test]
    public function it_includes_validation_errors_in_array(): void
    {
        $errors = [
            VendorValidationError::required('tax_id', 'Tax ID is required'),
        ];

        $result = VendorOnboardingResult::validationFailed(
            workflowId: 'wf-123',
            errors: $errors,
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('validation_errors', $array);
        $this->assertCount(1, $array['validation_errors']);
    }
}
