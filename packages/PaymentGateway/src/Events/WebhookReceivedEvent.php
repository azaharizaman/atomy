<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Events;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\WebhookPayload;

/**
 * Event dispatched when a webhook is received from a gateway.
 */
final readonly class WebhookReceivedEvent
{
    public function __construct(
        public string $tenantId,
        public GatewayProvider $provider,
        public WebhookPayload $payload,
        public \DateTimeImmutable $receivedAt,
    ) {}

    /**
     * Create from webhook payload.
     */
    public static function fromPayload(
        string $tenantId,
        GatewayProvider $provider,
        WebhookPayload $payload,
    ): self {
        return new self(
            tenantId: $tenantId,
            provider: $provider,
            payload: $payload,
            receivedAt: new \DateTimeImmutable(),
        );
    }
}
