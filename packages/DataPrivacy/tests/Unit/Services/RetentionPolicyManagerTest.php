<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\RetentionPolicyPersistInterface;
use Nexus\DataPrivacy\Contracts\RetentionPolicyQueryInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Enums\RetentionCategory;
use Nexus\DataPrivacy\Exceptions\InvalidRetentionPolicyException;
use Nexus\DataPrivacy\Exceptions\RetentionPolicyNotFoundException;
use Nexus\DataPrivacy\Services\RetentionPolicyManager;
use Nexus\DataPrivacy\ValueObjects\RetentionPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RetentionPolicyManager::class)]
final class RetentionPolicyManagerTest extends TestCase
{
    private RetentionPolicyQueryInterface&MockObject $policyQuery;
    private RetentionPolicyPersistInterface&MockObject $policyPersist;
    private AuditLoggerInterface&MockObject $auditLogger;
    private RetentionPolicyManager $manager;

    protected function setUp(): void
    {
        $this->policyQuery = $this->createMock(RetentionPolicyQueryInterface::class);
        $this->policyPersist = $this->createMock(RetentionPolicyPersistInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->manager = new RetentionPolicyManager(
            $this->policyQuery,
            $this->policyPersist,
            $this->auditLogger
        );
    }

    public function testCreatePolicySucceeds(): void
    {
        $this->policyPersist
            ->expects($this->once())
            ->method('save')
            ->willReturn('policy-123');

        $policy = $this->manager->createPolicy(
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 36,
            applicableCategories: [DataCategory::PERSONAL, DataCategory::CONTACT],
            requiresSecureDeletion: true
        );

        $this->assertInstanceOf(RetentionPolicy::class, $policy);
    }

    public function testCreatePolicyThrowsForZeroMonths(): void
    {
        $this->expectException(InvalidRetentionPolicyException::class);
        $this->expectExceptionMessage('Retention period must be at least 1 month');

        $this->manager->createPolicy(
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 0,
            applicableCategories: [DataCategory::PERSONAL]
        );
    }

    public function testCreatePolicyThrowsForNegativeMonths(): void
    {
        $this->expectException(InvalidRetentionPolicyException::class);
        $this->expectExceptionMessage('Retention period must be at least 1 month');

        $this->manager->createPolicy(
            category: RetentionCategory::CUSTOMER,
            retentionMonths: -12,
            applicableCategories: [DataCategory::PERSONAL]
        );
    }

    public function testCreatePolicyThrowsForEmptyCategories(): void
    {
        $this->expectException(InvalidRetentionPolicyException::class);
        $this->expectExceptionMessage('At least one applicable data category is required');

        $this->manager->createPolicy(
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
            applicableCategories: []
        );
    }

    public function testGetPolicyReturnsExistingPolicy(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Customer Data Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 36
        );

        $this->policyQuery
            ->expects($this->once())
            ->method('findById')
            ->with('policy-123')
            ->willReturn($policy);

        $result = $this->manager->getPolicy('policy-123');

        $this->assertSame($policy, $result);
    }

    public function testGetPolicyThrowsWhenNotFound(): void
    {
        $this->policyQuery
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(RetentionPolicyNotFoundException::class);

        $this->manager->getPolicy('nonexistent');
    }

    public function testGetAllPoliciesReturnsArray(): void
    {
        $this->policyQuery
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->manager->getAllPolicies();

        $this->assertIsArray($result);
    }

    public function testGetEffectivePolicyReturnsPolicy(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Customer Retention Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 84,
            requiresSecureDeletion: true,
            allowsLegalHold: true,
            description: 'Standard customer retention',
            legalBasis: 'Legal requirement',
        );

        $this->policyQuery
            ->expects($this->once())
            ->method('findEffectivePolicy')
            ->with(RetentionCategory::CUSTOMER, null)
            ->willReturn($policy);

        $result = $this->manager->getEffectivePolicy(RetentionCategory::CUSTOMER);

        $this->assertSame($policy, $result);
    }

    public function testGetEffectivePolicyReturnsNullWhenNone(): void
    {
        $this->policyQuery
            ->expects($this->once())
            ->method('findEffectivePolicy')
            ->willReturn(null);

        $result = $this->manager->getEffectivePolicy(RetentionCategory::TEMPORARY);

        $this->assertNull($result);
    }

    public function testManagerWorksWithoutAuditLogger(): void
    {
        $managerWithoutLogger = new RetentionPolicyManager(
            $this->policyQuery,
            $this->policyPersist,
            null
        );

        $this->policyPersist
            ->method('save')
            ->willReturn('policy-id');

        // Should not throw
        $policy = $managerWithoutLogger->createPolicy(
            category: RetentionCategory::FINANCIAL,
            retentionMonths: 84,
            applicableCategories: [DataCategory::FINANCIAL]
        );

        $this->assertInstanceOf(RetentionPolicy::class, $policy);
    }
}
