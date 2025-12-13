<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Rules;

use Nexus\ProcurementOperations\DTOs\Audit\DisposalCertificationData;
use Nexus\ProcurementOperations\DTOs\Audit\LegalHoldData;
use Nexus\ProcurementOperations\DTOs\Audit\RetentionPolicyData;
use Nexus\ProcurementOperations\Enums\RetentionCategory;
use Nexus\ProcurementOperations\Rules\DisposalAuthorizationRule;
use Nexus\ProcurementOperations\Rules\DisposalAuthorizationRuleResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisposalAuthorizationRule::class)]
#[CoversClass(DisposalAuthorizationRuleResult::class)]
final class DisposalAuthorizationRuleTest extends TestCase
{
    private DisposalAuthorizationRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new DisposalAuthorizationRule();
    }

    #[Test]
    public function validateDisposalEligibility_withinRetention_returnsFail(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::SOX_FINANCIAL_DATA,
            retentionYears: 7,
            disposalMethod: 'secure_shred',
            regulatoryBasis: 'SOX Section 802',
            requiresApproval: true,
        );

        $documentDate = new \DateTimeImmutable('-3 years');

        $result = $this->rule->validateDisposalEligibility(
            documentId: 'DOC-001',
            retentionPolicy: $policy,
            documentDate: $documentDate,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('WITHIN_RETENTION_PERIOD', $result->reason);
        $this->assertNotNull($result->expirationDate);
        $this->assertGreaterThan(0, $result->remainingDays);
    }

    #[Test]
    public function validateDisposalEligibility_expiredRetention_returnsPass(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::VENDOR_CORRESPONDENCE,
            retentionYears: 3,
            disposalMethod: 'standard',
            regulatoryBasis: 'Business Practice',
            requiresApproval: false,
        );

        $documentDate = new \DateTimeImmutable('-5 years');

        $result = $this->rule->validateDisposalEligibility(
            documentId: 'DOC-002',
            retentionPolicy: $policy,
            documentDate: $documentDate,
        );

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('eligible', strtolower($result->message));
    }

    #[Test]
    public function validateDisposalEligibility_activeLegalHold_returnsFail(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::VENDOR_CONTRACTS,
            retentionYears: 7,
            disposalMethod: 'archive',
            regulatoryBasis: 'Contract Law',
            requiresApproval: true,
        );

        $documentDate = new \DateTimeImmutable('-10 years');

        $legalHold = new LegalHoldData(
            holdId: 'HOLD-001',
            tenantId: 'tenant-1',
            matterReference: 'Litigation #2024-001',
            holdStartDate: new \DateTimeImmutable('-30 days'),
            initiatedBy: 'legal-counsel',
            reason: 'Ongoing litigation',
            affectedDocumentTypes: ['VENDOR_CONTRACTS'],
            custodians: ['records-manager'],
            isActive: true,
        );

        $result = $this->rule->validateDisposalEligibility(
            documentId: 'DOC-003',
            retentionPolicy: $policy,
            documentDate: $documentDate,
            activeLegalHolds: [$legalHold],
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('LEGAL_HOLD_ACTIVE', $result->reason);
        $this->assertEquals('HOLD-001', $result->legalHoldId);
    }

    #[Test]
    public function validateDisposalEligibility_releasedLegalHold_ignoresHold(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::PURCHASE_ORDERS,
            retentionYears: 7,
            disposalMethod: 'archive',
            regulatoryBasis: 'Business Records',
            requiresApproval: true,
        );

        $documentDate = new \DateTimeImmutable('-10 years');

        $releasedHold = new LegalHoldData(
            holdId: 'HOLD-002',
            tenantId: 'tenant-1',
            matterReference: 'Old Litigation',
            holdStartDate: new \DateTimeImmutable('-2 years'),
            initiatedBy: 'legal-counsel',
            reason: 'Resolved litigation',
            affectedDocumentTypes: ['PURCHASE_ORDERS'],
            custodians: ['records-manager'],
            isActive: true,
            holdEndDate: new \DateTimeImmutable('-1 year'),
            releaseApprovedBy: 'general-counsel',
            releaseReason: 'Litigation concluded',
        );

        $result = $this->rule->validateDisposalEligibility(
            documentId: 'DOC-004',
            retentionPolicy: $policy,
            documentDate: $documentDate,
            activeLegalHolds: [$releasedHold],
        );

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function validateApprovalChain_sufficientApprovals_returnsPass(): void
    {
        $certification = new DisposalCertificationData(
            certificationId: 'CERT-001',
            documentId: 'DOC-001',
            documentType: RetentionCategory::PURCHASE_ORDERS->value,
            disposalMethod: 'archive',
            disposalDate: new \DateTimeImmutable(),
            disposedBy: 'records-manager',
            approvalChain: [
                [
                    'approver_id' => 'manager-1',
                    'level' => 3,
                    'approved_at' => new \DateTimeImmutable('-1 day'),
                ],
                [
                    'approver_id' => 'director-1',
                    'level' => 5,
                    'approved_at' => new \DateTimeImmutable('-2 hours'),
                ],
            ],
            legalHoldVerified: true,
            chainOfCustody: [
                ['action' => 'created', 'by' => 'system', 'at' => new \DateTimeImmutable('-1 year')],
            ],
        );

        $result = $this->rule->validateApprovalChain(
            certification: $certification,
            category: RetentionCategory::PURCHASE_ORDERS,
        );

        $this->assertTrue($result->passed);
        $this->assertEquals(2, $result->approvalCount);
    }

    #[Test]
    public function validateApprovalChain_insufficientApprovals_returnsFail(): void
    {
        $certification = new DisposalCertificationData(
            certificationId: 'CERT-002',
            documentId: 'DOC-002',
            documentType: RetentionCategory::VENDOR_CONTRACTS->value,
            disposalMethod: 'secure_shred',
            disposalDate: new \DateTimeImmutable(),
            disposedBy: 'records-manager',
            approvalChain: [
                [
                    'approver_id' => 'manager-1',
                    'level' => 3,
                    'approved_at' => new \DateTimeImmutable(),
                ],
            ],
            legalHoldVerified: true,
            chainOfCustody: [],
        );

        $result = $this->rule->validateApprovalChain(
            certification: $certification,
            category: RetentionCategory::VENDOR_CONTRACTS,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('INSUFFICIENT_APPROVALS', $result->reason);
    }

    #[Test]
    public function validateApprovalChain_insufficientAuthority_returnsFail(): void
    {
        $certification = new DisposalCertificationData(
            certificationId: 'CERT-003',
            documentId: 'DOC-003',
            documentType: RetentionCategory::SOX_FINANCIAL_DATA->value,
            disposalMethod: 'secure_shred',
            disposalDate: new \DateTimeImmutable(),
            disposedBy: 'records-manager',
            approvalChain: [
                [
                    'approver_id' => 'supervisor-1',
                    'level' => 2, // Too low for SOX data (needs 5)
                    'approved_at' => new \DateTimeImmutable(),
                ],
                [
                    'approver_id' => 'manager-1',
                    'level' => 3, // Still too low
                    'approved_at' => new \DateTimeImmutable(),
                ],
            ],
            legalHoldVerified: true,
            chainOfCustody: [],
        );

        $result = $this->rule->validateApprovalChain(
            certification: $certification,
            category: RetentionCategory::SOX_FINANCIAL_DATA,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('INSUFFICIENT_AUTHORITY', $result->reason);
        $this->assertEquals(5, $result->requiredLevel);
        $this->assertEquals(3, $result->actualLevel);
    }

    #[Test]
    public function validateApprovalChain_duplicateApprover_returnsFail(): void
    {
        $certification = new DisposalCertificationData(
            certificationId: 'CERT-004',
            documentId: 'DOC-004',
            documentType: RetentionCategory::PURCHASE_ORDERS->value,
            disposalMethod: 'archive',
            disposalDate: new \DateTimeImmutable(),
            disposedBy: 'records-manager',
            approvalChain: [
                [
                    'approver_id' => 'manager-1',
                    'level' => 3,
                    'approved_at' => new \DateTimeImmutable('-1 day'),
                ],
                [
                    'approver_id' => 'manager-1', // Same approver
                    'level' => 3,
                    'approved_at' => new \DateTimeImmutable(),
                ],
            ],
            legalHoldVerified: true,
            chainOfCustody: [],
        );

        $result = $this->rule->validateApprovalChain(
            certification: $certification,
            category: RetentionCategory::PURCHASE_ORDERS,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('DUPLICATE_APPROVER', $result->reason);
    }

    #[Test]
    public function validateDisposalMethod_validMethod_returnsPass(): void
    {
        $result = $this->rule->validateDisposalMethod(
            proposedMethod: 'archive',
            category: RetentionCategory::PURCHASE_ORDERS,
        );

        $this->assertTrue($result->passed);
    }

    #[Test]
    public function validateDisposalMethod_invalidMethod_returnsFail(): void
    {
        $result = $this->rule->validateDisposalMethod(
            proposedMethod: 'standard',
            category: RetentionCategory::SOX_FINANCIAL_DATA, // Requires secure methods
            containsFinancialData: true,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('INVALID_DISPOSAL_METHOD', $result->reason);
        $this->assertNotEmpty($result->acceptableMethods);
    }

    #[Test]
    public function validateDisposalMethod_sensitiveDataNotSecure_returnsWarning(): void
    {
        $result = $this->rule->validateDisposalMethod(
            proposedMethod: 'secure_deletion', // Valid but not most secure
            category: RetentionCategory::VENDOR_CONTRACTS,
            containsPII: true,
        );

        $this->assertTrue($result->passed);
        $this->assertTrue($result->isWarning);
        $this->assertEquals('secure_shred', $result->recommendedMethod);
    }

    #[Test]
    public function validateCompleteAuthorization_allValid_returnsPass(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::VENDOR_CORRESPONDENCE,
            retentionYears: 3,
            disposalMethod: 'standard',
            regulatoryBasis: 'Business Practice',
            requiresApproval: false,
        );

        $certification = new DisposalCertificationData(
            certificationId: 'CERT-005',
            documentId: 'DOC-005',
            documentType: RetentionCategory::VENDOR_CORRESPONDENCE->value,
            disposalMethod: 'standard',
            disposalDate: new \DateTimeImmutable(),
            disposedBy: 'records-manager',
            approvalChain: [
                [
                    'approver_id' => 'manager-1',
                    'level' => 3,
                    'approved_at' => new \DateTimeImmutable(),
                ],
                [
                    'approver_id' => 'manager-2',
                    'level' => 3,
                    'approved_at' => new \DateTimeImmutable(),
                ],
            ],
            legalHoldVerified: true,
            chainOfCustody: [
                ['action' => 'transferred', 'by' => 'clerk-1', 'at' => new \DateTimeImmutable('-1 day')],
            ],
        );

        $documentDate = new \DateTimeImmutable('-5 years');

        $result = $this->rule->validateCompleteAuthorization(
            documentId: 'DOC-005',
            retentionPolicy: $policy,
            certification: $certification,
            documentDate: $documentDate,
        );

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('authorization granted', strtolower($result->message));
    }

    #[Test]
    public function validateCompleteAuthorization_legalHoldNotVerified_returnsFail(): void
    {
        $policy = new RetentionPolicyData(
            category: RetentionCategory::PURCHASE_ORDERS,
            retentionYears: 7,
            disposalMethod: 'archive',
            regulatoryBasis: 'Business Records',
            requiresApproval: true,
        );

        $certification = new DisposalCertificationData(
            certificationId: 'CERT-006',
            documentId: 'DOC-006',
            documentType: RetentionCategory::PURCHASE_ORDERS->value,
            disposalMethod: 'archive',
            disposalDate: new \DateTimeImmutable(),
            disposedBy: 'records-manager',
            approvalChain: [
                ['approver_id' => 'manager-1', 'level' => 3, 'approved_at' => new \DateTimeImmutable()],
                ['approver_id' => 'director-1', 'level' => 5, 'approved_at' => new \DateTimeImmutable()],
            ],
            legalHoldVerified: false, // Not verified
            chainOfCustody: [],
        );

        $documentDate = new \DateTimeImmutable('-10 years');

        $result = $this->rule->validateCompleteAuthorization(
            documentId: 'DOC-006',
            retentionPolicy: $policy,
            certification: $certification,
            documentDate: $documentDate,
        );

        $this->assertFalse($result->passed);
        $this->assertEquals('LEGAL_HOLD_NOT_VERIFIED', $result->reason);
    }

    #[Test]
    #[DataProvider('categoryApprovalLevelProvider')]
    public function validateApprovalChain_respectsCategoryLevels(
        RetentionCategory $category,
        int $requiredLevel,
    ): void {
        $certification = new DisposalCertificationData(
            certificationId: 'CERT-007',
            documentId: 'DOC-007',
            documentType: $category->value,
            disposalMethod: $category->getDisposalMethod(),
            disposalDate: new \DateTimeImmutable(),
            disposedBy: 'records-manager',
            approvalChain: [
                ['approver_id' => 'approver-1', 'level' => $requiredLevel, 'approved_at' => new \DateTimeImmutable()],
                ['approver_id' => 'approver-2', 'level' => $requiredLevel, 'approved_at' => new \DateTimeImmutable()],
            ],
            legalHoldVerified: true,
            chainOfCustody: [],
        );

        $result = $this->rule->validateApprovalChain($certification, $category);

        $this->assertTrue($result->passed, "Category {$category->value} should pass with level {$requiredLevel}");
    }

    /**
     * @return array<string, array{RetentionCategory, int}>
     */
    public static function categoryApprovalLevelProvider(): array
    {
        return [
            'SOX_FINANCIAL_DATA' => [RetentionCategory::SOX_FINANCIAL_DATA, 5],
            'LEGAL_LITIGATION_RECORDS' => [RetentionCategory::LEGAL_LITIGATION_RECORDS, 5],
            'PURCHASE_ORDERS' => [RetentionCategory::PURCHASE_ORDERS, 3],
            'VENDOR_CORRESPONDENCE' => [RetentionCategory::VENDOR_CORRESPONDENCE, 3],
        ];
    }

    #[Test]
    public function resultToArray_containsRelevantFields(): void
    {
        $result = DisposalAuthorizationRuleResult::fail(
            message: 'Disposal not authorized',
            reason: 'WITHIN_RETENTION_PERIOD',
            documentId: 'DOC-001',
            category: 'SOX_FINANCIAL_DATA',
            remainingDays: 1460,
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('passed', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('reason', $array);
        $this->assertArrayHasKey('document_id', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('remaining_days', $array);
    }
}
