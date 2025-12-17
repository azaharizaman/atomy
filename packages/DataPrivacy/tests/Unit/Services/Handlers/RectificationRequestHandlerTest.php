<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services\Handlers;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Contracts\External\PartyProviderInterface;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;
use Nexus\DataPrivacy\Services\Handlers\RectificationRequestHandler;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RectificationRequestHandler::class)]
final class RectificationRequestHandlerTest extends TestCase
{
    private RectificationRequestHandler $handler;
    private PartyProviderInterface&MockObject $partyProvider;
    private AuditLoggerInterface&MockObject $auditLogger;

    protected function setUp(): void
    {
        $this->partyProvider = $this->createMock(PartyProviderInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->handler = new RectificationRequestHandler(
            $this->partyProvider,
            $this->auditLogger,
        );
    }

    #[Test]
    public function it_supports_rectification_request_type(): void
    {
        $this->assertTrue($this->handler->supports(RequestType::RECTIFICATION));
        $this->assertFalse($this->handler->supports(RequestType::ACCESS));
        $this->assertFalse($this->handler->supports(RequestType::ERASURE));
        $this->assertFalse($this->handler->supports(RequestType::PORTABILITY));
    }

    #[Test]
    public function it_executes_rectification_request_successfully(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject-123');
        $corrections = ['email' => 'new@example.com', 'phone' => '+60123456789'];
        $request = $this->createRequest($dataSubjectId, RequestType::RECTIFICATION, ['corrections' => $corrections]);

        $this->partyProvider
            ->expects($this->once())
            ->method('partyExists')
            ->with('test-subject-123')
            ->willReturn(true);

        $this->partyProvider
            ->expects($this->once())
            ->method('getPersonalData')
            ->with('test-subject-123')
            ->willReturn(['email' => 'old@example.com']);

        $this->partyProvider
            ->expects($this->once())
            ->method('rectifyPersonalData')
            ->with('test-subject-123', $corrections);

        $this->auditLogger
            ->expects($this->once())
            ->method('log');

        $result = $this->handler->execute($request);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['fields_updated']);
        $this->assertSame(['email', 'phone'], $result['corrections_applied']);
    }

    #[Test]
    public function it_returns_error_when_no_corrections_provided(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::RECTIFICATION);

        $this->partyProvider->method('partyExists')->willReturn(true);

        $result = $this->handler->execute($request);

        $this->assertFalse($result['success']);
        $this->assertSame('No corrections provided in request', $result['error']);
    }

    #[Test]
    public function it_returns_error_when_party_not_found(): void
    {
        $dataSubjectId = new DataSubjectId('unknown-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::RECTIFICATION, ['corrections' => ['email' => 'new@email.com']]);

        $this->partyProvider
            ->expects($this->once())
            ->method('partyExists')
            ->willReturn(false);

        $result = $this->handler->execute($request);

        $this->assertFalse($result['success']);
        $this->assertSame('Data subject not found', $result['error']);
    }

    #[Test]
    public function it_throws_exception_for_unsupported_request_type(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::ERASURE);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Handler does not support request type');

        $this->handler->execute($request);
    }

    #[Test]
    public function it_throws_exception_for_already_completed_request(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = new DataSubjectRequest(
            id: 'req-001',
            dataSubjectId: $dataSubjectId,
            type: RequestType::RECTIFICATION,
            status: RequestStatus::COMPLETED,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
            completedAt: new DateTimeImmutable(),
        );

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Request is already completed');

        $this->handler->execute($request);
    }

    #[Test]
    public function it_validates_request_successfully(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::RECTIFICATION, ['corrections' => ['email' => 'new@example.com']]);

        $this->partyProvider->method('partyExists')->willReturn(true);

        $errors = $this->handler->validate($request);

        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_returns_validation_errors(): void
    {
        $dataSubjectId = new DataSubjectId('unknown-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::RECTIFICATION);

        $this->partyProvider->method('partyExists')->willReturn(false);

        $errors = $this->handler->validate($request);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('Data subject not found', $errors[0]);
        $this->assertStringContainsString('corrections', $errors[1]);
    }

    #[Test]
    public function it_returns_estimated_processing_days(): void
    {
        $this->assertSame(5, $this->handler->getEstimatedProcessingDays());
    }

    #[Test]
    public function it_works_without_audit_logger(): void
    {
        $handler = new RectificationRequestHandler(
            $this->partyProvider,
            null,
        );

        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::RECTIFICATION, ['corrections' => ['name' => 'New Name']]);

        $this->partyProvider->method('partyExists')->willReturn(true);
        $this->partyProvider->method('getPersonalData')->willReturn([]);
        $this->partyProvider->method('rectifyPersonalData');

        $result = $handler->execute($request);

        $this->assertTrue($result['success']);
    }

    private function createRequest(
        DataSubjectId $dataSubjectId,
        RequestType $type,
        array $metadata = [],
    ): DataSubjectRequest {
        return new DataSubjectRequest(
            id: 'req-' . uniqid(),
            dataSubjectId: $dataSubjectId,
            type: $type,
            status: RequestStatus::PENDING,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
            metadata: $metadata,
        );
    }
}
