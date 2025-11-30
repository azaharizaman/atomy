<?php

declare(strict_types=1);

namespace Nexus\Messaging\Tests\Unit\ValueObjects;

use Nexus\Messaging\Enums\ArchivalStatus;
use Nexus\Messaging\Enums\Channel;
use Nexus\Messaging\Enums\DeliveryStatus;
use Nexus\Messaging\Enums\Direction;
use Nexus\Messaging\ValueObjects\AttachmentMetadata;
use Nexus\Messaging\ValueObjects\MessageRecord;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nexus\Messaging\ValueObjects\MessageRecord
 */
final class MessageRecordTest extends TestCase
{
    public function test_can_create_outbound_message(): void
    {
        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test Subject',
            body: 'Test body',
            senderPartyId: 'party-123',
            recipientPartyId: 'party-456',
            tenantId: 'tenant-001'
        );

        $this->assertSame('msg-001', $message->id);
        $this->assertSame(Channel::Email, $message->channel);
        $this->assertSame(Direction::Outbound, $message->direction);
        $this->assertSame('Test Subject', $message->subject);
        $this->assertSame('Test body', $message->body);
        $this->assertSame('party-123', $message->senderPartyId);
        $this->assertSame('party-456', $message->recipientPartyId);
        $this->assertSame('tenant-001', $message->tenantId);
        $this->assertSame(DeliveryStatus::Pending, $message->deliveryStatus);
        $this->assertTrue($message->isOutbound());
        $this->assertFalse($message->isInbound());
    }

    public function test_can_create_inbound_message(): void
    {
        $receivedAt = new \DateTimeImmutable('2025-11-24 10:00:00');
        
        $message = MessageRecord::createInbound(
            id: 'msg-002',
            channel: Channel::WhatsApp,
            subject: null,
            body: 'Inbound message',
            receivedAt: $receivedAt,
            senderPartyId: 'party-789',
            recipientPartyId: 'party-123',
            tenantId: 'tenant-001',
            providerReferenceId: 'twilio-sid-123'
        );

        $this->assertSame('msg-002', $message->id);
        $this->assertSame(Direction::Inbound, $message->direction);
        $this->assertSame(DeliveryStatus::Delivered, $message->deliveryStatus);
        $this->assertSame('twilio-sid-123', $message->providerReferenceId);
        $this->assertTrue($message->isInbound());
        $this->assertFalse($message->isOutbound());
        $this->assertTrue($message->wasDelivered());
    }

    public function test_throws_exception_for_empty_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message ID cannot be empty');

        MessageRecord::createOutbound(
            id: '',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Test body',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: 'tenant-001'
        );
    }

    public function test_throws_exception_for_empty_body(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message body cannot be empty');

        MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: '',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: 'tenant-001'
        );
    }

    public function test_throws_exception_for_empty_sender_party_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sender Party ID cannot be empty');

        MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Test body',
            senderPartyId: '',
            recipientPartyId: null,
            tenantId: 'tenant-001'
        );
    }

    public function test_throws_exception_for_empty_tenant_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tenant ID cannot be empty');

        MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Test body',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: ''
        );
    }

    public function test_can_update_delivery_status(): void
    {
        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::SMS,
            subject: null,
            body: 'Test SMS',
            senderPartyId: 'party-123',
            recipientPartyId: 'party-456',
            tenantId: 'tenant-001'
        );

        $this->assertSame(DeliveryStatus::Pending, $message->deliveryStatus);
        $this->assertNull($message->providerReferenceId);

        $updated = $message->withDeliveryStatus(
            DeliveryStatus::Delivered,
            'twilio-sid-abc'
        );

        // Original unchanged (immutability)
        $this->assertSame(DeliveryStatus::Pending, $message->deliveryStatus);
        
        // New instance has updated values
        $this->assertSame(DeliveryStatus::Delivered, $updated->deliveryStatus);
        $this->assertSame('twilio-sid-abc', $updated->providerReferenceId);
        $this->assertTrue($updated->wasDelivered());
    }

    public function test_can_update_archival_status(): void
    {
        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Test body',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: 'tenant-001'
        );

        $this->assertSame(ArchivalStatus::Active, $message->archivalStatus);

        $archived = $message->withArchivalStatus(ArchivalStatus::Archived);

        $this->assertSame(ArchivalStatus::Active, $message->archivalStatus);
        $this->assertSame(ArchivalStatus::Archived, $archived->archivalStatus);
    }

    public function test_can_associate_with_entity(): void
    {
        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Test body',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: 'tenant-001'
        );

        $this->assertNull($message->entityType);
        $this->assertNull($message->entityId);
        $this->assertFalse($message->isAssociatedWithEntity());

        $associated = $message->withEntity('case', 'case-789');

        $this->assertSame('case', $associated->entityType);
        $this->assertSame('case-789', $associated->entityId);
        $this->assertTrue($associated->isAssociatedWithEntity());
    }

    public function test_attachments(): void
    {
        $attachment1 = new AttachmentMetadata('file1.pdf', 'application/pdf', 1024);
        $attachment2 = new AttachmentMetadata('file2.jpg', 'image/jpeg', 2048);

        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Test body',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: 'tenant-001',
            attachments: [$attachment1, $attachment2]
        );

        $this->assertTrue($message->hasAttachments());
        $this->assertSame(2, $message->getAttachmentCount());
        $this->assertCount(2, $message->attachments);
    }

    public function test_metadata(): void
    {
        $metadata = [
            'email_headers' => ['X-Custom' => 'value'],
            'call_duration' => 120,
        ];

        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Test body',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: 'tenant-001',
            metadata: $metadata
        );

        $this->assertSame(['X-Custom' => 'value'], $message->getMetadata('email_headers'));
        $this->assertSame(120, $message->getMetadata('call_duration'));
        $this->assertNull($message->getMetadata('nonexistent'));
        $this->assertSame('default', $message->getMetadata('nonexistent', 'default'));
    }

    public function test_pii_flag(): void
    {
        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Confidential',
            body: 'Contains PII',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: 'tenant-001',
            containsPII: true
        );

        $this->assertTrue($message->containsPII);
    }

    public function test_to_array(): void
    {
        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::Email,
            subject: 'Test',
            body: 'Test body',
            senderPartyId: 'party-123',
            recipientPartyId: 'party-456',
            tenantId: 'tenant-001'
        );

        $array = $message->toArray();

        $this->assertSame('msg-001', $array['id']);
        $this->assertSame('email', $array['channel']);
        $this->assertSame('outbound', $array['direction']);
        $this->assertSame('Test', $array['subject']);
        $this->assertSame('Test body', $array['body']);
        $this->assertSame('party-123', $array['sender_party_id']);
        $this->assertSame('party-456', $array['recipient_party_id']);
        $this->assertSame('tenant-001', $array['tenant_id']);
        $this->assertSame('pending', $array['delivery_status']);
    }

    public function test_delivery_failed(): void
    {
        $message = MessageRecord::createOutbound(
            id: 'msg-001',
            channel: Channel::SMS,
            subject: null,
            body: 'Test',
            senderPartyId: 'party-123',
            recipientPartyId: null,
            tenantId: 'tenant-001'
        );

        $failed = $message->withDeliveryStatus(DeliveryStatus::Failed);

        $this->assertTrue($failed->hasFailed());
        $this->assertFalse($failed->wasDelivered());
    }
}
