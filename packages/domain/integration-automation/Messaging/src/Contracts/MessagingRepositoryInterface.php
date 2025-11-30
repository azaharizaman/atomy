<?php

declare(strict_types=1);

namespace Nexus\Messaging\Contracts;

use Nexus\Messaging\ValueObjects\MessageRecord;

/**
 * Repository contract for message persistence
 * 
 * Application layer implements this using database (Eloquent, Doctrine, etc.)
 * 
 * @package Nexus\Messaging
 */
interface MessagingRepositoryInterface
{
    /**
     * Save message record (immutable - no updates)
     * 
     * @param MessageRecord $record
     * @return void
     * @throws \Nexus\Messaging\Exceptions\MessagingException
     */
    public function saveRecord(MessageRecord $record): void;

    /**
     * Find message by ID
     * 
     * @param string $id
     * @return MessageRecord|null
     */
    public function findById(string $id): ?MessageRecord;

    /**
     * Find all messages for an entity (builds conversation timeline)
     * 
     * @param string $entityType Entity type (e.g., 'case', 'customer', 'order')
     * @param string $entityId Entity ID
     * @param int $limit Maximum number of records to return
     * @param int $offset Pagination offset
     * @return array<MessageRecord>
     */
    public function findByEntity(
        string $entityType,
        string $entityId,
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * Find latest messages for entity (optimized for timeline loading)
     * 
     * L3.4: High-speed retrieval for UI timeline
     * 
     * @param string $entityType
     * @param string $entityId
     * @param int $limit
     * @return array<MessageRecord>
     */
    public function findLatestByEntity(
        string $entityType,
        string $entityId,
        int $limit = 20
    ): array;

    /**
     * Find messages by tenant
     * 
     * @param string $tenantId
     * @param int $limit
     * @param int $offset
     * @return array<MessageRecord>
     */
    public function findByTenant(
        string $tenantId,
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * Find messages by sender party
     * 
     * @param string $senderPartyId
     * @param int $limit
     * @param int $offset
     * @return array<MessageRecord>
     */
    public function findBySender(
        string $senderPartyId,
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * Find messages by channel
     * 
     * @param string $channel Channel value
     * @param string $tenantId Tenant ID for isolation
     * @param int $limit
     * @param int $offset
     * @return array<MessageRecord>
     */
    public function findByChannel(
        string $channel,
        string $tenantId,
        int $limit = 50,
        int $offset = 0
    ): array;

    /**
     * Count messages for entity
     * 
     * @param string $entityType
     * @param string $entityId
     * @return int
     */
    public function countByEntity(string $entityType, string $entityId): int;
}
