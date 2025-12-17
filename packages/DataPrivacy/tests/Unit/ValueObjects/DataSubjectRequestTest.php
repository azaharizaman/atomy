<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSubjectRequest::class)]
final class DataSubjectRequestTest extends TestCase
{
    private DataSubjectId $dataSubjectId;

    protected function setUp(): void
    {
        $this->dataSubjectId = new DataSubjectId('subject-123');
    }

    public function testConstructorWithValidData(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $deadline = new DateTimeImmutable('2024-01-31');

        $request = new DataSubjectRequest(
            id: 'req-123',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::PENDING,
            submittedAt: $submittedAt,
            deadline: $deadline,
        );

        $this->assertSame('req-123', $request->id);
        $this->assertSame($this->dataSubjectId, $request->dataSubjectId);
        $this->assertSame(RequestType::ACCESS, $request->type);
        $this->assertSame(RequestStatus::PENDING, $request->status);
    }

    public function testConstructorThrowsOnEmptyId(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request ID cannot be empty');

        new DataSubjectRequest(
            id: '',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::PENDING,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
        );
    }

    public function testConstructorThrowsWhenDeadlineBeforeSubmission(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Deadline cannot be before submission date');

        new DataSubjectRequest(
            id: 'req-123',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::PENDING,
            submittedAt: new DateTimeImmutable('2024-02-01'),
            deadline: new DateTimeImmutable('2024-01-01'),
        );
    }

    public function testConstructorThrowsWhenRejectedWithoutReason(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Rejection reason is required');

        new DataSubjectRequest(
            id: 'req-123',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::REJECTED,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
        );
    }

    public function testRejectedRequestWithReasonIsValid(): void
    {
        $request = new DataSubjectRequest(
            id: 'req-123',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::REJECTED,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
            rejectionReason: 'Identity could not be verified',
        );

        $this->assertSame(RequestStatus::REJECTED, $request->status);
        $this->assertSame('Identity could not be verified', $request->rejectionReason);
    }

    public function testCreateFactoryMethod(): void
    {
        $request = DataSubjectRequest::create(
            id: 'req-456',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ERASURE,
            deadlineDays: 30,
            description: 'Please delete my data',
        );

        $this->assertSame('req-456', $request->id);
        $this->assertSame(RequestType::ERASURE, $request->type);
        $this->assertSame(RequestStatus::PENDING, $request->status);
        $this->assertSame('Please delete my data', $request->description);
    }

    public function testIsOverdueReturnsFalseWhenNotOverdue(): void
    {
        $request = DataSubjectRequest::create(
            id: 'req-123',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            deadlineDays: 30,
        );

        $this->assertFalse($request->isOverdue());
    }

    public function testIsOverdueReturnsTrueWhenPastDeadline(): void
    {
        $submittedAt = new DateTimeImmutable('-60 days');
        $deadline = new DateTimeImmutable('-30 days');

        $request = new DataSubjectRequest(
            id: 'req-123',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::IN_PROGRESS,
            submittedAt: $submittedAt,
            deadline: $deadline,
        );

        $this->assertTrue($request->isOverdue());
    }

    public function testIsOverdueReturnsFalseWhenCompleted(): void
    {
        $submittedAt = new DateTimeImmutable('-60 days');
        $deadline = new DateTimeImmutable('-30 days');

        $request = new DataSubjectRequest(
            id: 'req-123',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::COMPLETED,
            submittedAt: $submittedAt,
            deadline: $deadline,
            completedAt: new DateTimeImmutable('-35 days'),
        );

        $this->assertFalse($request->isOverdue());
    }

    public function testDaysRemainingReturnsPositiveWhenNotOverdue(): void
    {
        $request = DataSubjectRequest::create(
            id: 'req-123',
            dataSubjectId: $this->dataSubjectId,
            type: RequestType::ACCESS,
            deadlineDays: 30,
        );

        $remaining = $request->getDaysRemaining();
        $this->assertGreaterThan(0, $remaining);
        $this->assertLessThanOrEqual(30, $remaining);
    }
}
