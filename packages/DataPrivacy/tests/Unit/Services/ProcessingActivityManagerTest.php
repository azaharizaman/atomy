<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services;

use Nexus\DataPrivacy\Contracts\ProcessingActivityPersistInterface;
use Nexus\DataPrivacy\Contracts\ProcessingActivityQueryInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\Enums\LawfulBasisType;
use Nexus\DataPrivacy\Exceptions\InvalidProcessingActivityException;
use Nexus\DataPrivacy\Exceptions\ProcessingActivityNotFoundException;
use Nexus\DataPrivacy\Services\ProcessingActivityManager;
use Nexus\DataPrivacy\ValueObjects\ProcessingActivity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessingActivityManager::class)]
final class ProcessingActivityManagerTest extends TestCase
{
    private ProcessingActivityQueryInterface&MockObject $activityQuery;
    private ProcessingActivityPersistInterface&MockObject $activityPersist;
    private AuditLoggerInterface&MockObject $auditLogger;
    private ProcessingActivityManager $manager;

    protected function setUp(): void
    {
        $this->activityQuery = $this->createMock(ProcessingActivityQueryInterface::class);
        $this->activityPersist = $this->createMock(ProcessingActivityPersistInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->manager = new ProcessingActivityManager(
            $this->activityQuery,
            $this->activityPersist,
            $this->auditLogger
        );
    }

    public function testRegisterActivitySucceeds(): void
    {
        $this->activityPersist
            ->expects($this->once())
            ->method('save')
            ->willReturn('activity-123');

        $activity = $this->manager->registerActivity(
            name: 'Customer Data Processing',
            purpose: 'Process customer orders',
            lawfulBasis: LawfulBasisType::CONTRACT,
            dataCategories: [DataCategory::PERSONAL, DataCategory::CONTACT],
            controllerName: 'Acme Corp'
        );

        $this->assertInstanceOf(ProcessingActivity::class, $activity);
    }

    public function testRegisterActivityThrowsForEmptyName(): void
    {
        $this->expectException(InvalidProcessingActivityException::class);
        $this->expectExceptionMessage('Activity name is required');

        $this->manager->registerActivity(
            name: '',
            purpose: 'Process data',
            lawfulBasis: LawfulBasisType::CONSENT,
            dataCategories: [DataCategory::PERSONAL],
            controllerName: 'Test Corp'
        );
    }

    public function testRegisterActivityThrowsForEmptyPurpose(): void
    {
        $this->expectException(InvalidProcessingActivityException::class);
        $this->expectExceptionMessage('Purpose is required');

        $this->manager->registerActivity(
            name: 'Test Activity',
            purpose: '',
            lawfulBasis: LawfulBasisType::CONSENT,
            dataCategories: [DataCategory::PERSONAL],
            controllerName: 'Test Corp'
        );
    }

    public function testRegisterActivityThrowsForEmptyCategories(): void
    {
        $this->expectException(InvalidProcessingActivityException::class);
        $this->expectExceptionMessage('At least one data category is required');

        $this->manager->registerActivity(
            name: 'Test Activity',
            purpose: 'Test purpose',
            lawfulBasis: LawfulBasisType::CONSENT,
            dataCategories: [],
            controllerName: 'Test Corp'
        );
    }

    public function testGetActivityReturnsExistingActivity(): void
    {
        $activity = new ProcessingActivity(
            id: 'activity-123',
            name: 'Test Activity',
            purpose: 'Testing',
            lawfulBasis: LawfulBasisType::CONSENT,
            dataCategories: [DataCategory::PERSONAL],
            controllerName: 'Test Controller',
        );

        $this->activityQuery
            ->expects($this->once())
            ->method('findById')
            ->with('activity-123')
            ->willReturn($activity);

        $result = $this->manager->getActivity('activity-123');

        $this->assertSame($activity, $result);
    }

    public function testGetActivityThrowsWhenNotFound(): void
    {
        $this->activityQuery
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(ProcessingActivityNotFoundException::class);

        $this->manager->getActivity('nonexistent');
    }

    public function testGetAllActivitiesReturnsArray(): void
    {
        $this->activityQuery
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = $this->manager->getAllActivities();

        $this->assertIsArray($result);
    }

    public function testGetActiveActivitiesReturnsArray(): void
    {
        $this->activityQuery
            ->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $result = $this->manager->getActiveActivities();

        $this->assertIsArray($result);
    }

    public function testManagerWorksWithoutAuditLogger(): void
    {
        $managerWithoutLogger = new ProcessingActivityManager(
            $this->activityQuery,
            $this->activityPersist,
            null
        );

        $this->activityPersist
            ->method('save')
            ->willReturn('activity-id');

        // Should not throw
        $activity = $managerWithoutLogger->registerActivity(
            name: 'Test Activity',
            purpose: 'Test purpose',
            lawfulBasis: LawfulBasisType::CONTRACT,
            dataCategories: [DataCategory::PERSONAL],
            controllerName: 'Test Corp'
        );

        $this->assertInstanceOf(ProcessingActivity::class, $activity);
    }
}
