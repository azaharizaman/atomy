<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\DTOs;

/**
 * Request to void/cancel an authorization.
 */
final readonly class VoidRequest
{
    /**
     * @param string $authorizationId Authorization ID to void
     * @param string|null $reason Reason for voiding
     * @param array<string, mixed> $metadata Additional metadata
     * @param string|null $idempotencyKey Idempotency key for safe retries
     */
    public function __construct(
        public string $authorizationId,
        public ?string $reason = null,
        public array $metadata = [],
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * Create void request.
     */
    public static function create(string $authorizationId, ?string $reason = null): self
    {
        return new self(
            authorizationId: $authorizationId,
            reason: $reason,
        );
    }
}
