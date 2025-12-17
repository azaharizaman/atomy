<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\BreachRecordPersistInterface;
use Nexus\DataPrivacy\Contracts\BreachRecordQueryInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Contracts\External\NotificationDispatcherInterface;
use Nexus\DataPrivacy\Contracts\External\StorageInterface;
use Nexus\DataPrivacy\Enums\BreachSeverity;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Exceptions\BreachRecordNotFoundException;
use Nexus\DataPrivacy\Exceptions\InvalidBreachRecordException;
use Nexus\DataPrivacy\Services\BreachRecordManager;
use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BreachRecordManager::class)]
final class BreachRecordManagerTest extends TestCase
{
    private BreachRecordQueryInterface&MockObject $breachQuery;
    private BreachRecordPersistInterface&MockObject $breachPersist;
    private AuditLoggerInterface&MockObject $auditLogger;
    private NotificationDispatcherInterface&MockObject $notifier;
    private StorageInterface&MockObject $storage;
    private BreachRecordManager $manager;

    protected function setUp(): void
    {
        $this->breachQuery = $this->createMock(BreachRecordQueryInterface::class);
        $this->breachPersist = $this->createMock(BreachRecordPersistInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->notifier = $this->createMock(NotificationDispatcherInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);

        $this->manager = new BreachRecordManager(
            $this->breachQuery,
            $this->breachPersist,
            $this->auditLogger,
            $this->notifier,
            $this->storage
        );
    }

    public function testReportBreachSucceeds(): void
    {
        $this->breachPersist
            ->expects($this->once())
            ->method('save')
            ->willReturn('breach-123');

        $breach = $this->manager->reportBreach(
            description: 'Unauthorized access to customer database',
            severity: BreachSeverity::HIGH,
            recordsAffected: 1000,
            affectedCategories: [DataCategory::PERSONAL, DataCategory::CONTACT],
            detectedAt: new DateTimeImmutable()
        );

        $this->assertInstanceOf(BreachRecord::class, $breach);
        $this->assertSame(BreachSeverity::HIGH, $breach->severity);
    }

    public function testReportBreachThrowsForNegativeRecords(): void
    {
        $this->expectException(InvalidBreachRecordException::class);
        $this->expectExceptionMessage('cannot be negative');

        $this->manager->reportBreach(
            description: 'Test breach',
            severity: BreachSeverity::LOW,
            recordsAffected: -100,
            affectedCategories: [DataCategory::PERSONAL],
            detectedAt: new DateTimeImmutable()
        );
    }

    public function testReportBreachThrowsForEmptyCategories(): void
    {
        $this->expectException(InvalidBreachRecordException::class);
        $this->expectExceptionMessage('At least one affected data category');

        $this->manager->reportBreach(
            description: 'Test breach',
            severity: BreachSeverity::LOW,
            recordsAffected: 100,
            affectedCategories: [],
            detectedAt: new DateTimeImmutable()
        );
    }

    public function testGetBreachReturnsExistingBreach(): void
    {
        $breach = new BreachRecord(
            id: 'breach-123',
            title: 'Data Breach',
            severity: BreachSeverity::MEDIUM,
            discoveredAt: new DateTimeImmutable(),
            occurredAt: new DateTimeImmutable('-1 hour'),
            recordsAffected: 500,
            dataCategories: [DataCategory::PERSONAL],
            description: 'Breach description'
        );

        $this->breachQuery
            ->expects($this->once())
            ->method('findById')
            ->with('breach-123')
            ->willReturn($breach);

        $result = $this->manager->getBreach('breach-123');

        $this->assertSame($breach, $result);
    }

    public function testGetBreachThrowsWhenNotFound(): void
    {
        $this->breachQuery
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(BreachRecordNotFoundException::class);

        $this->manager->getBreach('nonexistent-breach');
    }

    public function testGetAllBreachesReturnsArray(): void
    {
        $this->breachQuery
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->manager->getAllBreaches();

        $this->assertIsArray($result);
    }

    public function testGetUnresolvedBreachesReturnsArray(): void
    {
        $this->breachQuery
            ->expects($this->once())
            ->method('findUnresolved')
            ->willReturn([]);

        $result = $this->manager->getUnresolvedBreaches();

        $this->assertIsArray($result);
    }

    public function testManagerWorksWithoutOptionalDependencies(): void
    {
        $managerWithoutOptional = new BreachRecordManager(
            $this->breachQuery,
            $this->breachPersist,
            null,
            null,
            null
        );

        $this->breachPersist
            ->method('save')
            ->willReturn('breach-id');

        // Should not throw
        $breach = $managerWithoutOptional->reportBreach(
            description: 'Test breach',
            severity: BreachSeverity::LOW,
            recordsAffected: 10,
            affectedCategories: [DataCategory::PERSONAL],
            detectedAt: new DateTimeImmutable()
        );

        $this->assertInstanceOf(BreachRecord::class, $breach);
    }
}
