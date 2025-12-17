<?php

declare(strict_types=1);

namespace Nexus\Document\Tests\Unit\Services;

use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\Document\Contracts\DisposalCertificationInterface;
use Nexus\Document\Contracts\DisposalCertificationRepositoryInterface;
use Nexus\Document\Contracts\DocumentInterface;
use Nexus\Document\Contracts\DocumentRepositoryInterface;
use Nexus\Document\Contracts\LegalHoldInterface;
use Nexus\Document\Contracts\LegalHoldRepositoryInterface;
use Nexus\Document\Contracts\RetentionPolicyInterface;
use Nexus\Document\Exceptions\DocumentNotFoundException;
use Nexus\Document\Exceptions\RetentionPolicyViolationException;
use Nexus\Document\Services\RetentionService;
use Nexus\Document\Tests\TestCase;
use Nexus\Document\ValueObjects\DocumentState;
use Nexus\Document\ValueObjects\DocumentType;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class RetentionServiceTest extends TestCase
{
    private DocumentRepositoryInterface&MockObject $repository;
    private RetentionPolicyInterface&MockObject $retentionPolicy;
    private LegalHoldRepositoryInterface&MockObject $legalHoldRepository;
    private DisposalCertificationRepositoryInterface&MockObject $disposalCertificationRepository;
    private StorageDriverInterface&MockObject $storage;
    private TenantContextInterface&MockObject $tenantContext;
    private AuditLogManager&MockObject $auditLogger;
    private LoggerInterface&MockObject $logger;
    private RetentionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(DocumentRepositoryInterface::class);
        $this->retentionPolicy = $this->createMock(RetentionPolicyInterface::class);
        $this->legalHoldRepository = $this->createMock(LegalHoldRepositoryInterface::class);
        $this->disposalCertificationRepository = $this->createMock(DisposalCertificationRepositoryInterface::class);
        $this->storage = $this->createMock(StorageDriverInterface::class);
        $this->tenantContext = $this->createMock(TenantContextInterface::class);
        $this->auditLogger = $this->createMock(AuditLogManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new RetentionService(
            $this->repository,
            $this->retentionPolicy,
            $this->legalHoldRepository,
            $this->disposalCertificationRepository,
            $this->storage,
            $this->tenantContext,
            $this->auditLogger,
            $this->logger
        );
    }

    // ===========================================
    // disposeDocument Tests
    // ===========================================

    public function testDisposeDocumentWithFullCertification(): void
    {
        $documentId = $this->createTestId();
        $tenantId = $this->createTenantId();
        $disposedBy = $this->createUserId();

        $document = $this->createMockDocument($documentId, DocumentType::INVOICE);
        $certification = $this->createMock(DisposalCertificationInterface::class);
        $certification->method('getId')->willReturn('cert-001');

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('hasActiveHold')
            ->with($documentId)
            ->willReturn(false);

        $this->retentionPolicy
            ->method('getRetentionDays')
            ->with(DocumentType::INVOICE->value)
            ->willReturn(2555); // 7 years

        $this->retentionPolicy
            ->method('getExpirationDate')
            ->willReturn(new \DateTimeImmutable('-1 day'));

        $this->retentionPolicy
            ->method('getDisposalMethod')
            ->with(DocumentType::INVOICE->value)
            ->willReturn('SECURE_DELETE');

        $this->retentionPolicy
            ->method('getRegulatoryBasis')
            ->with(DocumentType::INVOICE->value)
            ->willReturn('SOX Section 802');

        $this->storage
            ->expects($this->once())
            ->method('delete')
            ->with('/path/to/storage/document.pdf');

        $this->repository
            ->expects($this->once())
            ->method('forceDelete')
            ->with($documentId);

        $this->tenantContext
            ->method('requireTenant')
            ->willReturn($tenantId);

        $this->disposalCertificationRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn($certification);

        $this->disposalCertificationRepository
            ->expects($this->once())
            ->method('save')
            ->with($certification);

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->service->disposeDocument(
            document: $document,
            disposalMethod: 'SECURE_DELETE',
            disposedBy: $disposedBy,
            approvedBy: null,
            reason: 'Retention period expired'
        );

        $this->assertSame($certification, $result);
    }

    public function testDisposeDocumentWithLegalHoldThrowsException(): void
    {
        $documentId = $this->createTestId();
        $document = $this->createMockDocument($documentId, DocumentType::CONTRACT);

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('hasActiveHold')
            ->with($documentId)
            ->willReturn(true);

        $this->expectException(RetentionPolicyViolationException::class);
        $this->expectExceptionMessage('Document is under legal hold');

        $this->service->disposeDocument(
            document: $document,
            disposalMethod: 'SECURE_DELETE',
            disposedBy: $this->createUserId()
        );
    }

    // ===========================================
    // applyLegalHold Tests
    // ===========================================

    public function testApplyLegalHoldCreatesHold(): void
    {
        $documentId = $this->createTestId();
        $tenantId = $this->createTenantId();
        $appliedBy = $this->createUserId('legal');
        $reason = 'Pending litigation - Case #12345';
        $matterReference = 'CASE-12345';

        $document = $this->createMockDocument($documentId, DocumentType::CONTRACT);
        $legalHold = $this->createMock(LegalHoldInterface::class);
        $legalHold->method('getId')->willReturn('hold-001');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($documentId)
            ->willReturn($document);

        $this->tenantContext
            ->method('requireTenant')
            ->willReturn($tenantId);

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn($legalHold);

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('save')
            ->with($legalHold);

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->service->applyLegalHold(
            documentId: $documentId,
            reason: $reason,
            appliedBy: $appliedBy,
            matterReference: $matterReference
        );

        $this->assertSame($legalHold, $result);
    }

    public function testApplyLegalHoldWithExpirationDate(): void
    {
        $documentId = $this->createTestId();
        $expiresAt = $this->createFutureDate(365);

        $document = $this->createMockDocument($documentId, DocumentType::CONTRACT);
        $legalHold = $this->createMock(LegalHoldInterface::class);

        $this->repository
            ->method('findById')
            ->willReturn($document);

        $this->tenantContext
            ->method('requireTenant')
            ->willReturn($this->createTenantId());

        $this->legalHoldRepository
            ->method('create')
            ->willReturn($legalHold);

        $result = $this->service->applyLegalHold(
            documentId: $documentId,
            reason: 'Regulatory hold',
            appliedBy: $this->createUserId(),
            expiresAt: $expiresAt
        );

        $this->assertSame($legalHold, $result);
    }

    public function testApplyLegalHoldToNonExistentDocumentThrowsException(): void
    {
        $documentId = $this->createTestId('999');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($documentId)
            ->willReturn(null);

        $this->expectException(DocumentNotFoundException::class);

        $this->service->applyLegalHold(
            documentId: $documentId,
            reason: 'Test hold',
            appliedBy: $this->createUserId()
        );
    }

    // ===========================================
    // releaseLegalHold Tests
    // ===========================================

    public function testReleaseLegalHoldUpdatesHold(): void
    {
        $holdId = 'hold-001';
        $documentId = $this->createTestId();
        $releasedBy = $this->createUserId('legal');
        $releaseReason = 'Case settled';

        $existingHold = $this->createMock(LegalHoldInterface::class);
        $existingHold->method('getId')->willReturn($holdId);
        $existingHold->method('getDocumentId')->willReturn($documentId);
        $existingHold->method('getTenantId')->willReturn($this->createTenantId());
        $existingHold->method('getReason')->willReturn('Original reason');
        $existingHold->method('getMatterReference')->willReturn('CASE-001');
        $existingHold->method('getAppliedBy')->willReturn($this->createUserId());
        $existingHold->method('getAppliedAt')->willReturn($this->createPastDate(30));
        $existingHold->method('getExpiresAt')->willReturn(null);
        $existingHold->method('getMetadata')->willReturn([]);

        $updatedHold = $this->createMock(LegalHoldInterface::class);
        $updatedHold->method('getId')->willReturn($holdId);

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('findById')
            ->with($holdId)
            ->willReturn($existingHold);

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn($updatedHold);

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('save')
            ->with($updatedHold);

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->service->releaseLegalHold(
            holdId: $holdId,
            releasedBy: $releasedBy,
            releaseReason: $releaseReason
        );

        $this->assertSame($updatedHold, $result);
    }

    public function testReleaseLegalHoldNotFoundThrowsException(): void
    {
        $holdId = 'hold-nonexistent';

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('findById')
            ->with($holdId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Legal hold not found: {$holdId}");

        $this->service->releaseLegalHold(
            holdId: $holdId,
            releasedBy: $this->createUserId()
        );
    }

    // ===========================================
    // checkRetentionCompliance Tests
    // ===========================================

    public function testCheckRetentionCompliancePassesWhenExpiredNoHold(): void
    {
        $documentId = $this->createTestId();
        $document = $this->createMockDocument($documentId, DocumentType::INVOICE);

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with($documentId)
            ->willReturn($document);

        $this->legalHoldRepository
            ->expects($this->once())
            ->method('hasActiveHold')
            ->with($documentId)
            ->willReturn(false);

        $this->retentionPolicy
            ->expects($this->once())
            ->method('isExpired')
            ->willReturn(true);

        $result = $this->service->checkRetentionCompliance($documentId);

        $this->assertTrue($result);
    }

    public function testCheckRetentionComplianceFailsWithLegalHold(): void
    {
        $documentId = $this->createTestId();
        $document = $this->createMockDocument($documentId, DocumentType::CONTRACT);

        $this->repository
            ->method('findById')
            ->willReturn($document);

        $this->legalHoldRepository
            ->method('hasActiveHold')
            ->willReturn(true);

        $this->expectException(RetentionPolicyViolationException::class);
        $this->expectExceptionMessage('legal hold');

        $this->service->checkRetentionCompliance($documentId);
    }

    public function testCheckRetentionComplianceFailsWhenNotExpired(): void
    {
        $documentId = $this->createTestId();
        $document = $this->createMockDocument($documentId, DocumentType::INVOICE);

        $this->repository
            ->method('findById')
            ->willReturn($document);

        $this->legalHoldRepository
            ->method('hasActiveHold')
            ->willReturn(false);

        $this->retentionPolicy
            ->method('isExpired')
            ->willReturn(false);

        $this->retentionPolicy
            ->method('getRetentionDays')
            ->willReturn(2555);

        $this->expectException(RetentionPolicyViolationException::class);
        $this->expectExceptionMessage('retained for 2555 days');

        $this->service->checkRetentionCompliance($documentId);
    }

    public function testCheckRetentionComplianceDocumentNotFound(): void
    {
        $documentId = $this->createTestId('999');

        $this->repository
            ->method('findById')
            ->willReturn(null);

        $this->expectException(DocumentNotFoundException::class);

        $this->service->checkRetentionCompliance($documentId);
    }

    // ===========================================
    // getDocumentsForDisposal Tests
    // ===========================================

    public function testGetDocumentsForDisposalReturnsEligible(): void
    {
        $document1 = $this->createMockDocument('doc-001', DocumentType::INVOICE);
        $document2 = $this->createMockDocument('doc-002', DocumentType::INVOICE);

        $this->retentionPolicy
            ->method('getRetentionDays')
            ->willReturn(2555);

        $this->repository
            ->method('findEligibleForDisposal')
            ->willReturn([$document1, $document2]);

        $this->legalHoldRepository
            ->method('hasActiveHold')
            ->willReturnCallback(fn($id) => $id === 'doc-002');

        $result = $this->service->getDocumentsForDisposal(DocumentType::INVOICE->value);

        $this->assertCount(1, $result);
        $this->assertSame($document1, $result[0]);
    }

    // ===========================================
    // generateRetentionReport Tests
    // ===========================================

    public function testGenerateRetentionReportReturnsStats(): void
    {
        $periodStart = $this->createPastDate(30);
        $periodEnd = $this->createDate();

        $this->disposalCertificationRepository
            ->method('getStatistics')
            ->with($periodStart, $periodEnd)
            ->willReturn([
                'total_disposed' => 100,
                'by_method' => ['SECURE_DELETE' => 80, 'ARCHIVE' => 20],
                'by_type' => ['invoice' => 60, 'contract' => 40],
                'compliant_count' => 95,
            ]);

        $this->legalHoldRepository
            ->method('countActive')
            ->willReturn(5);

        $this->retentionPolicy
            ->method('getRetentionDays')
            ->willReturn(2555);

        $this->repository
            ->method('findEligibleForDisposal')
            ->willReturn([]);

        $result = $this->service->generateRetentionReport($periodStart, $periodEnd);

        $this->assertEquals(100, $result['disposals']['count']);
        $this->assertEquals(80, $result['disposals']['by_method']['SECURE_DELETE']);
        $this->assertEquals(5, $result['legal_holds']['active']);
        $this->assertEquals(95.0, $result['compliance_rate']);
    }

    // ===========================================
    // Helper Methods
    // ===========================================

    private function createMockDocument(string $id, DocumentType $type): DocumentInterface&MockObject
    {
        $document = $this->createMock(DocumentInterface::class);
        $document->method('getId')->willReturn($id);
        $document->method('getType')->willReturn($type);
        $document->method('getOriginalFilename')->willReturn('test-document.pdf');
        $document->method('getStoragePath')->willReturn('/path/to/storage/document.pdf');
        $document->method('getFileSize')->willReturn(1024);
        $document->method('getCreatedAt')->willReturn($this->createPastDate(365 * 8)); // 8 years ago
        $document->method('getChecksum')->willReturn('abc123def456');
        $document->method('getMetadata')->willReturn(['source' => 'test']);
        $document->method('getState')->willReturn(DocumentState::ACTIVE);

        return $document;
    }
}
