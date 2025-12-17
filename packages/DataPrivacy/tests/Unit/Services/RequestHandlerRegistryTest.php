<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\Enums\RequestStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\Services\RequestHandlerRegistry;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\DataPrivacy\Contracts\RequestHandlerInterface;
use Nexus\DataPrivacy\Exceptions\InvalidRequestException;

#[CoversClass(RequestHandlerRegistry::class)]
final class RequestHandlerRegistryTest extends TestCase
{
    private RequestHandlerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new RequestHandlerRegistry();
    }

    public function testRegisterHandler(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('supports')->willReturn(true);

        $this->registry->register($handler);

        $this->assertTrue($this->registry->hasHandler(RequestType::ACCESS));
    }

    public function testGetHandlerReturnsRegisteredHandler(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('supports')
            ->willReturnCallback(fn(RequestType $type) => $type === RequestType::ACCESS);

        $this->registry->register($handler);

        $result = $this->registry->getHandler(RequestType::ACCESS);

        $this->assertSame($handler, $result);
    }

    public function testGetHandlerThrowsWhenNoHandlerRegistered(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('No handler registered for request type');

        $this->registry->getHandler(RequestType::ERASURE);
    }

    public function testHasHandlerReturnsTrueWhenHandlerExists(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('supports')
            ->willReturnCallback(fn(RequestType $type) => $type === RequestType::PORTABILITY);

        $this->registry->register($handler);

        $this->assertTrue($this->registry->hasHandler(RequestType::PORTABILITY));
    }

    public function testHasHandlerReturnsFalseWhenNoHandler(): void
    {
        $this->assertFalse($this->registry->hasHandler(RequestType::OBJECTION));
    }

    public function testExecuteCallsHandlerExecute(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $request = new DataSubjectRequest(
            id: 'request-123',
            dataSubjectId: $dataSubjectId,
            type: RequestType::ACCESS,
            status: RequestStatus::IN_PROGRESS,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
        );

        $expectedResult = ['success' => true, 'data' => ['name' => 'John']];

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('supports')
            ->willReturnCallback(fn(RequestType $type) => $type === RequestType::ACCESS);
        $handler->expects($this->once())
            ->method('execute')
            ->with($request)
            ->willReturn($expectedResult);

        $this->registry->register($handler);

        $result = $this->registry->execute($request);

        $this->assertSame($expectedResult, $result);
    }

    public function testExecuteThrowsWhenNoHandlerForRequest(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $request = new DataSubjectRequest(
            id: 'request-123',
            dataSubjectId: $dataSubjectId,
            type: RequestType::RECTIFICATION,
            status: RequestStatus::IN_PROGRESS,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
        );

        $this->expectException(InvalidRequestException::class);

        $this->registry->execute($request);
    }

    public function testValidateCallsHandlerValidate(): void
    {
        $dataSubjectId = new DataSubjectId('subject-123');
        $request = new DataSubjectRequest(
            id: 'request-123',
            dataSubjectId: $dataSubjectId,
            type: RequestType::ERASURE,
            status: RequestStatus::PENDING,
            submittedAt: new DateTimeImmutable(),
            deadline: new DateTimeImmutable('+30 days'),
        );

        $validationErrors = ['Missing required field: reason'];

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('supports')
            ->willReturnCallback(fn(RequestType $type) => $type === RequestType::ERASURE);
        $handler->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willReturn($validationErrors);

        $this->registry->register($handler);

        $result = $this->registry->validate($request);

        $this->assertSame($validationErrors, $result);
    }

    public function testMultipleHandlersRegistered(): void
    {
        $accessHandler = $this->createMock(RequestHandlerInterface::class);
        $accessHandler->method('supports')
            ->willReturnCallback(fn(RequestType $type) => $type === RequestType::ACCESS);

        $erasureHandler = $this->createMock(RequestHandlerInterface::class);
        $erasureHandler->method('supports')
            ->willReturnCallback(fn(RequestType $type) => $type === RequestType::ERASURE);

        $this->registry->register($accessHandler);
        $this->registry->register($erasureHandler);

        $this->assertTrue($this->registry->hasHandler(RequestType::ACCESS));
        $this->assertTrue($this->registry->hasHandler(RequestType::ERASURE));
        $this->assertFalse($this->registry->hasHandler(RequestType::PORTABILITY));
    }

    public function testFirstMatchingHandlerIsReturned(): void
    {
        $handler1 = $this->createMock(RequestHandlerInterface::class);
        $handler1->method('supports')->willReturn(true);

        $handler2 = $this->createMock(RequestHandlerInterface::class);
        $handler2->method('supports')->willReturn(true);

        $this->registry->register($handler1);
        $this->registry->register($handler2);

        // Should return the first registered handler that supports the type
        $result = $this->registry->getHandler(RequestType::ACCESS);

        $this->assertSame($handler1, $result);
    }
}
