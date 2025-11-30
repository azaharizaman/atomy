<?php

declare(strict_types=1);

namespace Nexus\Messaging\Contracts;

use Nexus\Messaging\ValueObjects\MessageRecord;

/**
 * Connector contract for external messaging providers
 * 
 * Application layer implements this using Nexus\Connector for:
 * - Email: SendGrid, Postmark, AWS SES
 * - SMS: Twilio, Nexmo
 * - WhatsApp: Twilio WhatsApp API, Meta Business API
 * - Chat: Slack, Discord
 * 
 * This abstraction keeps the Messaging package protocol-agnostic.
 * 
 * @package Nexus\Messaging
 */
interface MessagingConnectorInterface
{
    /**
     * Send outbound message via external provider
     * 
     * L2.2: Connector abstraction for sending
     * 
     * @param MessageRecord $draft Draft message to send
     * @return MessageRecord Updated message with delivery status and provider reference
     * @throws \Nexus\Messaging\Exceptions\MessageDeliveryException
     */
    public function send(MessageRecord $draft): MessageRecord;

    /**
     * Process inbound webhook from external provider
     * 
     * L2.6: Inbound webhook processing
     * 
     * Parses provider-specific webhook payload and returns standardized MessageRecord.
     * 
     * Example implementations:
     * - TwilioWhatsAppConnector: Parses Twilio webhook JSON
     * - SendGridConnector: Parses SendGrid inbound parse webhook
     * 
     * @param array<string, mixed> $webhookPayload Raw webhook data from provider
     * @return MessageRecord Standardized inbound message
     * @throws \Nexus\Messaging\Exceptions\MessagingException
     */
    public function processInboundWebhook(array $webhookPayload): MessageRecord;

    /**
     * Get supported channel
     * 
     * @return string Channel this connector handles (e.g., 'whatsapp', 'email')
     */
    public function getSupportedChannel(): string;

    /**
     * Check if connector is configured and ready
     * 
     * @return bool
     */
    public function isConfigured(): bool;
}
