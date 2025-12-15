<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services\Compliance;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Nexus\Document\Services\RetentionService;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\LegalHoldInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Document\Contracts\RetentionPolicyInterface;
use Nexus\ProcurementOperations\Enums\RetentionCategory;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\LegalHoldRepositoryInterface;
use Nexus\Document\Contracts\DisposalCertificationRepositoryInterface;
use Nexus\ProcurementOperations\Services\Compliance\DocumentRetentionService;

/**
 * Unit tests for DocumentRetentionService.
 */
final class DocumentRetentionServiceTest extends TestCase
{
    private RetentionService&MockObject $retentionService;
    private DocumentRepositoryInterface&MockObject $documentRepository;
    private LegalHoldRepositoryInterface&MockObject $legalHoldRepository;
    private DisposalCertificationRepositoryInterface&MockObject $disposalCertRepository;
    private RetentionPolicyInterface&MockObject $retentionPolicy;
    private TenantContextInterface&MockObject $tenantContext;
    private AuditLogManagerInterface&MockObject $auditLogger;
    private DocumentRetentionService $service;

    protected function setUp(): void
    {
        $this->retentionService = $this->createMock(RetentionService::class);
        $this->documentRepository = $this->createMock(DocumentRepositoryInterface::class);
        $this->legalHoldRepository = $this->createMock(LegalHoldRepositoryInterface::class);
        $this->disposalCertRepository = $this->createMock(DisposalCertificationRepositoryInterface::class);
        $this->retentionPolicy = $this->createMock(RetentionPolicyInterface::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManagerInterface::class);

        $this->service = new DocumentRetentionService(
            $this->retentionService,
            $this->documentRepository,
            $this->legalHoldRepository,
            $this->disposalCertRepository,
            $this->retentionPolicy,
            $this->tenantContext,
            $this->auditLogger,
        );
    }

    /**
     * Test get retention policy for document type.
     */
    public function test_get_retention_policy_for_document_type(): void
    {
        $policy = $this->service->getRetentionPolicy('purchase_order');

        $this->assertIsArray($policy);
        $this->assertArrayHasKey('years', $policy);
        $this->assertArrayHasKey('category', $policy);
        $this->assertArrayHasKey('legal_hold', $policy);
        $this->assertArrayHasKey('disposal_method', $policy);
        $this->assertArrayHasKey('regulatory_basis', $policy);
        $this->assertArrayHasKey('subject_to_sox', $policy);
        $this->assertSame('purchase_order', $policy['document_type']);
        $this->assertSame(RetentionCategory::PURCHASE_ORDERS->value, $policy['category']);
    }

    /**
     * Test retention policy varies by document type.
     */
    public function test_retention_policy_varies_by_document_type(): void
    {
        $poPolicy = $this->service->getRetentionPolicy('purchase_order');
        $contractPolicy = $this->service->getRetentionPolicy('vendor_contract');

        $this->assertSame(RetentionCategory::PURCHASE_ORDERS->value, $poPolicy['category']);
        $this->assertSame(RetentionCategory::VENDOR_CONTRACTS->value, $contractPolicy['category']);
    }

    /**
     * Test is within retention period for recent document.
     */
    public function test_is_within_retention_period_for_recent_document(): void
    {
        $createdAt = new \DateTimeImmutable('-1 year');

        $result = $this->service->isWithinRetentionPeriod('purchase_order', $createdAt);

        $this->assertTrue($result);
    }

    /**
     * Test is not within retention period for old document.
     */
    public function test_is_not_within_retention_period_for_old_document(): void
    {
        // Purchase orders have 7 year retention per RetentionCategory::PURCHASE_ORDERS
        $createdAt = new \DateTimeImmutable('-10 years');

        $result = $this->service->isWithinRetentionPeriod('purchase_order', $createdAt);

        $this->assertFalse($result);
    }

    /**
     * Test get documents for disposal.
     */
    public function test_get_documents_for_disposal(): void
    {
        $doc1 = $this->createMock(DocumentInterface::class);
        $doc1->method('getId')->willReturn('doc-1');

        $doc2 = $this->createMock(DocumentInterface::class);
        $doc2->method('getId')->willReturn('doc-2');

        $this->retentionService
            ->method('getDocumentsForDisposal')
            ->willReturn([$doc1, $doc2]);

        $result = $this->service->getDocumentsForDisposal('purchase_order');

        $this->assertIsArray($result);
    }

    /**
     * Test apply legal hold.
     */
    public function test_apply_legal_hold(): void
    {
        $documentId = 'doc-123';
        $holdReason = 'Audit investigation';
        $holdBy = 'user-legal-1';

        $document = $this->createMock(DocumentInterface::class);
        $document->method('getId')->willReturn($documentId);

        $this->documentRepository
            ->method('findById')
            ->with($documentId)
            ->willReturn($document);

        $legalHold = $this->createMock(LegalHoldInterface::class);
        $legalHold->method('getId')->willReturn('hold-1');
        $legalHold->method('getDocumentId')->willReturn($documentId);
        $legalHold->method('getReason')->willReturn($holdReason);

        $this->retentionService
            ->method('applyLegalHold')
            ->willReturn($legalHold);

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->service->applyLegalHold($documentId, $holdReason, $holdBy);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('hold_id', $result);
    }

    /**
     * Test release legal hold.
     */
    public function test_release_legal_hold(): void
    {
        $documentId = 'doc-123';
        $releaseReason = 'Investigation completed';
        $releasedBy = 'user-legal-1';

        $document = $this->createMock(DocumentInterface::class);
        $document->method('getId')->willReturn($documentId);

        $this->documentRepository
            ->method('findById')
            ->with($documentId)
            ->willReturn($document);

        $legalHold = $this->createMock(LegalHoldInterface::class);
        $legalHold->method('getId')->willReturn('hold-1');
        $legalHold->method('getDocumentId')->willReturn($documentId);
        $legalHold->method('isActive')->willReturn(true);

        $this->legalHoldRepository
            ->method('findActiveByDocumentId')
            ->with($documentId)
            ->willReturn($legalHold);

        $this->retentionService
            ->expects($this->once())
            ->method('releaseLegalHold');

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->service->releaseLegalHold($documentId, $releaseReason, $releasedBy);

        $this->assertIsArray($result);
    }

    /**
     * Test get retention category from document type mapping.
     */
    public function test_document_type_to_category_mapping(): void
    {
        // Test that different document types map to correct categories
        $this->assertSame(
            RetentionCategory::PURCHASE_ORDERS->value,
            $this->service->getRetentionPolicy('purchase_order')['category']
        );

        $this->assertSame(
            RetentionCategory::INVOICES_PAYABLE->value,
            $this->service->getRetentionPolicy('vendor_invoice')['category']
        );

        $this->assertSame(
            RetentionCategory::VENDOR_CONTRACTS->value,
            $this->service->getRetentionPolicy('vendor_contract')['category']
        );

        $this->assertSame(
            RetentionCategory::RFQ_DATA->value,
            $this->service->getRetentionPolicy('rfq')['category']
        );

        $this->assertSame(
            RetentionCategory::TAX_DOCUMENTS->value,
            $this->service->getRetentionPolicy('tax_document')['category']
        );
    }

    /**
     * Test unknown document type defaults to general.
     */
    public function test_unknown_document_type_defaults_to_general(): void
    {
        $policy = $this->service->getRetentionPolicy('unknown_type');

        $this->assertSame(RetentionCategory::GENERAL_AP->value, $policy['category']);
    }
}
