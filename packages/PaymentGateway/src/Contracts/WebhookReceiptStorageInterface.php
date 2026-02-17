<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Contracts;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\WebhookStatus;

interface WebhookReceiptStorageInterface
{
    public function store(string $eventId, WebhookStatus $status, ?string $message = null): void;
    public function getStatus(string $eventId): ?WebhookStatus;
    
    /**
     * Record webhook receipt with full details.
     *
     * @param GatewayProvider $provider Payment gateway provider
     * @param string $eventId Unique event identifier
     * @param string $payload Raw webhook payload
     * @param array<string, mixed> $headers Webhook headers
     */
    public function recordReceipt(
        GatewayProvider $provider,
        string $eventId,
        string $payload,
        array $headers
    ): void;
    
    /**
     * Update webhook processing status.
     *
     * @param GatewayProvider $provider Payment gateway provider
     * @param string $eventId Unique event identifier
     * @param WebhookStatus $status New status
     * @param string|null $errorMessage Optional error message
     */
    public function updateStatus(
        GatewayProvider $provider,
        string $eventId,
        WebhookStatus $status,
        ?string $errorMessage = null
    ): void;
}
