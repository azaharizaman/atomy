<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\BreachSeverity;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Exceptions\InvalidBreachRecordException;
use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BreachRecord::class)]
final class BreachRecordTest extends TestCase
{
    public function testConstructorWithValidData(): void
    {
        $discoveredAt = new DateTimeImmutable('2024-01-15');
        $occurredAt = new DateTimeImmutable('2024-01-10');

        $breach = new BreachRecord(
            id: 'breach-123',
            title: 'Unauthorized Data Access',
            severity: BreachSeverity::HIGH,
            discoveredAt: $discoveredAt,
            occurredAt: $occurredAt,
            recordsAffected: 1000,
            dataCategories: [DataCategory::PERSONAL, DataCategory::CONTACT],
            description: 'Unauthorized access to customer database',
        );

        $this->assertSame('breach-123', $breach->id);
        $this->assertSame('Unauthorized Data Access', $breach->title);
        $this->assertSame(BreachSeverity::HIGH, $breach->severity);
        $this->assertSame(1000, $breach->recordsAffected);
    }

    public function testConstructorThrowsOnEmptyId(): void
    {
        $this->expectException(InvalidBreachRecordException::class);
        $this->expectExceptionMessage('Breach ID cannot be empty');

        new BreachRecord(
            id: '',
            title: 'Test Breach',
            severity: BreachSeverity::LOW,
            discoveredAt: new DateTimeImmutable(),
            occurredAt: new DateTimeImmutable(),
            recordsAffected: 0,
            dataCategories: [],
            description: 'Test',
        );
    }

    public function testConstructorThrowsOnEmptyTitle(): void
    {
        $this->expectException(InvalidBreachRecordException::class);
        $this->expectExceptionMessage('Breach title cannot be empty');

        new BreachRecord(
            id: 'breach-123',
            title: '',
            severity: BreachSeverity::LOW,
            discoveredAt: new DateTimeImmutable(),
            occurredAt: new DateTimeImmutable(),
            recordsAffected: 0,
            dataCategories: [],
            description: 'Test',
        );
    }

    public function testConstructorThrowsOnNegativeRecordsAffected(): void
    {
        $this->expectException(InvalidBreachRecordException::class);
        $this->expectExceptionMessage('Records affected cannot be negative');

        new BreachRecord(
            id: 'breach-123',
            title: 'Test Breach',
            severity: BreachSeverity::LOW,
            discoveredAt: new DateTimeImmutable(),
            occurredAt: new DateTimeImmutable(),
            recordsAffected: -1,
            dataCategories: [],
            description: 'Test',
        );
    }

    public function testConstructorThrowsWhenOccurredAfterDiscovered(): void
    {
        $this->expectException(InvalidBreachRecordException::class);
        $this->expectExceptionMessage('Breach occurrence date cannot be after discovery date');

        new BreachRecord(
            id: 'breach-123',
            title: 'Test Breach',
            severity: BreachSeverity::LOW,
            discoveredAt: new DateTimeImmutable('2024-01-01'),
            occurredAt: new DateTimeImmutable('2024-01-15'),
            recordsAffected: 0,
            dataCategories: [],
            description: 'Test',
        );
    }

    public function testConstructorThrowsWhenRegulatoryNotifiedWithoutDate(): void
    {
        $this->expectException(InvalidBreachRecordException::class);
        $this->expectExceptionMessage('Regulatory notification date is required when regulatory is notified');

        $now = new DateTimeImmutable();
        $yesterday = $now->modify('-1 day');

        new BreachRecord(
            id: 'breach-123',
            title: 'Test Breach',
            severity: BreachSeverity::LOW,
            discoveredAt: $now,
            occurredAt: $yesterday,
            recordsAffected: 0,
            dataCategories: [],
            description: 'Test',
            regulatoryNotified: true,
        );
    }

    public function testRequiresImmediateEscalationForCriticalSeverity(): void
    {
        $now = new DateTimeImmutable();
        $yesterday = $now->modify('-1 day');

        $breach = new BreachRecord(
            id: 'breach-123',
            title: 'Critical Breach',
            severity: BreachSeverity::CRITICAL,
            discoveredAt: $now,
            occurredAt: $yesterday,
            recordsAffected: 10000,
            dataCategories: [DataCategory::FINANCIAL],
            description: 'Critical system breach',
        );

        // requiresImmediateEscalation is on severity, not on breach
        $this->assertTrue($breach->severity->requiresImmediateEscalation());
    }

    public function testInvolvesSensitiveDataReturnsTrueWhenHealthDataIncluded(): void
    {
        $now = new DateTimeImmutable();
        $yesterday = $now->modify('-1 day');

        $breach = new BreachRecord(
            id: 'breach-123',
            title: 'Health Data Breach',
            severity: BreachSeverity::HIGH,
            discoveredAt: $now,
            occurredAt: $yesterday,
            recordsAffected: 500,
            dataCategories: [DataCategory::HEALTH, DataCategory::PERSONAL],
            description: 'Health records exposed',
        );

        $this->assertTrue($breach->involvesSensitiveData());
    }

    public function testInvolvesSensitiveDataReturnsFalseForNonSensitiveCategories(): void
    {
        $now = new DateTimeImmutable();
        $yesterday = $now->modify('-1 day');

        $breach = new BreachRecord(
            id: 'breach-123',
            title: 'Contact Data Breach',
            severity: BreachSeverity::LOW,
            discoveredAt: $now,
            occurredAt: $yesterday,
            recordsAffected: 100,
            dataCategories: [DataCategory::CONTACT],
            description: 'Contact list exposed',
        );

        $this->assertFalse($breach->involvesSensitiveData());
    }
}
