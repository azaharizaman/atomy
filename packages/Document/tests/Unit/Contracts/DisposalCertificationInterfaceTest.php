<?php

declare(strict_types=1);

namespace Nexus\Document\Tests\Unit\Contracts;

use Nexus\Document\Contracts\DisposalCertificationInterface;
use Nexus\Document\Tests\TestCase;

/**
 * Tests for DisposalCertificationInterface contract.
 *
 * These tests verify that the interface contract is properly defined
 * and supports comprehensive audit trail requirements for document disposal.
 */
final class DisposalCertificationInterfaceTest extends TestCase
{
    public function testInterfaceCanBeMocked(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('getId')->willReturn('cert-001');
        $certification->method('getTenantId')->willReturn($this->createTenantId());
        $certification->method('getDocumentId')->willReturn('doc-001');

        $this->assertEquals('cert-001', $certification->getId());
        $this->assertEquals('doc-001', $certification->getDocumentId());
    }

    public function testInterfaceWithFullDisposalDetails(): void
    {
        $disposedAt = $this->createDate();

        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('getDocumentType')->willReturn('invoice');
        $certification->method('getDocumentName')->willReturn('INV-2024-001.pdf');
        $certification->method('getDisposalMethod')->willReturn('SECURE_DELETE');
        $certification->method('getDisposedBy')->willReturn($this->createUserId('admin'));
        $certification->method('getDisposedAt')->willReturn($disposedAt);
        $certification->method('getDisposalReason')->willReturn('Retention period expired');

        $this->assertEquals('invoice', $certification->getDocumentType());
        $this->assertEquals('SECURE_DELETE', $certification->getDisposalMethod());
        $this->assertEquals('Retention period expired', $certification->getDisposalReason());
    }

    public function testInterfaceWithApprovalDualControl(): void
    {
        $approvedAt = $this->createDate();

        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('getApprovedBy')->willReturn($this->createUserId('supervisor'));
        $certification->method('getApprovedAt')->willReturn($approvedAt);

        $this->assertEquals('user-supervisor-001', $certification->getApprovedBy());
        $this->assertInstanceOf(\DateTimeImmutable::class, $certification->getApprovedAt());
    }

    public function testInterfaceWithRetentionDetails(): void
    {
        $createdAt = $this->createPastDate(365 * 8); // 8 years ago
        $expiredAt = $this->createPastDate(30);

        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('getDocumentCreatedAt')->willReturn($createdAt);
        $certification->method('getRetentionPeriodDays')->willReturn(2555); // 7 years
        $certification->method('getRetentionExpiredAt')->willReturn($expiredAt);

        $this->assertEquals(2555, $certification->getRetentionPeriodDays());
        $this->assertInstanceOf(\DateTimeImmutable::class, $certification->getDocumentCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $certification->getRetentionExpiredAt());
    }

    public function testInterfaceWithLegalHoldVerification(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('isLegalHoldVerified')->willReturn(true);

        $this->assertTrue($certification->isLegalHoldVerified());
    }

    public function testInterfaceWithChecksumAndRegulatoryBasis(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('getDocumentChecksum')->willReturn('sha256:abc123def456...');
        $certification->method('getRegulatoryBasis')->willReturn('SOX Section 802 / Companies Act 2016 (Malaysia)');

        $this->assertStringStartsWith('sha256:', $certification->getDocumentChecksum());
        $this->assertStringContainsString('SOX', $certification->getRegulatoryBasis());
    }

    public function testInterfaceWithWitness(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('getWitnessedBy')->willReturn($this->createUserId('auditor'));

        $this->assertEquals('user-auditor-001', $certification->getWitnessedBy());
    }

    public function testInterfaceWithChainOfCustody(): void
    {
        $chainOfCustody = [
            [
                'action' => 'received',
                'by' => 'user-admin-001',
                'at' => '2024-01-01T10:00:00+00:00',
                'notes' => 'Document received from vendor',
            ],
            [
                'action' => 'approved_for_disposal',
                'by' => 'user-supervisor-001',
                'at' => '2024-01-15T14:30:00+00:00',
                'notes' => 'Approved after retention review',
            ],
            [
                'action' => 'disposed',
                'by' => 'user-admin-001',
                'at' => '2024-01-15T15:00:00+00:00',
                'notes' => 'Securely deleted',
            ],
        ];

        $certification = $this->createMock(DisposalCertificationInterface::class);
        $certification->method('getChainOfCustody')->willReturn($chainOfCustody);

        $result = $certification->getChainOfCustody();
        $this->assertCount(3, $result);
        $this->assertEquals('disposed', $result[2]['action']);
    }

    public function testInterfaceToComplianceReport(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('toComplianceReport')->willReturn([
            'certification_number' => 'cert-001',
            'document_id' => 'doc-001',
            'document_name' => 'Invoice.pdf',
            'disposal_date' => '2024-01-15',
            'disposal_method' => 'SECURE_DELETE',
            'disposed_by' => 'John Doe',
            'approved_by' => 'Jane Smith',
            'regulatory_basis' => 'SOX Section 802',
            'retention_period_days' => 2555,
            'legal_hold_verified' => true,
            'checksum' => 'sha256:abc123...',
            'witness' => null,
        ]);

        $report = $certification->toComplianceReport();
        $this->assertIsArray($report);
        $this->assertArrayHasKey('certification_number', $report);
        $this->assertArrayHasKey('regulatory_basis', $report);
        $this->assertTrue($report['legal_hold_verified']);
    }

    public function testInterfaceToArray(): void
    {
        $certification = $this->createMock(DisposalCertificationInterface::class);

        $certification->method('toArray')->willReturn([
            'id' => 'cert-001',
            'tenant_id' => $this->createTenantId(),
            'document_id' => 'doc-001',
            'document_type' => 'invoice',
            'disposal_method' => 'SECURE_DELETE',
        ]);

        $array = $certification->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('disposal_method', $array);
    }

    public function testInterfaceWithMetadata(): void
    {
        $metadata = [
            'source_system' => 'ERP',
            'storage_location' => 's3://bucket/path/document.pdf',
            'tags' => ['financial', 'audited'],
        ];

        $certification = $this->createMock(DisposalCertificationInterface::class);
        $certification->method('getMetadata')->willReturn($metadata);

        $result = $certification->getMetadata();
        $this->assertArrayHasKey('source_system', $result);
        $this->assertContains('financial', $result['tags']);
    }
}
