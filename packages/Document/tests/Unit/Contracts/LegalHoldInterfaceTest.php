<?php

declare(strict_types=1);

namespace Nexus\Document\Tests\Unit\Contracts;

use Nexus\Document\Contracts\LegalHoldInterface;
use Nexus\Document\Tests\TestCase;

/**
 * Tests for LegalHoldInterface contract.
 *
 * These tests verify that the interface contract is properly defined
 * and can be mocked for unit testing purposes.
 */
final class LegalHoldInterfaceTest extends TestCase
{
    public function testInterfaceCanBeMocked(): void
    {
        $legalHold = $this->createMock(LegalHoldInterface::class);

        $legalHold->method('getId')->willReturn('hold-001');
        $legalHold->method('getTenantId')->willReturn($this->createTenantId());
        $legalHold->method('getDocumentId')->willReturn('doc-001');
        $legalHold->method('getReason')->willReturn('Pending litigation');
        $legalHold->method('isActive')->willReturn(true);

        $this->assertEquals('hold-001', $legalHold->getId());
        $this->assertTrue($legalHold->isActive());
    }

    public function testInterfaceWithMatterReference(): void
    {
        $legalHold = $this->createMock(LegalHoldInterface::class);

        $legalHold->method('getMatterReference')->willReturn('CASE-2024-001');
        $legalHold->method('getAppliedBy')->willReturn($this->createUserId('legal'));
        $legalHold->method('getAppliedAt')->willReturn($this->createDate());

        $this->assertEquals('CASE-2024-001', $legalHold->getMatterReference());
        $this->assertInstanceOf(\DateTimeImmutable::class, $legalHold->getAppliedAt());
    }

    public function testInterfaceWithReleasedHold(): void
    {
        $legalHold = $this->createMock(LegalHoldInterface::class);

        $legalHold->method('isActive')->willReturn(false);
        $legalHold->method('getReleasedBy')->willReturn($this->createUserId('legal'));
        $legalHold->method('getReleasedAt')->willReturn($this->createDate());
        $legalHold->method('getReleaseReason')->willReturn('Case closed');

        $this->assertFalse($legalHold->isActive());
        $this->assertEquals('Case closed', $legalHold->getReleaseReason());
    }

    public function testInterfaceWithExpiration(): void
    {
        $expiresAt = $this->createFutureDate(365);

        $legalHold = $this->createMock(LegalHoldInterface::class);
        $legalHold->method('getExpiresAt')->willReturn($expiresAt);

        $this->assertEquals($expiresAt, $legalHold->getExpiresAt());
    }

    public function testInterfaceWithMetadata(): void
    {
        $metadata = [
            'court_case_number' => 'CV-2024-001234',
            'attorney' => 'Jane Smith',
            'priority' => 'high',
        ];

        $legalHold = $this->createMock(LegalHoldInterface::class);
        $legalHold->method('getMetadata')->willReturn($metadata);

        $result = $legalHold->getMetadata();
        $this->assertArrayHasKey('court_case_number', $result);
        $this->assertEquals('high', $result['priority']);
    }

    public function testInterfaceToArray(): void
    {
        $legalHold = $this->createMock(LegalHoldInterface::class);

        $legalHold->method('toArray')->willReturn([
            'id' => 'hold-001',
            'tenant_id' => $this->createTenantId(),
            'document_id' => 'doc-001',
            'reason' => 'Pending litigation',
            'is_active' => true,
        ]);

        $array = $legalHold->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('is_active', $array);
    }
}
