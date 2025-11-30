<?php

declare(strict_types=1);

namespace Nexus\Messaging\Services;

use Nexus\Messaging\Contracts\MessageTemplateEngineInterface;
use Nexus\Messaging\Contracts\MessagingConnectorInterface;
use Nexus\Messaging\Contracts\MessagingRepositoryInterface;
use Nexus\Messaging\Contracts\RateLimiterInterface;
use Nexus\Messaging\Enums\Channel;
use Nexus\Messaging\Enums\DeliveryStatus;
use Nexus\Messaging\Exceptions\MessageDeliveryException;
use Nexus\Messaging\Exceptions\MessageNotFoundException;
use Nexus\Messaging\Exceptions\RateLimitExceededException;
use Nexus\Messaging\ValueObjects\AttachmentMetadata;
use Nexus\Messaging\ValueObjects\MessageRecord;
use Psr\Log\LoggerInterface;

/**
 * Message management service (core orchestrator)
 * 
 * Responsibilities:
 * - Orchestrate outbound message flow (draft → send → persist)
 * - Process inbound webhooks
 * - Query conversation timelines
 * - Enforce rate limits (L3.1)
 * - Fire audit events (L1.6)
 * 
 * This service knows WHAT to do, not HOW (protocol abstraction).
 * 
 * @package Nexus\Messaging
 */
final readonly class MessageManager
{
    /**
     * @param MessagingRepositoryInterface $repository Message persistence
     * @param MessagingConnectorInterface $connector External provider integration
     * @param LoggerInterface|null $logger Optional PSR-3 logger
     * @param RateLimiterInterface|null $rateLimiter Optional rate limiting (L3.1)
     * @param MessageTemplateEngineInterface|null $templateEngine Optional template rendering (L3.3)
     * @param callable|null $auditLogger Optional audit event dispatcher (L1.6)
     */
    public function __construct(
        private MessagingRepositoryInterface $repository,
        private MessagingConnectorInterface $connector,
        private ?LoggerInterface $logger = null,
        private ?RateLimiterInterface $rateLimiter = null,
        private ?MessageTemplateEngineInterface $templateEngine = null,
        private mixed $auditLogger = null,
    ) {
    }

    /**
     * Send outbound message
     * 
     * L2.3: Outbound workflow - draft → send → update status → save
     * 
     * @param string $id Unique message ID (ULID)
     * @param Channel $channel Communication channel
     * @param string|null $subject Message subject (nullable for SMS/Chat)
     * @param string $body Message body
     * @param string $senderPartyId Sender party ID (from Nexus\Party)
     * @param string|null $recipientPartyId Recipient party ID
     * @param string $tenantId Tenant ID for isolation
     * @param array<AttachmentMetadata> $attachments
     * @param array<string, mixed> $metadata Channel-specific data
     * @param bool $containsPII PII flag for compliance
     * @param string|null $entityType Associated entity type
     * @param string|null $entityId Associated entity ID
     * @return MessageRecord Sent message with updated delivery status
     * @throws RateLimitExceededException
     * @throws MessageDeliveryException
     */
    public function sendMessage(
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
    ): MessageRecord {
        // L3.1: Check rate limit before sending
        $this->checkRateLimit($tenantId, $channel);

        // Create draft message
        $draft = MessageRecord::createOutbound(
            id: $id,
            channel: $channel,
            subject: $subject,
            body: $body,
            senderPartyId: $senderPartyId,
            recipientPartyId: $recipientPartyId,
            tenantId: $tenantId,
            attachments: $attachments,
            metadata: $metadata,
            containsPII: $containsPII,
            entityType: $entityType,
            entityId: $entityId,
        );

        $this->logger?->info('Sending outbound message', [
            'message_id' => $id,
            'channel' => $channel->value,
            'tenant_id' => $tenantId,
        ]);

        try {
            // L2.2: Call connector to send via external provider
            $sentMessage = $this->connector->send($draft);

            // L1.3: Persist the message record
            $this->repository->saveRecord($sentMessage);

            // L1.6: Fire audit event
            $this->fireAuditEvent('message_sent', $sentMessage);

            $this->logger?->info('Message sent successfully', [
                'message_id' => $sentMessage->id,
                'delivery_status' => $sentMessage->deliveryStatus->value,
                'provider_reference' => $sentMessage->providerReferenceId,
            ]);

            return $sentMessage;

        } catch (\Throwable $e) {
            // Update draft with failed status and persist
            $failedMessage = $draft->withDeliveryStatus(DeliveryStatus::Failed);
            $this->repository->saveRecord($failedMessage);

            $this->logger?->error('Message delivery failed', [
                'message_id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw MessageDeliveryException::forMessage($id, $e->getMessage());
        }
    }

    /**
     * Send message from template
     * 
     * L3.3: Template rendering abstraction
     * 
     * @param string $id Message ID
     * @param Channel $channel
     * @param string $templateId Template identifier
     * @param array<string, mixed> $templateContext
     * @param string $senderPartyId
     * @param string|null $recipientPartyId
     * @param string $tenantId
     * @param array<AttachmentMetadata> $attachments
     * @param array<string, mixed> $metadata
     * @param bool $containsPII
     * @param string|null $entityType
     * @param string|null $entityId
     * @return MessageRecord
     * @throws \RuntimeException If template engine not configured
     */
    public function sendFromTemplate(
        string $id,
        Channel $channel,
        string $templateId,
        array $templateContext,
        string $senderPartyId,
        ?string $recipientPartyId,
        string $tenantId,
        array $attachments = [],
        array $metadata = [],
        bool $containsPII = false,
        ?string $entityType = null,
        ?string $entityId = null,
    ): MessageRecord {
        if ($this->templateEngine === null) {
            throw new \RuntimeException('Template engine not configured');
        }

        $subject = $this->templateEngine->renderSubject($templateId, $templateContext);
        $body = $this->templateEngine->render($templateId, $templateContext);

        return $this->sendMessage(
            id: $id,
            channel: $channel,
            subject: $subject,
            body: $body,
            senderPartyId: $senderPartyId,
            recipientPartyId: $recipientPartyId,
            tenantId: $tenantId,
            attachments: $attachments,
            metadata: $metadata,
            containsPII: $containsPII,
            entityType: $entityType,
            entityId: $entityId,
        );
    }

    /**
     * Process inbound webhook from external provider
     * 
     * L2.6: Webhook processing
     * 
     * @param array<string, mixed> $webhookPayload Raw webhook data
     * @return MessageRecord Persisted inbound message
     */
    public function processInboundWebhook(array $webhookPayload): MessageRecord
    {
        $this->logger?->info('Processing inbound webhook', [
            'provider' => $this->connector->getSupportedChannel(),
        ]);

        // L2.6: Connector transforms raw webhook to standardized MessageRecord
        $inboundMessage = $this->connector->processInboundWebhook($webhookPayload);

        // L1.3: Persist inbound message
        $this->repository->saveRecord($inboundMessage);

        // L1.6: Fire audit event
        $this->fireAuditEvent('message_received', $inboundMessage);

        $this->logger?->info('Inbound message processed', [
            'message_id' => $inboundMessage->id,
            'channel' => $inboundMessage->channel->value,
        ]);

        return $inboundMessage;
    }

    /**
     * Get message by ID
     * 
     * @param string $id
     * @return MessageRecord
     * @throws MessageNotFoundException
     */
    public function getMessage(string $id): MessageRecord
    {
        $message = $this->repository->findById($id);

        if ($message === null) {
            throw MessageNotFoundException::withId($id);
        }

        return $message;
    }

    /**
     * Get conversation timeline for entity
     * 
     * L1.4: Entity association for conversation panels
     * 
     * @param string $entityType
     * @param string $entityId
     * @param int $limit
     * @param int $offset
     * @return array<MessageRecord>
     */
    public function getConversationTimeline(
        string $entityType,
        string $entityId,
        int $limit = 50,
        int $offset = 0
    ): array {
        return $this->repository->findByEntity($entityType, $entityId, $limit, $offset);
    }

    /**
     * Get latest messages for entity (optimized)
     * 
     * L3.4: High-speed retrieval for UI timeline
     * 
     * @param string $entityType
     * @param string $entityId
     * @param int $limit
     * @return array<MessageRecord>
     */
    public function getLatestMessages(
        string $entityType,
        string $entityId,
        int $limit = 20
    ): array {
        return $this->repository->findLatestByEntity($entityType, $entityId, $limit);
    }

    /**
     * Update delivery status (for webhook callbacks)
     * 
     * @param string $messageId
     * @param DeliveryStatus $status
     * @param string|null $providerReferenceId
     * @return MessageRecord
     * @throws MessageNotFoundException
     */
    public function updateDeliveryStatus(
        string $messageId,
        DeliveryStatus $status,
        ?string $providerReferenceId = null
    ): MessageRecord {
        $message = $this->getMessage($messageId);
        
        $updatedMessage = $message->withDeliveryStatus($status, $providerReferenceId);
        
        // Note: This violates immutability principle but is necessary for webhook updates
        // Application layer should handle this by storing new version
        $this->repository->saveRecord($updatedMessage);

        $this->logger?->info('Delivery status updated', [
            'message_id' => $messageId,
            'status' => $status->value,
        ]);

        return $updatedMessage;
    }

    /**
     * Check rate limit before sending
     * 
     * L3.1: High-volume throttling
     * 
     * @param string $tenantId
     * @param Channel $channel
     * @return void
     * @throws RateLimitExceededException
     */
    private function checkRateLimit(string $tenantId, Channel $channel): void
    {
        if ($this->rateLimiter === null) {
            return; // Rate limiting not configured
        }

        $key = "messaging:tenant:{$tenantId}:channel:{$channel->value}";
        $maxAttempts = 100; // Default: 100 messages per minute
        $decaySeconds = 60;

        if (!$this->rateLimiter->allowAction($key, $maxAttempts, $decaySeconds)) {
            $this->logger?->warning('Rate limit exceeded', [
                'tenant_id' => $tenantId,
                'channel' => $channel->value,
            ]);

            throw RateLimitExceededException::forTenant($tenantId, $maxAttempts);
        }
    }

    /**
     * Fire audit event
     * 
     * L1.6: Integration with Nexus\AuditLogger
     * 
     * @param string $eventType
     * @param MessageRecord $message
     * @return void
     */
    private function fireAuditEvent(string $eventType, MessageRecord $message): void
    {
        if ($this->auditLogger === null) {
            return;
        }

        ($this->auditLogger)($eventType, $message);
    }
}
