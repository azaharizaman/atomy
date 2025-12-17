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
use Nexus\DataPrivacy\Services\Handlers\PortabilityRequestHandler;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PortabilityRequestHandler::class)]
final class PortabilityRequestHandlerTest extends TestCase
{
    private PortabilityRequestHandler $handler;
    private PartyProviderInterface&MockObject $partyProvider;
    private StorageInterface&MockObject $storage;
    private AuditLoggerInterface&MockObject $auditLogger;

    protected function setUp(): void
    {
        $this->partyProvider = $this->createMock(PartyProviderInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->auditLogger = $this->createMock(AuditLoggerInterface::class);

        $this->handler = new PortabilityRequestHandler(
            $this->partyProvider,
            $this->storage,
            $this->auditLogger,
        );
    }

    #[Test]
    public function it_supports_portability_request_type(): void
    {
        $this->assertTrue($this->handler->supports(RequestType::PORTABILITY));
        $this->assertFalse($this->handler->supports(RequestType::ACCESS));
        $this->assertFalse($this->handler->supports(RequestType::ERASURE));
        $this->assertFalse($this->handler->supports(RequestType::RECTIFICATION));
    }

    #[Test]
    public function it_executes_portability_request_successfully(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject-123');
        $request = $this->createRequest($dataSubjectId, RequestType::PORTABILITY, ['format' => 'json']);

        $this->partyProvider
            ->expects($this->once())
            ->method('partyExists')
            ->with('test-subject-123')
            ->willReturn(true);

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
        $this->assertSame('json', $result['format']);
        $this->assertSame('export-ref-001', $result['export_reference']);
    }

    #[Test]
    public function it_executes_with_csv_format(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::PORTABILITY, ['format' => 'csv']);

        $this->partyProvider->method('partyExists')->willReturn(true);
        $this->partyProvider->method('exportPersonalData')->willReturn(['name' => 'John', 'email' => 'john@example.com']);
        $this->storage->method('storeExport')->willReturn('ref');
        $this->storage->method('getExportUrl')->willReturn('url');

        $result = $this->handler->execute($request);

        $this->assertTrue($result['success']);
        $this->assertSame('csv', $result['format']);
    }

    #[Test]
    public function it_returns_error_for_unsupported_format(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::PORTABILITY, ['format' => 'pdf']);

        $this->partyProvider->method('partyExists')->willReturn(true);

        $result = $this->handler->execute($request);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported format', $result['error']);
    }

    #[Test]
    public function it_returns_error_when_party_not_found(): void
    {
        $dataSubjectId = new DataSubjectId('unknown-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::PORTABILITY);

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
    public function it_validates_request_successfully(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::PORTABILITY, ['format' => 'json']);

        $this->partyProvider->method('partyExists')->willReturn(true);

        $errors = $this->handler->validate($request);

        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_returns_validation_errors_for_unsupported_format(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::PORTABILITY, ['format' => 'invalid']);

        $this->partyProvider->method('partyExists')->willReturn(true);

        $errors = $this->handler->validate($request);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Unsupported format', $errors[0]);
    }

    #[Test]
    public function it_returns_estimated_processing_days(): void
    {
        $this->assertSame(5, $this->handler->getEstimatedProcessingDays());
    }

    #[Test]
    public function it_defaults_to_json_format(): void
    {
        $dataSubjectId = new DataSubjectId('test-subject');
        $request = $this->createRequest($dataSubjectId, RequestType::PORTABILITY);

        $this->partyProvider->method('partyExists')->willReturn(true);
        $this->partyProvider->method('exportPersonalData')->willReturn([]);
        $this->storage->method('storeExport')->willReturn('ref');
        $this->storage->method('getExportUrl')->willReturn('url');

        $result = $this->handler->execute($request);

        $this->assertTrue($result['success']);
        $this->assertSame('json', $result['format']);
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
