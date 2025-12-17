<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services\Handlers;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\ConsentManagerInterface;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Contracts\External\PartyProviderInterface;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;
use Nexus\DataPrivacy\Services\Handlers\ErasureRequestHandler;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ErasureRequestHandler::class)]
final class ErasureRequestHandlerTest extends TestCase
{
    private ErasureRequestHandler $handler;
    private PartyProviderInterface&MockObject $partyProvider;
    private ConsentManagerInterface&MockObject $consentManager;
    private AuditLoggerInterface&MockObject $auditLogger;

    protected function setUp(): void
    {
        $this->partyProvider = $this->createMock(PartyProviderInterface::class);
        $this->consentManager = $this->createMock(ConsentManagerInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->handler = new ErasureRequestHandler(
            $this->partyProvider,
            $this->consentManager,
            $this->auditLogger,
        );
    }

    #[Test]
    public function it_supports_erasure_request_type(): void
    {
        $this->assertTrue($this->handler->supports(RequestType::ERASURE));
        $this->assertFalse($this->handler->supports(RequestType::ACCESS));
        $this->assertFalse($this->handler->supports(RequestType::RECTIFICATION));
        $this->assertFalse($this->handler->supports(RequestType::PORTABILITY));
    }

    #[Test]
    public function it_executes_erasure_request_successfully(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject-123');
        $request = $this->createRequest($dataSubjectId, RequestType::ERASURE);

        $this->partyProvider
            ->expects($this->exactly(2))
            ->method('partyExists')
            ->with('test-subject-123')
            ->willReturn(true);

        $this->consentManager
            ->expects($this->once())
            ->method('withdrawAllConsents')
            ->with($dataSubjectId)
            ->willReturn(3);

        $this->partyProvider
            ->expects($this->once())
            ->method('deletePersonalData')
            ->with('test-subject-123');

        $this->auditLogger
            ->expects($this->once())
            ->method('logDataDeleted');

        $result = $this->handler->execute($request);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['withdrawn_consents']);
    }

    #[Test]
    public function it_returns_error_when_party_not_found(): void
    {
        $dataSubjectId = new DataSubjectId('unknown-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::ERASURE);

        $this->partyProvider
            ->expects($this->once())
            ->method('partyExists')
            ->with('unknown-subject')
            ->willReturn(false);

        $result = $this->handler->execute($request);

        $this->assertFalse($result['success']);
        $this->assertSame('Data subject not found', $result['error']);
    }

    #[Test]
    public function it_throws_exception_for_unsupported_request_type(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::ACCESS);

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
            type: RequestType::ERASURE,
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
        $request = $this->createRequest($dataSubjectId, RequestType::ERASURE);

        $this->partyProvider
            ->expects($this->once())
            ->method('partyExists')
            ->willReturn(true);

        $errors = $this->handler->validate($request);

        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_returns_validation_errors(): void
    {
        $dataSubjectId = new DataSubjectId('unknown-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::ACCESS);

        $this->partyProvider
            ->expects($this->once())
            ->method('partyExists')
            ->willReturn(false);

        $errors = $this->handler->validate($request);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('Unsupported request type', $errors[0]);
        $this->assertStringContainsString('Data subject not found', $errors[1]);
    }

    #[Test]
    public function it_returns_estimated_processing_days(): void
    {
        $this->assertSame(14, $this->handler->getEstimatedProcessingDays());
    }

    #[Test]
    public function it_works_without_audit_logger(): void
    {
        $handler = new ErasureRequestHandler(
            $this->partyProvider,
            $this->consentManager,
            null,
        );

        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::ERASURE);

        $this->partyProvider->method('partyExists')->willReturn(true);
        $this->consentManager->method('withdrawAllConsents')->willReturn(0);
        $this->partyProvider->method('deletePersonalData');

        $result = $handler->execute($request);

        $this->assertTrue($result['success']);
    }

    private function createRequest(
        DataSubjectId $dataSubjectId,
        RequestType $type,
    ): DataSubjectRequest {
        return new DataSubjectRequest(
            id: 'req-' . uniqid(),
            dataSubjectId: $dataSubjectId,
            type: $type,
            status: RequestStatus::PENDING,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
        );
    }
}
