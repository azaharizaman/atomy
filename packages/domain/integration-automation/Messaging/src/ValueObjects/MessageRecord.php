<?php

declare(strict_types=1);

namespace Nexus\Messaging\ValueObjects;

use Nexus\Messaging\Enums\ArchivalStatus;
use Nexus\Messaging\Enums\Channel;
use Nexus\Messaging\Enums\DeliveryStatus;
use Nexus\Messaging\Enums\Direction;

/**
 * Immutable record of a communication event
 * 
 * This is the aggregate root for the Messaging domain. Once created, records cannot be modified
 * (except for updating delivery status via withDeliveryStatus() which creates new instance).
 * 
 * Design Principle: Records the WHAT (communication happened), not the HOW (protocol details).
 * Protocol-specific logic is delegated to application layer via MessagingConnectorInterface.
 * 
 * @package Nexus\Messaging
 */
final readonly class MessageRecord
{
    /**
     * @param string $id Unique message identifier (ULID)
     * @param Channel $channel Communication channel
     * @param Direction $direction Message flow direction
     * @param string|null $subject Message subject (nullable for SMS/Chat)
     * @param string $body Message body content
     * @param \DateTimeImmutable $sentAt When message was sent/received
     * @param string $senderPartyId ID of sending party (links to Nexus\Party)
     * @param string|null $recipientPartyId ID of recipient party (if known)
     * @param DeliveryStatus $deliveryStatus Current delivery status
     * @param string|null $providerReferenceId External provider's reference (Twilio SID, SendGrid ID, etc.)
     * @param string $tenantId Multi-tenant isolation
     * @param array<AttachmentMetadata> $attachments Attachment metadata (no file I/O)
     * @param array<string, mixed> $metadata Channel-specific data (email headers, call duration, etc.)
     * @param bool $containsPII Flag for PII compliance
     * @param ArchivalStatus $archivalStatus Retention policy status
     * @param string|null $entityType Associated entity type (e.g., 'case', 'customer', 'order')
     * @param string|null $entityId Associated entity ID
     */
    public function __construct(
        public string $id,
        public Channel $channel,
        public Direction $direction,
        public ?string $subject,
        public string $body,
        public \DateTimeImmutable $sentAt,
        public string $senderPartyId,
        public ?string $recipientPartyId,
        public DeliveryStatus $deliveryStatus,
        public ?string $providerReferenceId,
        public string $tenantId,
        public array $attachments = [],
        public array $metadata = [],
        public bool $containsPII = false,
        public ArchivalStatus $archivalStatus = ArchivalStatus::Active,
        public ?string $entityType = null,
        public ?string $entityId = null,
    ) {
        if (empty($this->id)) {
            throw new \InvalidArgumentException('Message ID cannot be empty');
        }

        if (empty($this->body)) {
            throw new \InvalidArgumentException('Message body cannot be empty');
        }

        if (empty($this->senderPartyId)) {
            throw new \InvalidArgumentException('Sender Party ID cannot be empty');
        }

        if (empty($this->tenantId)) {
            throw new \InvalidArgumentException('Tenant ID cannot be empty');
        }

        // Validate attachments array contains only AttachmentMetadata instances
        foreach ($this->attachments as $attachment) {
            if (!$attachment instanceof AttachmentMetadata) {
                throw new \InvalidArgumentException('Attachments must be instances of AttachmentMetadata');
            }
        }
    }

    /**
     * Create outbound draft message
     * 
     * Factory method for creating new outbound messages before sending.
     */
    public static function createOutbound(
        string $id,
        Channel $channel,
        ?string $subject,
        string $body,
        string $senderPartyId,
        ?string $recipientPartyId,
        string $tenantId,
        array $attachments = [],
        array $metadata = [],
        bool $containsPII = false,
        ?string $entityType = null,
        ?string $entityId = null,
    ): self {
        return new self(
            id: $id,
            channel: $channel,
            direction: Direction::Outbound,
            subject: $subject,
            body: $body,
            sentAt: new \DateTimeImmutable(),
            senderPartyId: $senderPartyId,
            recipientPartyId: $recipientPartyId,
            deliveryStatus: DeliveryStatus::Pending,
            providerReferenceId: null,
            tenantId: $tenantId,
            attachments: $attachments,
            metadata: $metadata,
            containsPII: $containsPII,
            entityType: $entityType,
            entityId: $entityId,
        );
    }

    /**
     * Create inbound message from webhook
     * 
     * Factory method for creating inbound messages received from external providers.
     */
    public static function createInbound(
        string $id,
        Channel $channel,
        ?string $subject,
        string $body,
        \DateTimeImmutable $receivedAt,
        string $senderPartyId,
        ?string $recipientPartyId,
        string $tenantId,
        ?string $providerReferenceId = null,
        array $attachments = [],
        array $metadata = [],
        bool $containsPII = false,
        ?string $entityType = null,
        ?string $entityId = null,
    ): self {
        return new self(
            id: $id,
            channel: $channel,
            direction: Direction::Inbound,
            subject: $subject,
            body: $body,
            sentAt: $receivedAt,
            senderPartyId: $senderPartyId,
            recipientPartyId: $recipientPartyId,
            deliveryStatus: DeliveryStatus::Delivered, // Inbound messages are already delivered
            providerReferenceId: $providerReferenceId,
            tenantId: $tenantId,
            attachments: $attachments,
            metadata: $metadata,
            containsPII: $containsPII,
            entityType: $entityType,
            entityId: $entityId,
        );
    }

    /**
     * Update delivery status (creates new instance - immutability)
     */
    public function withDeliveryStatus(
        DeliveryStatus $status,
        ?string $providerReferenceId = null
    ): self {
        return new self(
            id: $this->id,
            channel: $this->channel,
            direction: $this->direction,
            subject: $this->subject,
            body: $this->body,
            sentAt: $this->sentAt,
            senderPartyId: $this->senderPartyId,
            recipientPartyId: $this->recipientPartyId,
            deliveryStatus: $status,
            providerReferenceId: $providerReferenceId ?? $this->providerReferenceId,
            tenantId: $this->tenantId,
            attachments: $this->attachments,
            metadata: $this->metadata,
            containsPII: $this->containsPII,
            archivalStatus: $this->archivalStatus,
            entityType: $this->entityType,
            entityId: $this->entityId,
        );
    }

    /**
     * Mark for archival (creates new instance)
     */
    public function withArchivalStatus(ArchivalStatus $status): self
    {
        return new self(
            id: $this->id,
            channel: $this->channel,
            direction: $this->direction,
            subject: $this->subject,
            body: $this->body,
            sentAt: $this->sentAt,
            senderPartyId: $this->senderPartyId,
            recipientPartyId: $this->recipientPartyId,
            deliveryStatus: $this->deliveryStatus,
            providerReferenceId: $this->providerReferenceId,
            tenantId: $this->tenantId,
            attachments: $this->attachments,
            metadata: $this->metadata,
            containsPII: $this->containsPII,
            archivalStatus: $status,
            entityType: $this->entityType,
            entityId: $this->entityId,
        );
    }

    /**
     * Associate with entity (creates new instance)
     */
    public function withEntity(string $entityType, string $entityId): self
    {
        return new self(
            id: $this->id,
            channel: $this->channel,
            direction: $this->direction,
            subject: $this->subject,
            body: $this->body,
            sentAt: $this->sentAt,
            senderPartyId: $this->senderPartyId,
            recipientPartyId: $this->recipientPartyId,
            deliveryStatus: $this->deliveryStatus,
            providerReferenceId: $this->providerReferenceId,
            tenantId: $this->tenantId,
            attachments: $this->attachments,
            metadata: $this->metadata,
            containsPII: $this->containsPII,
            archivalStatus: $this->archivalStatus,
            entityType: $entityType,
            entityId: $entityId,
        );
    }

    /**
     * Check if message is outbound
     */
    public function isOutbound(): bool
    {
        return $this->direction->isOutbound();
    }

    /**
     * Check if message is inbound
     */
    public function isInbound(): bool
    {
        return $this->direction->isInbound();
    }

    /**
     * Check if delivery was successful
     */
    public function wasDelivered(): bool
    {
        return $this->deliveryStatus->isSuccessful();
    }

    /**
     * Check if delivery failed
     */
    public function hasFailed(): bool
    {
        return $this->deliveryStatus->isFailed();
    }

    /**
     * Check if message has attachments
     */
    public function hasAttachments(): bool
    {
        return count($this->attachments) > 0;
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCount(): int
    {
        return count($this->attachments);
    }

    /**
     * Check if message is associated with entity
     */
    public function isAssociatedWithEntity(): bool
    {
        return $this->entityType !== null && $this->entityId !== null;
    }

    /**
     * Get metadata value by key
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel->value,
            'direction' => $this->direction->value,
            'subject' => $this->subject,
            'body' => $this->body,
            'sent_at' => $this->sentAt->format('c'),
            'sender_party_id' => $this->senderPartyId,
            'recipient_party_id' => $this->recipientPartyId,
            'delivery_status' => $this->deliveryStatus->value,
            'provider_reference_id' => $this->providerReferenceId,
            'tenant_id' => $this->tenantId,
            'attachments' => array_map(fn($a) => $a->toArray(), $this->attachments),
            'metadata' => $this->metadata,
            'contains_pii' => $this->containsPII,
            'archival_status' => $this->archivalStatus->value,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
        ];
    }
}
