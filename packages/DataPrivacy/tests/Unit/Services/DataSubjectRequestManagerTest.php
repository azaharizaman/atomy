<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestPersistInterface;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestQueryInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Contracts\External\NotificationDispatcherInterface;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;
use Nexus\DataPrivacy\Exceptions\RequestNotFoundException;
use Nexus\DataPrivacy\Services\DataSubjectRequestManager;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSubjectRequestManager::class)]
final class DataSubjectRequestManagerTest extends TestCase
{
    private DataSubjectRequestQueryInterface&MockObject $requestQuery;
    private DataSubjectRequestPersistInterface&MockObject $requestPersist;
    private AuditLoggerInterface&MockObject $auditLogger;
    private NotificationDispatcherInterface&MockObject $notifier;
    private DataSubjectRequestManager $manager;

    protected function setUp(): void
    {
        $this->requestQuery = $this->createMock(DataSubjectRequestQueryInterface::class);
        $this->requestPersist = $this->createMock(DataSubjectRequestPersistInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);
        $this->notifier = $this->createMock(NotificationDispatcherInterface::class);

        $this->manager = new DataSubjectRequestManager(
            $this->requestQuery,
            $this->requestPersist,
            $this->auditLogger,
            $this->notifier
        );
    }

    public function testSubmitRequestSucceeds(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $type = RequestType::ACCESS;

        $this->requestQuery
            ->expects($this->once())
            ->method('hasPendingRequest')
            ->with('subject-123', $type)
            ->willReturn(false);

        $this->requestPersist
            ->expects($this->once())
            ->method('save')
            ->willReturn('request-id-123');

        $request = $this->manager->submitRequest($dataSubjectId, $type, 30);

        $this->assertInstanceOf(DataSubjectRequest::class, $request);
        $this->assertSame($type, $request->type);
        $this->assertSame(RequestStatus::PENDING, $request->status);
    }

    public function testSubmitRequestThrowsWhenPendingExists(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $type = RequestType::ERASURE;

        $this->requestQuery
            ->expects($this->once())
            ->method('hasPendingRequest')
            ->willReturn(true);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('pending request');

        $this->manager->submitRequest($dataSubjectId, $type);
    }

    public function testGetRequestReturnsExistingRequest(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $request = new DataSubjectRequest(
            id: 'request-123',
            dataSubjectId: $dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::PENDING,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
        );

        $this->requestQuery
            ->expects($this->once())
            ->method('findById')
            ->with('request-123')
            ->willReturn($request);

        $result = $this->manager->getRequest('request-123');

        $this->assertSame($request, $result);
    }

    public function testGetRequestThrowsWhenNotFound(): void
    {
        $this->requestQuery
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $this->expectException(RequestNotFoundException::class);

        $this->manager->getRequest('nonexistent-123');
    }

    public function testGetRequestsByDataSubjectReturnsArray(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');

        $this->requestQuery
            ->expects($this->once())
            ->method('findByDataSubject')
            ->with('subject-123')
            ->willReturn([]);

        $result = $this->manager->getRequestsByDataSubject($dataSubjectId);

        $this->assertIsArray($result);
    }

    public function testTransitionStatusSucceeds(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $existingRequest = new DataSubjectRequest(
            id: 'request-123',
            dataSubjectId: $dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::PENDING,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
        );

        $this->requestQuery
            ->expects($this->once())
            ->method('findById')
            ->willReturn($existingRequest);

        $this->requestPersist
            ->expects($this->once())
            ->method('update');

        $result = $this->manager->transitionStatus('request-123', RequestStatus::IN_PROGRESS);

        $this->assertSame(RequestStatus::IN_PROGRESS, $result->status);
    }

    public function testTransitionStatusThrowsOnInvalidTransition(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $completedRequest = new DataSubjectRequest(
            id: 'request-123',
            dataSubjectId: $dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::COMPLETED,
            submittedAt: new DateTimeImmutable('-10 days'),
            deadline: new DateTimeImmutable('+20 days'),
            completedAt: new DateTimeImmutable('-5 days'),
        );

        $this->requestQuery
            ->expects($this->once())
            ->method('findById')
            ->willReturn($completedRequest);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Cannot transition');

        $this->manager->transitionStatus('request-123', RequestStatus::IN_PROGRESS);
    }

    public function testManagerWorksWithoutOptionalDependencies(): void
    {
        $managerWithoutOptional = new DataSubjectRequestManager(
            $this->requestQuery,
            $this->requestPersist,
            null,
            null
        );

        $dataSubjectId = new DataSubjectId('subject-123');

        $this->requestQuery
            ->method('hasPendingRequest')
            ->willReturn(false);

        $this->requestPersist
            ->method('save')
            ->willReturn('request-id');

        // Should not throw even without optional dependencies
        $request = $managerWithoutOptional->submitRequest($dataSubjectId, RequestType::ACCESS);
        $this->assertInstanceOf(DataSubjectRequest::class, $request);
    }
}
