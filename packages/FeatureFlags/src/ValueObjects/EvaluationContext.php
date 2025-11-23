<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\ValueObjects;

/**
 * Immutable evaluation context for feature flag evaluation.
 *
 * Contains all data required to evaluate flags:
 * - tenantId: For TENANT_LIST strategy and tenant scoping
 * - userId: For USER_LIST strategy and PERCENTAGE_ROLLOUT
 * - sessionId: For PERCENTAGE_ROLLOUT when userId not available
 * - customAttributes: For CUSTOM strategy evaluators
 */
final readonly class EvaluationContext
{
    /**
     * Create a new evaluation context.
     *
     * @param string|null $tenantId The tenant identifier
     * @param string|null $userId The user identifier
     * @param string|null $sessionId The session identifier
     * @param array<string, mixed> $customAttributes Additional attributes for CUSTOM evaluators
     */
    public function __construct(
        public ?string $tenantId = null,
        public ?string $userId = null,
        public ?string $sessionId = null,
        public array $customAttributes = []
    ) {
    }

    /**
     * Create context from an array of data.
     *
     * Accepts keys: tenant_id, user_id, session_id, and any custom attributes.
     *
     * @param array<string, mixed> $data The context data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $tenantId = $data['tenant_id'] ?? $data['tenantId'] ?? null;
        $userId = $data['user_id'] ?? $data['userId'] ?? null;
        $sessionId = $data['session_id'] ?? $data['sessionId'] ?? null;

        // Everything else goes into custom attributes
        $customAttributes = array_diff_key(
            $data,
            array_flip(['tenant_id', 'tenantId', 'user_id', 'userId', 'session_id', 'sessionId'])
        );

        return new self(
            tenantId: is_string($tenantId) ? $tenantId : null,
            userId: is_string($userId) ? $userId : null,
            sessionId: is_string($sessionId) ? $sessionId : null,
            customAttributes: $customAttributes
        );
    }

    /**
     * Get the first available stable identifier for percentage rollout.
     *
     * Priority: userId > sessionId > tenantId
     *
     * @return string|null The stable identifier or null if none available
     */
    public function getStableIdentifier(): ?string
    {
        return $this->userId ?? $this->sessionId ?? $this->tenantId;
    }

    /**
     * Convert context to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'custom_attributes' => $this->customAttributes,
        ];
    }
}
