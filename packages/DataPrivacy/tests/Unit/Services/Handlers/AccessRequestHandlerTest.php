<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services\Handlers;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\External\AuditLoggerInterface;
use Nexus\DataPrivacy\Contracts\External\PartyProviderInterface;
use Nexus\DataPrivacy\Contracts\External\StorageInterface;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;
use Nexus\DataPrivacy\Services\Handlers\AccessRequestHandler;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccessRequestHandler::class)]
final class AccessRequestHandlerTest extends TestCase
{
    private AccessRequestHandler $handler;
    private PartyProviderInterface&MockObject $partyProvider;
    private StorageInterface&MockObject $storage;
    private AuditLoggerInterface&MockObject $auditLogger;

    protected function setUp(): void
    {
        $this->partyProvider = $this->createMock(PartyProviderInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->handler = new AccessRequestHandler(
            $this->partyProvider,
            $this->storage,
            $this->auditLogger,
        );
    }

    #[Test]
    public function it_supports_access_request_type(): void
    {
        $this->assertTrue($this->handler->supports(RequestType::ACCESS));
        $this->assertFalse($this->handler->supports(RequestType::ERASURE));
        $this->assertFalse($this->handler->supports(RequestType::RECTIFICATION));
        $this->assertFalse($this->handler->supports(RequestType::PORTABILITY));
    }

    #[Test]
    public function it_executes_access_request_successfully(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject-123');
        $request = $this->createRequest($dataSubjectId, RequestType::ACCESS);

        $personalData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->partyProvider
            ->expects($this->once())
            ->method('partyExists')
            ->with('test-subject-123')
            ->willReturn(true);

        $this->partyProvider
            ->expects($this->once())
            ->method('getPersonalData')
            ->with('test-subject-123')
            ->willReturn($personalData);

        $this->partyProvider
            ->expects($this->once())
            ->method('exportPersonalData')
            ->with('test-subject-123')
            ->willReturn(['name' => 'John Doe', 'email' => 'john@example.com']);

        $this->storage
            ->expects($this->once())
            ->method('storeExport')
            ->willReturn('export-ref-001');

        $this->storage
            ->expects($this->once())
            ->method('getExportUrl')
            ->willReturn('https://example.com/download/export-ref-001');

        $this->auditLogger
            ->expects($this->once())
            ->method('logDataExported');

        $result = $this->handler->execute($request);

        $this->assertTrue($result['success']);
        $this->assertSame($personalData, $result['data']);
        $this->assertSame('export-ref-001', $result['export_reference']);
        $this->assertSame('https://example.com/download/export-ref-001', $result['download_url']);
    }

    #[Test]
    public function it_returns_error_when_party_not_found(): void
    {
        $dataSubjectId = new DataSubjectId('unknown-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::ACCESS);

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
            type: RequestType::ACCESS,
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
        $request = $this->createRequest($dataSubjectId, RequestType::ACCESS);

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
        $request = $this->createRequest($dataSubjectId, RequestType::ERASURE);

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
        $this->assertSame(3, $this->handler->getEstimatedProcessingDays());
    }

    #[Test]
    public function it_works_without_audit_logger(): void
    {
        $handler = new AccessRequestHandler(
            $this->partyProvider,
            $this->storage,
            null, // No audit logger
        );

        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::ACCESS);

        $this->partyProvider->method('partyExists')->willReturn(true);
        $this->partyProvider->method('getPersonalData')->willReturn([]);
        $this->partyProvider->method('exportPersonalData')->willReturn([]);
        $this->storage->method('storeExport')->willReturn('ref');
        $this->storage->method('getExportUrl')->willReturn('url');

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
