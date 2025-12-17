<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Enums\RetentionCategory;
use Nexus\DataPrivacy\Exceptions\InvalidRetentionPolicyException;
use Nexus\DataPrivacy\ValueObjects\RetentionPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RetentionPolicy::class)]
final class RetentionPolicyTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Financial Records Policy',
            category: RetentionCategory::FINANCIAL,
            retentionMonths: 84,
            requiresSecureDeletion: true,
        );

        $this->assertSame('policy-123', $policy->id);
        $this->assertSame('Financial Records Policy', $policy->name);
        $this->assertSame(RetentionCategory::FINANCIAL, $policy->category);
        $this->assertSame(84, $policy->retentionMonths);
        $this->assertTrue($policy->requiresSecureDeletion);
    }

    public function testConstructorThrowsOnEmptyId(): void
    {
        $this->expectException(InvalidRetentionPolicyException::class);
        $this->expectExceptionMessage('Policy ID cannot be empty');

        new RetentionPolicy(
            id: '',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
        );
    }

    public function testConstructorThrowsOnEmptyName(): void
    {
        $this->expectException(InvalidRetentionPolicyException::class);
        $this->expectExceptionMessage('Policy name cannot be empty');

        new RetentionPolicy(
            id: 'policy-123',
            name: '',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
        );
    }

    public function testConstructorThrowsOnNegativeRetentionMonths(): void
    {
        $this->expectException(InvalidRetentionPolicyException::class);
        $this->expectExceptionMessage('Retention months cannot be negative');

        new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: -1,
        );
    }

    public function testConstructorThrowsWhenEffectiveToBeforeFrom(): void
    {
        $this->expectException(InvalidRetentionPolicyException::class);
        $this->expectExceptionMessage('Effective end date must be after start date');

        new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
            effectiveFrom: new DateTimeImmutable('2025-01-01'),
            effectiveTo: new DateTimeImmutable('2024-01-01'),
        );
    }

    public function testIsEffectiveReturnsTrueWhenNoDatesSet(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
        );

        $this->assertTrue($policy->isEffective());
    }

    public function testIsEffectiveReturnsFalseBeforeEffectiveFrom(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
            effectiveFrom: new DateTimeImmutable('+1 year'),
        );

        $this->assertFalse($policy->isEffective());
    }

    public function testIsEffectiveReturnsFalseAfterEffectiveTo(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
            effectiveFrom: new DateTimeImmutable('-2 years'),
            effectiveTo: new DateTimeImmutable('-1 year'),
        );

        $this->assertFalse($policy->isEffective());
    }

    public function testCalculateDeletionDateAddsRetentionMonths(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
        );

        $referenceDate = new DateTimeImmutable('2024-01-01');
        $deletionDate = $policy->calculateDeletionDate($referenceDate);

        $this->assertSame('2025-01-01', $deletionDate->format('Y-m-d'));
    }

    public function testCalculateDeletionDateWithZeroRetention(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Immediate Deletion Policy',
            category: RetentionCategory::TEMPORARY,
            retentionMonths: 0,
        );

        $referenceDate = new DateTimeImmutable('2024-01-01');
        $deletionDate = $policy->calculateDeletionDate($referenceDate);

        $this->assertSame($referenceDate, $deletionDate);
    }

    public function testShouldDeleteReturnsTrueWhenPastRetention(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
        );

        $referenceDate = new DateTimeImmutable('-2 years');
        
        $this->assertTrue($policy->shouldDelete($referenceDate));
    }

    public function testShouldDeleteReturnsFalseWithinRetention(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
        );

        $referenceDate = new DateTimeImmutable('-6 months');
        
        $this->assertFalse($policy->shouldDelete($referenceDate));
    }

    public function testShouldDeleteReturnsFalseWithLegalHold(): void
    {
        $policy = new RetentionPolicy(
            id: 'policy-123',
            name: 'Test Policy',
            category: RetentionCategory::CUSTOMER,
            retentionMonths: 12,
            allowsLegalHold: true,
        );

        $referenceDate = new DateTimeImmutable('-2 years');
        
        $this->assertFalse($policy->shouldDelete($referenceDate, hasLegalHold: true));
    }
}
