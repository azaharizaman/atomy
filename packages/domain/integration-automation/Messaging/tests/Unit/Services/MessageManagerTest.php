<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\Services;

use Nexus\Messaging\Contracts\MessageTemplateEngineInterface;
use Nexus\Messaging\Contracts\MessagingConnectorInterface;
use Nexus\Messaging\Contracts\MessagingRepositoryInterface;
use Nexus\Messaging\Contracts\RateLimiterInterface;
use Nexus\Messaging\Enums\Channel;
use Nexus\Messaging\Enums\DeliveryStatus;
use Nexus\Messaging\Enums\Direction;
use Nexus\Messaging\Exceptions\MessageDeliveryException;
use Nexus\Messaging\Exceptions\MessageNotFoundException;
use Nexus\Messaging\Exceptions\RateLimitExceededException;
use Nexus\Messaging\Services\MessageManager;
use Nexus\Messaging\ValueObjects\MessageRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Nexus\Messaging\Services\MessageManager
 */
final class MessageManagerTest extends TestCase
{
    private MessagingRepositoryInterface $repository;
    private MessagingConnectorInterface $connector;
    private LoggerInterface $logger;
    private RateLimiterInterface $rateLimiter;
    private MessageTemplateEngineInterface $templateEngine;
    private MessageManager $manager;
    private array $auditEvents = [];

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MessagingRepositoryInterface::class);
        $this->connector = $this->createMock(MessagingConnectorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->rateLimiter = $this->createMock(RateLimiterInterface::class);
        $this->templateEngine = $this->createMock(MessageTemplateEngineInterface::class);
        $this->auditEvents = [];
        
        $auditLogger = function(string $eventType, MessageRecord $message) {
            $this->auditEvents[] = ['type' => $eventType, 'message' => $message];
        };

        $this->manager = new MessageManager(
            repository: $this->repository,
            connector: $this->connector,
            logger: $this->logger,
            rateLimiter: $this->rateLimiter,
            templateEngine: $this->templateEngine,
            auditLogger: $auditLogger,
        );
    }

    public function testSendMessageSuccessfully(): void
    {
        $this->rateLimiter
            ->expects($this->once())
            ->method('allowAction')
            ->willReturn(true);

        $sentMessage = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Hello',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        )->withDeliveryStatus(DeliveryStatus::Sent, 'provider-ref-123');

        $this->connector
            ->expects($this->once())
            ->method('send')
            ->willReturn($sentMessage);

        $this->repository
            ->expects($this->once())
            ->method('saveRecord')
            ->with($sentMessage);

        $result = $this->manager->sendMessage(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Hello',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        );

        $this->assertSame($sentMessage, $result);
        $this->assertCount(1, $this->auditEvents);
        $this->assertSame('message_sent', $this->auditEvents[0]['type']);
    }

    public function testSendMessageWithRateLimitExceeded(): void
    {
        $this->rateLimiter
            ->expects($this->once())
            ->method('allowAction')
            ->willReturn(false);

        $this->expectException(RateLimitExceededException::class);
        $this->expectExceptionMessage('Rate limit of 100 messages exceeded');

        $this->manager->sendMessage(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Hello',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        );
    }

    public function testSendMessageWithDeliveryFailure(): void
    {
        $this->rateLimiter
            ->expects($this->once())
            ->method('allowAction')
            ->willReturn(true);

        $this->connector
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \RuntimeException('Provider error'));

        $this->repository
            ->expects($this->once())
            ->method('saveRecord')
            ->with($this->callback(function (MessageRecord $message) {
                return $message->deliveryStatus === DeliveryStatus::Failed;
            }));

        $this->expectException(MessageDeliveryException::class);
        $this->expectExceptionMessage('Failed to deliver message');

        $this->manager->sendMessage(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Hello',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        );
    }

    public function testSendMessageWithoutRateLimiter(): void
    {
        $manager = new MessageManager(
            repository: $this->repository,
            connector: $this->connector,
            rateLimiter: null, // No rate limiter
        );

        $sentMessage = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::SMS,
            subject: null,
            body: 'Hello',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        )->withDeliveryStatus(DeliveryStatus::Sent);

        $this->connector
            ->expects($this->once())
            ->method('send')
            ->willReturn($sentMessage);

        $this->repository
            ->expects($this->once())
            ->method('saveRecord');

        $result = $manager->sendMessage(
            id: 'msg-001',
            channel: Channel::SMS,
            subject: null,
            body: 'Hello',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        );

        $this->assertSame($sentMessage, $result);
    }

    public function testSendFromTemplate(): void
    {
        $this->rateLimiter
            ->expects($this->once())
            ->method('allowAction')
            ->willReturn(true);

        $this->templateEngine
            ->expects($this->once())
            ->method('renderSubject')
            ->with('welcome_email', ['name' => 'John'])
            ->willReturn('Welcome John!');

        $this->templateEngine
            ->expects($this->once())
            ->method('render')
            ->with('welcome_email', ['name' => 'John'])
            ->willReturn('Hello John, welcome to our platform.');

        $sentMessage = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Welcome John!',
            body: 'Hello John, welcome to our platform.',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        )->withDeliveryStatus(DeliveryStatus::Sent);

        $this->connector
            ->expects($this->once())
            ->method('send')
            ->willReturn($sentMessage);

        $result = $this->manager->sendFromTemplate(
            id: 'msg-001',
            channel: Channel::Email,
            templateId: 'welcome_email',
            templateContext: ['name' => 'John'],
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        );

        $this->assertSame($sentMessage, $result);
    }

    public function testSendFromTemplateWithoutEngine(): void
    {
        $manager = new MessageManager(
            repository: $this->repository,
            connector: $this->connector,
            templateEngine: null, // No template engine
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template engine not configured');

        $manager->sendFromTemplate(
            id: 'msg-001',
            channel: Channel::Email,
            templateId: 'welcome_email',
            templateContext: ['name' => 'John'],
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        );
    }

    public function testProcessInboundWebhook(): void
    {
        $webhookPayload = [
            'from' => '+60123456789',
            'body' => 'Customer reply',
        ];

        $inboundMessage = MessageRecord::createInbound(
            id: 'msg-002',
            channel: Channel::SMS,
            subject: null,
            body: 'Customer reply',
            receivedAt: new \DateTimeImmutable('2024-11-24 10:00:00'),
            senderPartyId: 'customer-001',
            recipientPartyId: 'company-001',
            tenantId: 'tenant-001',
        );

        $this->connector
            ->expects($this->once())
            ->method('processInboundWebhook')
            ->with($webhookPayload)
            ->willReturn($inboundMessage);

        $this->repository
            ->expects($this->once())
            ->method('saveRecord')
            ->with($inboundMessage);

        $result = $this->manager->processInboundWebhook($webhookPayload);

        $this->assertSame($inboundMessage, $result);
        $this->assertCount(1, $this->auditEvents);
        $this->assertSame('message_received', $this->auditEvents[0]['type']);
    }

    public function testGetMessageFound(): void
    {
        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Hello',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('msg-001')
            ->willReturn($message);

        $result = $this->manager->getMessage('msg-001');

        $this->assertSame($message, $result);
    }

    public function testGetMessageNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('msg-999')
            ->willReturn(null);

        $this->expectException(MessageNotFoundException::class);
        $this->expectExceptionMessage("Message with ID 'msg-999' not found");

        $this->manager->getMessage('msg-999');
    }

    public function testGetConversationTimeline(): void
    {
        $messages = [
            MessageRecord::createOutbound(
                id: 'msg-001',
                channel: Channel::Email,
                subject: 'Test 1',
                body: 'Hello',
                senderPartyId: 'sender-001',
                recipientPartyId: 'recipient-001',
                tenantId: 'tenant-001',
                entityType: 'invoice',
                entityId: 'inv-001',
            ),
        ];

        $this->repository
            ->expects($this->once())
            ->method('findByEntity')
            ->with('invoice', 'inv-001', 50, 0)
            ->willReturn($messages);

        $result = $this->manager->getConversationTimeline('invoice', 'inv-001');

        $this->assertSame($messages, $result);
    }

    public function testGetLatestMessages(): void
    {
        $messages = [
            MessageRecord::createOutbound(
                id: 'msg-001',
                channel: Channel::Email,
                subject: 'Latest',
                body: 'Hello',
                senderPartyId: 'sender-001',
                recipientPartyId: 'recipient-001',
                tenantId: 'tenant-001',
                entityType: 'customer',
                entityId: 'cust-001',
            ),
        ];

        $this->repository
            ->expects($this->once())
            ->method('findLatestByEntity')
            ->with('customer', 'cust-001', 20)
            ->willReturn($messages);

        $result = $this->manager->getLatestMessages('customer', 'cust-001');

        $this->assertSame($messages, $result);
    }

    public function testUpdateDeliveryStatus(): void
    {
        $originalMessage = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Hello',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('msg-001')
            ->willReturn($originalMessage);

        $this->repository
            ->expects($this->once())
            ->method('saveRecord')
            ->with($this->callback(function (MessageRecord $message) {
                return $message->deliveryStatus === DeliveryStatus::Delivered
                    && $message->providerReferenceId === 'ref-123';
            }));

        $result = $this->manager->updateDeliveryStatus(
            'msg-001',
            DeliveryStatus::Delivered,
            'ref-123'
        );

        $this->assertSame(DeliveryStatus::Delivered, $result->deliveryStatus);
        $this->assertSame('ref-123', $result->providerReferenceId);
    }

    public function testUpdateDeliveryStatusMessageNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with('msg-999')
            ->willReturn(null);

        $this->expectException(MessageNotFoundException::class);

        $this->manager->updateDeliveryStatus('msg-999', DeliveryStatus::Delivered);
    }

    public function testSendMessageWithAllOptionalParameters(): void
    {
        $this->rateLimiter
            ->expects($this->once())
            ->method('allowAction')
            ->willReturn(true);

        $sentMessage = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::WhatsApp,
            subject: null,
            body: 'Hello with attachment',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
            attachments: [],
            metadata: ['priority' => 'high'],
            containsPII: true,
            entityType: 'support_ticket',
            entityId: 'ticket-001',
        )->withDeliveryStatus(DeliveryStatus::Sent);

        $this->connector
            ->expects($this->once())
            ->method('send')
            ->willReturn($sentMessage);

        $this->repository
            ->expects($this->once())
            ->method('saveRecord');

        $result = $this->manager->sendMessage(
            id: 'msg-001',
            channel: Channel::WhatsApp,
            subject: null,
            body: 'Hello with attachment',
            senderPartyId: 'sender-001',
            recipientPartyId: 'recipient-001',
            tenantId: 'tenant-001',
            attachments: [],
            metadata: ['priority' => 'high'],
            containsPII: true,
            entityType: 'support_ticket',
            entityId: 'ticket-001',
        );

        $this->assertSame($sentMessage, $result);
        $this->assertTrue($result->containsPII);
        $this->assertSame('support_ticket', $result->entityType);
    }
}
